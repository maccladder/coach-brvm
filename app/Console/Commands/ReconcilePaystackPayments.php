<?php

namespace App\Console\Commands;

use App\Models\CaurisAchat;
use App\Models\DocumentPurchase;
use App\Models\Payment;
use App\Services\CaurisService;
use App\Services\Payments\PaystackCourseService;
use App\Services\Payments\PaystackMarketplaceService;
use App\Services\Payments\PaystackPackService;
use App\Services\Payments\PaystackWalletTopupService;
use App\Services\PaystackService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Rattrape les paiements restés "en attente" chez nous en interrogeant Paystack
 * (transaction/verify) : valide ceux qui sont "success" (même logique idempotente
 * que le webhook) et marque ceux qui sont "failed"/"abandoned".
 */
class ReconcilePaystackPayments extends Command
{
    protected $signature = 'payments:reconcile-paystack
                            {--days=60 : Ancienneté maximale des paiements à vérifier}
                            {--dry-run : Affiche ce qui serait fait, sans rien modifier}';

    protected $description = 'Vérifie chez Paystack les paiements en attente et les valide / marque échoués';

    // ~4 appels API par seconde (Paystack tolère bien plus, on reste prudent)
    private const API_DELAY_US = 250_000;

    // Un paiement plus récent peut être en cours chez Paystack ("abandoned" tant que
    // l'utilisateur n'a pas fini) : on ne le touche pas.
    private const MIN_AGE_MINUTES = 30;

    public function handle(PaystackService $paystack): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $days   = max(1, (int) $this->option('days'));

        $candidates = $this->collectCandidates(now()->subDays($days), now()->subMinutes(self::MIN_AGE_MINUTES));

        $this->info(sprintf(
            '%d paiement(s) en attente sur %d jour(s)%s',
            count($candidates),
            $days,
            $dryRun ? ' — DRY RUN, aucune modification' : ''
        ));

        if (count($candidates) === 0) {
            return Command::SUCCESS;
        }

        $rows   = [];
        $counts = [];

        foreach ($candidates as $i => $c) {
            if ($i > 0) {
                usleep(self::API_DELAY_US);
            }

            $res = $paystack->fetchTransaction($c['reference']);

            if ($res['error']) {
                $psStatus = 'erreur API';
            } elseif (!$res['found']) {
                $psStatus = 'introuvable';
            } else {
                $psStatus = (string) ($res['data']['status'] ?? '?');
            }

            $action = $this->plannedAction($c, $psStatus);

            if (!$dryRun && $action !== 'aucune') {
                $action .= ' → ' . $this->apply($c, $psStatus, (array) $res['data']);
            }

            $counts[$psStatus] = ($counts[$psStatus] ?? 0) + 1;

            $rows[] = [
                $c['reference'],
                $c['type'],
                $c['email'] ?? '-',
                number_format((int) $c['amount'], 0, ',', ' '),
                $psStatus . ($res['error'] ? ' (' . mb_substr($res['error'], 0, 40) . ')' : ''),
                $action,
            ];
        }

        $this->table(['référence', 'type', 'email', 'montant', 'statut Paystack', 'action prévue'], $rows);

        ksort($counts);
        $this->line('Statuts Paystack : ' . collect($counts)->map(fn ($n, $s) => "{$s}={$n}")->implode(', '));

        if ($dryRun) {
            $this->comment('Relance sans --dry-run pour appliquer.');
        } else {
            Log::info('payments:reconcile-paystack terminé', ['days' => $days, 'statuts' => $counts]);
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, array{source: string, id: int, reference: string, type: string, email: ?string, amount: int, purpose: ?string}>
     */
    private function collectCandidates($since, $until): array
    {
        $out = [];
        $refs = [];

        // 1) payments (formations, packs, marketplace, wallet) — "PENDING" ou "pending"
        Payment::query()
            ->whereRaw('LOWER(status) = ?', ['pending'])
            ->whereNull('credited_at')
            ->whereBetween('created_at', [$since, $until])
            ->with('user:id,email')
            ->orderBy('id')
            ->get()
            ->each(function (Payment $p) use (&$out, &$refs) {
                // CinetPay : rien à demander à Paystack
                if (data_get($p->meta, 'provider') === 'cinetpay') return;

                $refs[$p->transaction_id] = true;
                $out[] = [
                    'source'    => 'payments',
                    'id'        => $p->id,
                    'reference' => $p->transaction_id,
                    'type'      => $p->purpose,
                    'email'     => $p->user?->email,
                    'amount'    => (int) $p->amount_paid,
                    'purpose'   => $p->purpose,
                ];
            });

        // 2) marketplace_purchases Paystack sans ligne payments correspondante
        DB::table('marketplace_purchases as mp')
            ->leftJoin('users as u', 'u.id', '=', 'mp.user_id')
            ->where('mp.status', 'pending')
            ->where('mp.provider', 'paystack')
            ->whereBetween('mp.updated_at', [$since, $until])
            ->select('mp.id', 'mp.provider_ref', 'mp.amount', 'u.email')
            ->orderBy('mp.id')
            ->get()
            ->each(function ($row) use (&$out, &$refs) {
                if (!$row->provider_ref || isset($refs[$row->provider_ref])) return;
                if (Payment::where('transaction_id', $row->provider_ref)->exists()) return;

                $out[] = [
                    'source'    => 'marketplace_purchases',
                    'id'        => $row->id,
                    'reference' => $row->provider_ref,
                    'type'      => 'marketplace (achat seul)',
                    'email'     => $row->email,
                    'amount'    => (int) $row->amount,
                    'purpose'   => null,
                ];
            });

        // 3) cauris_achats
        CaurisAchat::query()
            ->whereRaw('LOWER(status) = ?', ['pending'])
            ->whereNull('credited_at')
            ->whereBetween('created_at', [$since, $until])
            ->orderBy('id')
            ->get()
            ->each(function (CaurisAchat $a) use (&$out) {
                $out[] = [
                    'source'    => 'cauris_achats',
                    'id'        => $a->id,
                    'reference' => $a->reference,
                    'type'      => 'cauris',
                    'email'     => DB::table('users')->where('id', $a->user_id)->value('email'),
                    'amount'    => (int) $a->prix,
                    'purpose'   => null,
                ];
            });

        // 4) document_purchases payés via Paystack (aujourd'hui tous en CinetPay)
        DocumentPurchase::query()
            ->where('status', 'pending')
            ->where('provider', 'paystack')
            ->whereBetween('created_at', [$since, $until])
            ->orderBy('id')
            ->get()
            ->each(function (DocumentPurchase $d) use (&$out) {
                $out[] = [
                    'source'    => 'document_purchases',
                    'id'        => $d->id,
                    'reference' => $d->provider_ref,
                    'type'      => 'document',
                    'email'     => DB::table('users')->where('id', $d->user_id)->value('email'),
                    'amount'    => (int) $d->amount,
                    'purpose'   => null,
                ];
            });

        return $out;
    }

    private function plannedAction(array $c, string $psStatus): string
    {
        if ($psStatus === 'success') {
            return $this->canAutoValidate($c) ? 'valider' : 'à traiter à la main';
        }

        if (in_array($psStatus, ['failed', 'abandoned'], true)) {
            return "marquer {$psStatus}";
        }

        return 'aucune';
    }

    private function canAutoValidate(array $c): bool
    {
        return $c['source'] === 'cauris_achats'
            || ($c['source'] === 'payments' && in_array($c['purpose'], ['marketplace', 'course', 'pack_brvm', 'wallet_topup'], true));
    }

    private function apply(array $c, string $psStatus, array $data): string
    {
        try {
            if ($psStatus === 'success') {
                if (!$this->canAutoValidate($c)) return 'non traité';

                if ($c['source'] === 'cauris_achats') {
                    app(CaurisService::class)->validerPaiementPaystack($data);
                    $ok = CaurisAchat::whereKey($c['id'])->whereNotNull('credited_at')->exists();
                    return $ok ? 'validé' : 'non validé (voir logs)';
                }

                return match ($c['purpose']) {
                    'marketplace'  => app(PaystackMarketplaceService::class)->handleChargeSuccess($data),
                    'course'       => app(PaystackCourseService::class)->handleChargeSuccess($data),
                    'pack_brvm'    => $this->viaCreditedAt($c['id'], fn () => app(PaystackPackService::class)->handleChargeSuccess($data)),
                    'wallet_topup' => $this->viaCreditedAt($c['id'], fn () => app(PaystackWalletTopupService::class)->handleChargeSuccess($data)),
                };
            }

            return $this->markFailed($c, $psStatus);

        } catch (\Throwable $e) {
            Log::error('payments:reconcile-paystack: erreur', [
                'reference' => $c['reference'],
                'error'     => $e->getMessage(),
            ]);
            return 'erreur : ' . mb_substr($e->getMessage(), 0, 60);
        }
    }

    private function viaCreditedAt(int $paymentId, callable $fn): string
    {
        $fn();
        return Payment::whereKey($paymentId)->whereNotNull('credited_at')->exists()
            ? 'validé'
            : 'non validé (voir logs)';
    }

    /**
     * Marque failed/abandoned uniquement si la ligne est toujours en attente.
     * Un paiement marqué ainsi reste validable si Paystack envoie plus tard un
     * charge.success (les services se basent sur credited_at / "paid", pas sur "pending").
     */
    private function markFailed(array $c, string $psStatus): string
    {
        $updated = 0;

        switch ($c['source']) {
            case 'payments':
                // Même casse que le reste de la ligne : minuscules pour le marketplace
                $status = $c['purpose'] === 'marketplace' ? $psStatus : strtoupper($psStatus);

                $updated = Payment::whereKey($c['id'])
                    ->whereRaw('LOWER(status) = ?', ['pending'])
                    ->whereNull('credited_at')
                    ->update(['status' => $status, 'updated_at' => now()]);

                if ($c['purpose'] === 'marketplace') {
                    DB::table('marketplace_purchases')
                        ->where('provider_ref', $c['reference'])
                        ->where('status', 'pending')
                        ->update(['status' => 'failed', 'updated_at' => now()]);
                }
                break;

            case 'marketplace_purchases':
                $updated = DB::table('marketplace_purchases')
                    ->where('id', $c['id'])
                    ->where('status', 'pending')
                    ->update(['status' => 'failed', 'updated_at' => now()]);
                break;

            case 'cauris_achats':
                $updated = CaurisAchat::whereKey($c['id'])
                    ->whereRaw('LOWER(status) = ?', ['pending'])
                    ->whereNull('credited_at')
                    ->update(['status' => strtoupper($psStatus)]);
                break;

            case 'document_purchases':
                $updated = DocumentPurchase::whereKey($c['id'])
                    ->where('status', 'pending')
                    ->update(['status' => 'failed']);
                break;
        }

        return $updated ? 'marqué' : 'déjà modifié';
    }
}
