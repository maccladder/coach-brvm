<?php

namespace App\Mail;

use App\Models\AffiliateCommission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AffiliateCommissionEarnedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AffiliateCommission $commission) {}

    public function build()
    {
        return $this
            ->subject('💰 Vous avez gagné une commission – Boursiv')
            ->view('emails.affiliate.commission_earned');
    }
}
