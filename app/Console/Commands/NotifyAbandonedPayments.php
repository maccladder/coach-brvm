<?php

namespace App\Console\Commands;

use App\Mail\AbandonedPaymentMail;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotifyAbandonedPayments extends Command
{
    protected $signature = 'payments:notify-abandoned';
    protected $description = 'Envoie un email aux utilisateurs dont le paiement est resté PENDING plus de 15 minutes sans aboutir';

    public function handle(): int
    {
        $limit = now()->subMinutes(15);
        $sent  = 0;

        Payment::where('status', 'PENDING')
            ->whereNull('credited_at')
            ->whereNull('abandoned_notified_at')
            ->where('created_at', '<=', $limit)
            ->with('user')
            ->chunkById(100, function ($payments) use (&$sent) {
                foreach ($payments as $payment) {
                    if (!$payment->user || !$payment->user->email) {
                        Log::warning('NotifyAbandonedPayments: user ou email manquant, skip', [
                            'payment_id' => $payment->id,
                        ]);
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

        $this->info("Emails abandonnés envoyés : {$sent}");

        return Command::SUCCESS;
    }
}
