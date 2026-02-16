<?php

namespace App\Support;

use App\Models\AdminNotification;

class AdminNotify
{
    public static function push(string $type, string $title, ?string $message = null, ?string $url = null): void
    {
        AdminNotification::create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
        ]);
    }
}
