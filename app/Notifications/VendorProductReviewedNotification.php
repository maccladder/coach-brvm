<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class VendorProductReviewedNotification extends Notification
{
    public function __construct(
        public int $productId,
        public string $productTitle,
        public string $status,   // approved|rejected
        public string $message,
        public string $url
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $title = $this->status === 'approved'
            ? '✅ Produit approuvé'
            : '⛔ Produit rejeté';

        return [
            'type'          => 'vendor_product_reviewed',
            'title'         => $title,
            'message'       => $this->message,
            'url'           => $this->url,
            'product_id'    => $this->productId,
            'product_title' => $this->productTitle,
            'status'        => $this->status,
        ];
    }
}
