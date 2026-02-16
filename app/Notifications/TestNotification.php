<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Test notification ✅',
            'message' => 'Si tu vois ça, le système marche.',
            'action_url' => url('/notifications'),
        ];
    }
}
