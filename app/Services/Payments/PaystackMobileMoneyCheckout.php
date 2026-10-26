<?php

namespace App\Services\Payments;

use App\Checkout\Payable;
use App\Checkout\PayableProvider;
use App\Models\MobileMoneyCheckout;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Checkout Mobile Money personnalisé (Paystack /charge), commun à tous les types de paiement.
 *
 * Cycle : start() crée l'enregistrement métier en attente (via le PayableProvider du type)
 * puis le charge Paystack ; l'utilisateur valide sur son téléphone (ou saisit un code de
 * paiement, send_otp) ; le webhook charge.success valide le paiement en base ; refresh()
 * (polling) lit la base et, en secours, interroge Paystack (GET /charge après 10 s,
 * transaction/verify après 180 s).
 */
class PaystackMobileMoneyCheckout
{
    public const FIRST_CHECK_AFTER = 10;  // s avant le premier GET /charge
    public const CHECK_EVERY       = 8;   // s entre deux appels Paystack pendant le polling

    /**
     * Explication affichée sur l'écran "send_otp" (code de paiement à générer), par opérateur.
     * Texte libre, à adapter si les opérateurs changent leur parcours.
     */
    public const OTP_HELP = [
        'orange'  => "Ouvre l'application Orange Money (ou compose le #144# depuis ton téléphone), "
                   . "génère un code de paiement temporaire, puis saisis-le ici pour valider le paiement.",
        'default' => "Génère un code de paiement temporaire depuis l'application ou le menu Mobile Money "
                   . "de ton téléphone, puis saisis-le ici pour valider le paiement.",
    ];

    /** Textes Paystack (anglais) connus → français. Les inconnus sont affichés tels quels, en petit. */
    public const DISPLAY_TEXTS = [
        'please complete authorization process on your mobile application' => "Termine l'autorisation du paiement dans ton application Mobile Money.",
        'please complete authorization on your mobile phone'               => 'Valide le paiement sur ton téléphone.',
        'please complete authorization on your phone'                      => 'Valide le paiement sur ton téléphone.',
        'please complete authorization process on your mobile phone'       => 'Valide le paiement sur ton téléphone.',
        'please confirm the payment on your phone'                         => 'Confirme le paiement sur ton téléphone.',
        'please enter otp'                                                 => 'Saisis le code reçu.',
        'please enter the otp sent to your phone'                          => 'Saisis le code reçu sur ton téléphone.',
        'insufficient funds'                                               => 'Solde insuffisant.',
        'invalid otp'                                                      => 'Code de paiement invalide.',
        'declined'                                                         => 'Paiement refusé par l’opérateur.',
        'transaction declined'                                             => 'Paiement refusé par l’opérateur.',
        'timeout'                                                          => 'Délai de validation dépassé.',
    ];

    public const WAVE_UNAVAILABLE = 'Wave est momentanément indisponible, choisis Orange Money ou MTN MoMo.';
    public const START_FAILED     = "Le paiement n'a pas pu démarrer. Réessaie ou choisis un autre opérateur.";
    public const OTP_REJECTED     = 'Code refusé. Vérifie-le ou génère un nouveau code de paiement.';

    private bool $warnedSimulation = false;

    public function __construct(
        private PaystackService $paystack,
        private MobileMoneySimulator $simulator,
    ) {}

    // ─── Configuration / activation ────────────────────────────────────────

    public function exists(string $type): bool
    {
        return (bool) config("checkout.types.{$type}");
    }

    public function isEnabled(string $type): bool
    {
        return $this->exists($type) && (bool) config("checkout.types.{$type}.enabled");
    }

    /**
     * Test en prod réservé : si CHECKOUT_MM_ALLOWED_EMAILS est rempli, seuls ces comptes
     * connectés utilisent le checkout ; tous les autres gardent l'ancien flux.
     */
    public function isAllowed(?User $user): bool
    {
        $allowed = $this->allowedEmails();

        return $allowed === [] || ($user && in_array(strtolower((string) $user->email), $allowed, true));
    }

    public function allowedEmails(): array
    {
        $raw = config('checkout.allowed_emails', []);
        $raw = is_array($raw) ? $raw : explode(',', (string) $raw);

        return array_values(array_filter(array_map(fn ($e) => strtolower(trim((string) $e)), $raw)));
    }

    public function isEnabledFor(string $type, ?User $user): bool
    {
        return $this->isEnabled($type) && $this->isAllowed($user);
    }

    /** L'ancien flux doit-il rediriger vers le checkout ? (?classic=1 force l'ancien flux) */
    public function shouldIntercept(string $type, Request $request): bool
    {
        return $this->isEnabledFor($type, $request->user()) && !$request->boolean('classic');
    }

    public function entryUrl(string $type, array $params = []): string
    {
        return route('checkout.mm.show', array_merge(['type' => $type], array_filter($params, fn ($v) => $v !== null && $v !== '')));
    }

    public function provider(string $type): PayableProvider
    {
        return app(config("checkout.types.{$type}.provider"));
    }

    /** Opérateurs proposés (Wave masqué tant que CHECKOUT_MM_WAVE_ENABLED=false) */
    public function operators(): array
    {
        $operators = config('checkout.operators', []);

        if (!config('checkout.wave_enabled')) {
            unset($operators['wave']);
        }

        return $operators;
    }

    public function windowSeconds(): int
    {
        return (int) config('checkout.window_seconds', 180);
    }

    /** Simulation locale (aucun appel Paystack) — jamais en production. */
    public function simulating(): bool
    {
        if (!config('checkout.simulate')) {
            return false;
        }

        if (app()->environment('production')) {
            if (!$this->warnedSimulation) {
                Log::error('CHECKOUT_MM_SIMULATE ignoré : interdit en production');
                $this->warnedSimulation = true;
            }
            return false;
        }

        return true;
    }

    /** @return PaystackService|MobileMoneySimulator */
    private function gateway(): object
    {
        return $this->simulating() ? $this->simulator : $this->paystack;
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
            'meta'      => array_merge($payable->snapshot(), [
                'email'     => $payable->email,
                'simulated' => $this->simulating(),
            ]),
        ]);

        $res = $this->gateway()->charge([
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

        // Erreur API (HTTP ≠ 2xx ou status=false), même avec data.status="failed" : le charge
        // n'a pas été traité par l'opérateur → "n'a pas pu démarrer", sans affirmer quoi que
        // ce soit sur le débit.
        if (!$res['ok']) {
            $code = (string) ($res['body']['code'] ?? '');

            $attempt->update([
                'status'          => 'failed',
                'paystack_status' => 'api_error',
                'not_debited'     => false,
                'message'         => $operator === 'wave' && $code === 'unprocessed_transaction'
                    ? self::WAVE_UNAVAILABLE
                    : self::START_FAILED,
                'meta'            => array_merge((array) $attempt->meta, ['paystack_error' => [
                    'http' => $res['http'],
                    'code' => $code ?: null,
                    'type' => $res['body']['type'] ?? null,
                ]]),
            ]);

            return $attempt->fresh();
        }

        $this->applyChargeData($provider, $attempt, (array) ($res['body']['data'] ?? []));

        return $attempt->fresh();
    }

    public function submitOtp(MobileMoneyCheckout $attempt, string $otp): MobileMoneyCheckout
    {
        $res = $this->gateway()->submitOtp($otp, $attempt->reference);
        $this->logRawOnce($attempt->operator . '_otp', $attempt->reference, $res);

        $data = (array) ($res['body']['data'] ?? []);

        if (!$res['ok'] && empty($data['status'])) {
            // Code refusé par l'API : on reste sur la saisie du code
            $attempt->update(['message' => self::OTP_REJECTED]);
            return $attempt->fresh();
        }

        // Le délai de validation repart de la saisie du code
        $attempt->update([
            'message' => null,
            'meta'    => array_merge((array) $attempt->meta, ['window_started_at' => now()->toIso8601String()]),
        ]);

        $this->applyChargeData($this->provider($attempt->type), $attempt->fresh(), $data);

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

        // Échec définitif, ou code de paiement pas encore saisi (pas de délai qui court)
        if (in_array($attempt->status, ['failed', 'send_otp'], true)) {
            return $attempt;
        }

        $age = $this->windowAge($attempt);

        $dueForCheck = $age >= self::FIRST_CHECK_AFTER
            && (!$attempt->last_checked_at || $attempt->last_checked_at->lte(now()->subSeconds(self::CHECK_EVERY)));

        if ($dueForCheck) {
            $attempt->update(['last_checked_at' => now()]);

            if ($age < $this->windowSeconds()) {
                $res  = $this->gateway()->checkCharge($attempt->reference);
                $data = (array) ($res['body']['data'] ?? []);
            } else {
                $res  = $this->gateway()->fetchTransaction($attempt->reference);
                $data = (array) ($res['data'] ?? []);
            }

            $status = (string) ($data['status'] ?? '');

            if ($status === 'success') {
                $provider->validate(array_merge($data, ['reference' => $attempt->reference]));
                if ($provider->isPaid($attempt->reference)) {
                    return $this->markSuccess($attempt);
                }
                // Payé chez Paystack mais refusé chez nous (montant/devise) : à traiter à la main
                Log::error('MobileMoneyCheckout: paiement success non validé', ['reference' => $attempt->reference]);
                $attempt->update(['status' => 'failed', 'paystack_status' => 'success', 'not_debited' => false]);
                return $attempt->fresh();
            }

            // Transaction réellement traitée et confirmée en échec par Paystack
            if (in_array($status, ['failed', 'abandoned', 'reversed'], true)) {
                $attempt->update([
                    'status'          => 'failed',
                    'paystack_status' => $status,
                    'not_debited'     => true,
                    'message'         => $this->failureReason($data),
                ]);
                return $attempt->fresh();
            }

            if ($status !== '') {
                $attempt->update(['paystack_status' => $status]);
            }
        }

        // Fenêtre écoulée sans confirmation : expiré (on continue de vérifier si on nous rappelle,
        // un paiement validé en retard passera quand même en succès via le webhook)
        if ($attempt->status === 'pending' && $age >= $this->windowSeconds()) {
            $attempt->update(['status' => 'expired']);
        }

        return $attempt->fresh();
    }

    /** Données publiques d'une tentative pour le front (écrans code / attente / succès / échec). */
    public function present(MobileMoneyCheckout $attempt): array
    {
        $operators = config('checkout.operators', []);
        $meta      = (array) $attempt->meta;
        [$displayText, $displayRaw] = $this->translate($attempt->display_text);

        return [
            'reference'        => $attempt->reference,
            'status'           => $attempt->status,
            'operator'         => $attempt->operator,
            'operator_label'   => $operators[$attempt->operator]['label'] ?? $attempt->operator,
            'phone'            => $attempt->phone,
            'amount'           => $attempt->amount,
            'display_text'     => $displayText,
            'display_text_raw' => $displayRaw,
            'action_url'       => $this->validUrl($attempt->action_url),
            'qr_code'          => $this->validImage($attempt->qr_code),
            'needs_otp'        => $attempt->status === 'send_otp',
            'otp_help'         => self::OTP_HELP[$attempt->operator] ?? self::OTP_HELP['default'],
            'message'          => $attempt->message,
            'start_failed'     => $attempt->paystack_status === 'api_error',
            'not_debited'      => (bool) $attempt->not_debited,
            'seconds_left'     => max(0, $this->windowSeconds() - $this->windowAge($attempt)),
            'title'            => $meta['title'] ?? '',
            'image'            => $meta['image'] ?? null,
            'icon'             => $meta['icon'] ?? '🛒',
            'success_url'      => $meta['success_url'] ?? url('/'),
            'success_label'    => $meta['success_label'] ?? 'Continuer',
            'success_title'    => $meta['success_title'] ?? "C'est payé !",
            'note'             => $meta['note'] ?? null,
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

    /** Secondes écoulées depuis le début du délai de validation (création, ou saisie du code). */
    private function windowAge(MobileMoneyCheckout $attempt): int
    {
        $start = data_get($attempt->meta, 'window_started_at');
        $from  = $start ? Carbon::parse($start) : $attempt->created_at;

        return (int) $from->diffInSeconds(now(), true);
    }

    private function applyChargeData(PayableProvider $provider, MobileMoneyCheckout $attempt, array $data): void
    {
        $status = (string) ($data['status'] ?? '');

        // Jamais le "message" de premier niveau ("Charge attempted") : seulement data.*
        $fields = [
            'paystack_status' => $status ?: null,
            'display_text'    => $data['display_text'] ?? $attempt->display_text,
            // Wave peut renvoyer un lien ou un QR à afficher
            'action_url'      => $data['url'] ?? $data['redirect_url'] ?? $data['authorization_url'] ?? $attempt->action_url,
            'qr_code'         => $data['qr_code'] ?? $data['qr'] ?? $attempt->qr_code,
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
                // Charge accepté par l'API puis refusé par l'opérateur : rien n'a été débité
                $attempt->update(array_merge($fields, [
                    'status'      => 'failed',
                    'not_debited' => true,
                    'message'     => $this->failureReason($data),
                ]));
                return;

            case 'send_otp':
                $attempt->update(array_merge($fields, ['status' => 'send_otp']));
                return;

            default:
                // pay_offline, pending, open_url… : validation sur le téléphone
                $attempt->update(array_merge($fields, ['status' => 'pending']));
        }
    }

    private function failureReason(array $data): ?string
    {
        $reason = (string) ($data['gateway_response'] ?? '');
        if ($reason === '') {
            return null;
        }

        [$fr, $raw] = $this->translate($reason);

        return $fr ?? $raw;
    }

    /** @return array{0: ?string, 1: ?string} [texte français connu, texte original si inconnu] */
    private function translate(?string $text): array
    {
        $text = trim((string) $text);
        if ($text === '') {
            return [null, null];
        }

        $key = strtolower(rtrim($text, " .!"));

        return isset(self::DISPLAY_TEXTS[$key]) ? [self::DISPLAY_TEXTS[$key], null] : [null, $text];
    }

    private function validUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $url) ? $url : null;
    }

    private function validImage(?string $src): ?string
    {
        $src = trim((string) $src);

        if (preg_match('#^data:image/(png|jpe?g|gif|webp|svg\+xml);base64,[A-Za-z0-9+/=\s]+$#', $src)) {
            return $src;
        }

        return $this->validUrl($src);
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
        if ($this->simulating()) {
            return;
        }

        if (Cache::add('mm_checkout_raw_logged:' . $key, true, now()->addYear())) {
            Log::info('MobileMoneyCheckout: réponse Paystack brute (' . $key . ')', [
                'reference' => $reference,
                'http'      => $res['http'],
                'body'      => $res['body'],
            ]);
        }
    }
}
