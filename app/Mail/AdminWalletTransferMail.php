<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminWalletTransferMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $direction  'received' | 'sent'
     */
    public function __construct(
        public User $user,
        public User $counterpart,
        public int $amount,
        public float $newBalance,
        public string $direction,
        public ?string $motif = null,
    ) {}

    public function build(): static
    {
        $subject = $this->direction === 'received'
            ? '💸 Vous avez reçu un transfert sur Boursiv'
            : '💸 Transfert de solde effectué – Boursiv';

        return $this
            ->subject($subject)
            ->view('emails.admin_wallet_transfer');
    }
}
