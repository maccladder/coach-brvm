<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserNewMarketplaceContentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $productId,
        public string $productTitle,
        public string $url
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'          => 'user_new_marketplace_content',
            'title'         => '🆕 Nouveau contenu sur la marketplace',
            'message'       => "Nouveau : « {$this->productTitle} » est disponible.",
            'url'           => $this->url,
            'product_id'    => $this->productId,
            'product_title' => $this->productTitle,
        ];
    }
}
