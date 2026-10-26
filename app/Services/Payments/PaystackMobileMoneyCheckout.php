<?php

namespace App\Services\Payments;

use App\Checkout\Payable;
use App\Checkout\PayableProvider;
use App\Models\MobileMoneyCheckout;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Checkout Mobile Money personnalisé (Paystack /charge), commun à tous les types de paiement.
 *
 * Cycle : start() crée l'enregistrement métier en attente (via le PayableProvider du type)
 * puis le charge Paystack ; l'utilisateur valide sur son téléphone ; le webhook
 * charge.success valide le paiement en base ; refresh() (polling) lit la base et, en
 * secours, interroge Paystack (GET /charge après 10 s, transaction/verify après 180 s).
 */
class PaystackMobileMoneyCheckout
{
    public const FIRST_CHECK_AFTER = 10;  // s avant le premier GET /charge
    public const CHECK_EVERY       = 8;   // s entre deux appels Paystack pendant le polling

    public function __construct(private PaystackService $paystack) {}

    // ─── Configuration / activation ────────────────────────────────────────

    public function exists(string $type): bool
    {
        return (bool) config("checkout.types.{$type}");
    }

    public function isEnabled(string $type): bool
    {
        return $this->exists($type) && (bool) config("checkout.types.{$type}.enabled");
    }

    /** L'ancien flux doit-il rediriger vers le checkout ? (?classic=1 force l'ancien flux) */
    public function shouldIntercept(string $type, Request $request): bool
    {
        return $this->isEnabled($type) && !$request->boolean('classic');
    }

    public function entryUrl(string $type, array $params = []): string
    {
        return route('checkout.mm.show', array_merge(['type' => $type], array_filter($params, fn ($v) => $v !== null && $v !== '')));
    }

    public function provider(string $type): PayableProvider
    {
        return app(config("checkout.types.{$type}.provider"));
    }

    public function operators(): array
    {
        return config('checkout.operators', []);
    }

    public function windowSeconds(): int
    {
        return (int) config('checkout.window_seconds', 180);
    }

    // ─── Paiement ──────────────────────────────────────────────────────────

    public function start(PayableProvider $provider, Payable $payable, ?User $user, string $operator, string $phone): MobileMoneyCheckout
    {
        // Une seule tentative en cours par utilisateur et par article : même opérateur et
        // même numéro dans la fenêtre de validation → on réaffiche la tentative en cours.
        $active = $this->activeAttempt($payable, $user);

        if ($active) {
            if ($active->operator === $operator && $active->phone === $phone) {
                return $active;
            }
            // Changement de numéro / d'opérateur : la tentative précédente est remplacée
            // (si elle est quand même validée sur le téléphone, le webhook la créditera).
            $active->update(['status' => 'replaced']);
        }

        $reference = $provider->createPending($payable, $user);

        $attempt = MobileMoneyCheckout::create([
            'user_id'   => $user?->id,
            'type'      => $payable->type,
            'item_key'  => $payable->itemKey,
            'reference' => $reference,
            'amount'    => $payable->amount,
            'operator'  => $operator,
            'phone'     => $phone,
            'status'    => 'pending',
            'meta'      => array_merge($payable->snapshot(), ['email' => $payable->email]),
        ]);

        $res = $this->paystack->charge([
            'email'        => $payable->email,
            'amount'       => $payable->amount,
            'reference'    => $reference,
            'metadata'     => $provider->metadata($payable, $user),
            'mobile_money' => [
                'phone'    => config('checkout.phone_prefix', '+225') . $phone,
                'provider' => $operator,
            ],
        ]);

        $this->logRawOnce($operator, $reference, $res);

        // Échec de création du charge (numéro refusé, opérateur indisponible…) : rien n'a été débité
        if (!$res['ok'] && empty($res['body']['data']['status'])) {
            $attempt->update([
                'status'          => 'failed',
                'paystack_status' => 'charge_error',
                'not_debited'     => $res['http'] >= 400 && $res['http'] < 500,
                'message'         => (string) ($res['body']['message'] ?? 'Paystack indisponible'),
            ]);

            return $attempt;
        }

        $this->applyChargeData($provider, $attempt, (array) ($res['body']['data'] ?? []), (string) ($res['body']['message'] ?? ''));

        return $attempt->fresh();
    }

    public function submitOtp(MobileMoneyCheckout $attempt, string $otp): MobileMoneyCheckout
    {
        $res = $this->paystack->submitOtp($otp, $attempt->reference);
        $this->logRawOnce($attempt->operator . '_otp', $attempt->reference, $res);

        $data = (array) ($res['body']['data'] ?? []);

        if (!$res['ok'] && empty($data['status'])) {
            // Code erroné : on reste sur la saisie OTP
            $attempt->update(['message' => (string) ($res['body']['message'] ?? 'Code invalide, réessaie.')]);
            return $attempt->fresh();
        }

        $this->applyChargeData($this->provider($attempt->type), $attempt, $data, (string) ($res['body']['message'] ?? ''));

        return $attempt->fresh();
    }

    /**
     * Polling : la base (mise à jour par le webhook) d'abord, Paystack en secours.
     */
    public function refresh(MobileMoneyCheckout $attempt): MobileMoneyCheckout
    {
        if (in_array($attempt->status, ['success', 'replaced'], true)) {
            return $attempt;
        }

        $provider = $this->provider($attempt->type);

        if ($provider->isPaid($attempt->reference)) {
            return $this->markSuccess($attempt);
        }

        if ($attempt->status === 'failed') {
            return $attempt;
        }

        $age = $attempt->ageInSeconds();

        $dueForCheck = $age >= self::FIRST_CHECK_AFTER
            && (!$attempt->last_checked_at || $attempt->last_checked_at->lte(now()->subSeconds(self::CHECK_EVERY)));

        if ($dueForCheck) {
            $attempt->update(['last_checked_at' => now()]);

            if ($age < $this->windowSeconds()) {
                $res  = $this->paystack->checkCharge($attempt->reference);
                $data = (array) ($res['body']['data'] ?? []);
            } else {
                $res  = $this->paystack->fetchTransaction($attempt->reference);
                $data = (array) ($res['data'] ?? []);
            }

            $status = (string) ($data['status'] ?? '');

            if ($status === 'success') {
                $provider->validate($data);
                if ($provider->isPaid($attempt->reference)) {
                    return $this->markSuccess($attempt);
                }
                // Payé chez Paystack mais refusé chez nous (montant/devise) : à traiter à la main
                Log::error('MobileMoneyCheckout: paiement success non validé', ['reference' => $attempt->reference]);
                $attempt->update(['status' => 'failed', 'paystack_status' => 'success', 'not_debited' => false]);
                return $attempt->fresh();
            }

            if (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
                $attempt->update([
                    'status'          => 'failed',
                    'paystack_status' => $status,
                    'not_debited'     => true,
                    'message'         => (string) ($data['gateway_response'] ?? $data['message'] ?? ''),
                ]);
                return $attempt->fresh();
            }

            if ($status !== '') {
                $attempt->update(['paystack_status' => $status]);
            }
        }

        // Fenêtre écoulée sans confirmation : expiré (on continue de vérifier si on nous rappelle,
        // un paiement validé en retard passera quand même en succès via le webhook)
        if ($attempt->isWaiting() && $age >= $this->windowSeconds()) {
            $attempt->update(['status' => 'expired']);
        }

        return $attempt->fresh();
    }

    /** Données publiques d'une tentative pour le front (écrans attente / succès / échec). */
    public function present(MobileMoneyCheckout $attempt): array
    {
        $operators = $this->operators();
        $meta      = (array) $attempt->meta;

        return [
            'reference'      => $attempt->reference,
            'status'         => $attempt->status,
            'operator'       => $attempt->operator,
            'operator_label' => $operators[$attempt->operator]['label'] ?? $attempt->operator,
            'phone'          => $attempt->phone,
            'amount'         => $attempt->amount,
            'display_text'   => $attempt->display_text,
            'action_url'     => $attempt->action_url,
            'qr_code'        => $attempt->qr_code,
            'needs_otp'      => $attempt->status === 'send_otp',
            'message'        => $attempt->message,
            'not_debited'    => (bool) $attempt->not_debited,
            'seconds_left'   => max(0, $this->windowSeconds() - $attempt->ageInSeconds()),
            'title'          => $meta['title'] ?? '',
            'image'          => $meta['image'] ?? null,
            'icon'           => $meta['icon'] ?? '🛒',
            'success_url'    => $meta['success_url'] ?? url('/'),
            'success_label'  => $meta['success_label'] ?? 'Continuer',
            'success_title'  => $meta['success_title'] ?? "C'est payé !",
            'note'           => $meta['note'] ?? null,
            'offer_activation' => (bool) ($meta['offer_activation'] ?? true),
        ];
    }

    // ─── Interne ───────────────────────────────────────────────────────────

    private function activeAttempt(Payable $payable, ?User $user): ?MobileMoneyCheckout
    {
        $query = MobileMoneyCheckout::where('type', $payable->type)
            ->where('item_key', $payable->itemKey)
            ->whereIn('status', MobileMoneyCheckout::WAITING)
            ->where('created_at', '>=', now()->subSeconds($this->windowSeconds()))
            ->latest('id');

        // Invité (dons) : la tentative est rattachée à l'email du donateur
        $user
            ? $query->where('user_id', $user->id)
            : $query->whereNull('user_id')->where('meta->email', $payable->email);

        return $query->first();
    }

    private function applyChargeData(PayableProvider $provider, MobileMoneyCheckout $attempt, array $data, string $message): void
    {
        $status = (string) ($data['status'] ?? '');

        $fields = [
            'paystack_status' => $status ?: null,
            'display_text'    => $data['display_text'] ?? $attempt->display_text,
            // Wave peut renvoyer un lien ou un QR à afficher
            'action_url'      => $data['url'] ?? $data['redirect_url'] ?? $data['authorization_url'] ?? $attempt->action_url,
            'qr_code'         => $data['qr_code'] ?? $data['qr'] ?? $attempt->qr_code,
            'message'         => $message ?: $attempt->message,
        ];

        switch ($status) {
            case 'success':
                $attempt->update($fields);
                $provider->validate(array_merge($data, ['reference' => $attempt->reference]));
                if ($provider->isPaid($attempt->reference)) {
                    $this->markSuccess($attempt);
                }
                return;

            case 'failed':
                $attempt->update(array_merge($fields, [
                    'status'      => 'failed',
                    'not_debited' => true,
                    'message'     => (string) ($data['gateway_response'] ?? $data['message'] ?? $message),
                ]));
                return;

            case 'send_otp':
                $attempt->update($fields + ['status' => 'send_otp']);
                return;

            default:
                // pay_offline, pending, open_url… : validation sur le téléphone
                $attempt->update($fields + ['status' => 'pending']);
        }
    }

    private function markSuccess(MobileMoneyCheckout $attempt): MobileMoneyCheckout
    {
        $attempt->update([
            'status'          => 'success',
            'paystack_status' => 'success',
            'paid_at'         => $attempt->paid_at ?? now(),
        ]);

        return $attempt->fresh();
    }

    /** Réponse brute loguée la 1re fois pour chaque opérateur (formats Wave/Orange/MTN à observer). */
    private function logRawOnce(string $key, string $reference, array $res): void
    {
        if (Cache::add('mm_checkout_raw_logged:' . $key, true, now()->addYear())) {
            Log::info('MobileMoneyCheckout: réponse Paystack brute (' . $key . ')', [
                'reference' => $reference,
                'http'      => $res['http'],
                'body'      => $res['body'],
            ]);
        }
    }
}
