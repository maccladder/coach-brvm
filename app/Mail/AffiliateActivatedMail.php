<?php

namespace App\Mail;

use App\Models\Affiliate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AffiliateActivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Affiliate $affiliate) {}

    public function build()
    {
        return $this
            ->subject('🎉 Votre compte apporteur est actif – Boursiv')
            ->view('emails.affiliate.activated');
    }
}
