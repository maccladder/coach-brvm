<?php

namespace App\Mail;

use App\Models\ClientFinancial;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminFinancialPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ClientFinancial $financial) {}

    public function build()
    {
        return $this
            ->subject('💰 Nouveau paiement – États financiers Coach BRVM')
            ->view('emails.financial_payment_received');
    }
}
