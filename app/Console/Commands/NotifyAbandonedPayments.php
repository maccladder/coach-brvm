<?php

namespace App\Console\Commands;

use App\Mail\AbandonedPaymentMail;
use App\Models\CoursePurchase;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAbandonedPayments extends Command
{
    protected $signature = 'payments:notify-abandoned';
    protected $description = 'Envoie un email aux utilisateurs dont le paiement est resté en attente (entre 15 min et 48 h) sans aboutir';

    private const MIN_AGE_MINUTES = 15;
    private const MAX_AGE_HOURS   = 48;

    public function handle(): int
    {
        $sent    = 0;
        $skipped = 0;

        // Comparaison insensible à la casse : le marketplace écrit "pending" en minuscules
        // (SQLite compare en tenant compte de la casse). "abandoned" = marqué par
        // payments:reconcile-paystack d'après le statut Paystack.
        Payment::whereRaw('LOWER(status) IN (?, ?)', ['pending', 'abandoned'])
            ->whereNull('credited_at')
            ->whereNull('abandoned_notified_at')
            ->where('created_at', '<=', now()->subMinutes(self::MIN_AGE_MINUTES))
            ->where('created_at', '>=', now()->subHours(self::MAX_AGE_HOURS))
            ->with('user')
            ->chunkById(100, function ($payments) use (&$sent, &$skipped) {
                foreach ($payments as $payment) {
                    if (!$payment->user || !$payment->user->email) {
                        Log::warning('NotifyAbandonedPayments: user ou email manquant, skip', [
                            'payment_id' => $payment->id,
                        ]);
                        continue;
                    }

                    if ($this->alreadyPaid($payment) || $this->recentlyNotified($payment)) {
                        $skipped++;
                        continue;
                    }

                    try {
                        Mail::to($payment->user->email)->send(new AbandonedPaymentMail($payment));

                        $payment->abandoned_notified_at = now();
                        $payment->save();

                        $sent++;
                    } catch (\Throwable $e) {
                        Log::error('NotifyAbandonedPayments: échec envoi', [
                            'payment_id' => $payment->id,
                            'email'      => $payment->user->email,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('NotifyAbandonedPayments: relances envoyées', ['sent' => $sent, 'skipped' => $skipped]);
        $this->info("Emails abandonnés envoyés : {$sent} (ignorés : {$skipped})");

        return Command::SUCCESS;
    }

    /**
     * Paiements du même utilisateur pour le même produit / cours / pack.
     */
    private function sameItem(Payment $payment)
    {
        $query = Payment::where('user_id', $payment->user_id)
            ->where('purpose', $payment->purpose)
            ->where('id', '!=', $payment->id);

        $key = match ($payment->purpose) {
            'marketplace' => 'product_id',
            'course'      => 'course_id',
            default       => null, // pack_brvm, wallet_topup : un seul "produit"
        };

        if ($key) {
            $query->where("meta->{$key}", (int) data_get($payment->meta, $key));
        }

        return $query;
    }

    /**
     * L'utilisateur a-t-il payé depuis (autre tentative réussie) ou possède-t-il déjà l'article ?
     */
    private function alreadyPaid(Payment $payment): bool
    {
        if ($this->sameItem($payment)
            ->whereNotNull('credited_at')
            ->where('created_at', '>=', $payment->created_at)
            ->exists()) {
            return true;
        }

        if ($payment->purpose === 'marketplace') {
            $productId = (int) data_get($payment->meta, 'product_id');

            return $productId && DB::table('marketplace_purchases')
                ->where('user_id', $payment->user_id)
                ->where('product_id', $productId)
                ->where('status', 'paid')
                ->exists();
        }

        if ($payment->purpose === 'course') {
            $courseId = (int) data_get($payment->meta, 'course_id');

            return $courseId && CoursePurchase::where('user_id', $payment->user_id)
                ->where('course_id', $courseId)
                ->whereNotNull('paid_at')
                ->exists();
        }

        return false;
    }

    private function recentlyNotified(Payment $payment): bool
    {
        return $this->sameItem($payment)
            ->where('abandoned_notified_at', '>=', now()->subHours(self::MAX_AGE_HOURS))
            ->exists();
    }
}
