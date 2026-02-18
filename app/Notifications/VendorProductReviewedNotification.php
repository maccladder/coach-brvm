<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

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
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $isApproved = $this->status === 'approved';

        $subject = $isApproved
            ? '✅ Produit approuvé — Coach BRVM'
            : '⛔ Produit refusé — Coach BRVM';

        $headline = $isApproved
            ? 'Bonne nouvelle ! Votre produit a été approuvé.'
            : 'Votre produit a été refusé.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . ($notifiable->name ?? ''))
            ->line($headline)
            ->line("Produit : « {$this->productTitle} »")
            ->line($this->message)
            ->action('Voir le produit', $this->url);
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
