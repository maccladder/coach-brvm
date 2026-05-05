<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $htmlContent,
        public string $recipientName,
    ) {}

    public function build()
    {
        return $this
            ->subject($this->emailSubject)
            ->view('emails.admin_broadcast')
            ->text('emails.admin_broadcast_text');
    }
}
