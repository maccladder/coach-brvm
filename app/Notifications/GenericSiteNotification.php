<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GenericSiteNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $message = '',
        public ?string $url = null
    ) {}

    public function via($notifiable): array
    {
        return ['database']; // + 'mail' plus tard si tu veux
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
        ];
    }
}
