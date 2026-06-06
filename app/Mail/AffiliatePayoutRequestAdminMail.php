<?php

namespace App\Mail;

use App\Models\AffiliatePayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AffiliatePayoutRequestAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AffiliatePayoutRequest $payout) {}

    public function build()
    {
        return $this
            ->subject('💸 Nouvelle demande de reversement apporteur – Coach BRVM')
            ->view('emails.affiliate.payout_admin');
    }
}
