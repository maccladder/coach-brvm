<?php

namespace App\Mail;

use App\Models\AffiliateCommission;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AffiliateCommissionAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public AffiliateCommission $commission) {}

    public function build()
    {
        return $this
            ->subject('🤝 Vente affiliée – Boursiv')
            ->view('emails.affiliate.commission_admin');
    }
}
