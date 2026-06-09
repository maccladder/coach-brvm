<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AffiliateRequestAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User   $applicant,
        public string $code
    ) {}

    public function build()
    {
        return $this
            ->subject('🤝 Nouvelle demande d\'apporteur – Boursiv')
            ->view('emails.affiliate.request_admin');
    }
}
