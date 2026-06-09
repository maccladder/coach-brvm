<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminWalletTopupMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $amount,
        public float $newBalance,
        public ?string $motif = null,
    ) {}

    public function build(): static
    {
        return $this
            ->subject('💰 Votre portefeuille Boursiv a été rechargé')
            ->view('emails.admin_wallet_topup');
    }
}
