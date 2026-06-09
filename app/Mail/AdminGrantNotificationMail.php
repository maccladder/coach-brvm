<?php

namespace App\Mail;

use App\Models\AdminGrant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminGrantNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AdminGrant $grant,
        public string $itemName,
        public string $itemUrl,
    ) {}

    public function build()
    {
        return $this
            ->subject('🎁 Un cadeau de l\'équipe Boursiv pour vous')
            ->view('emails.admin_grant_notification')
            ->text('emails.admin_grant_notification_text');
    }
}
