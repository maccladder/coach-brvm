<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WalletBonusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function build(): static
    {
        $amount = number_format(config('otp.wallet_bonus_amount'), 0, ',', ' ');

        return $this
            ->subject("🎁 Tu as reçu {$amount} FCFA virtuels — Coach BRVM")
            ->view('emails.wallet-bonus');
    }
}
