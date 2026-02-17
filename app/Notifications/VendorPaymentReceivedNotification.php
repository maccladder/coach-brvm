<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class VendorPaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $transactionId,
        public int $productId,
        public string $productTitle,
        public int $amount,
        public string $buyerName,
        public string $url
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'           => 'vendor_payment_received',
            'title'          => '💸 Nouveau paiement',
            'message'        => "{$this->buyerName} a acheté « {$this->productTitle} » ("
                                . number_format($this->amount, 0, ',', ' ') . " FCFA).",
            'url'            => $this->url,

            'transaction_id' => $this->transactionId, // ✅ important pour éviter doublon
            'product_id'     => $this->productId,
            'product_title'  => $this->productTitle,
            'amount'         => $this->amount,
            'buyer_name'     => $this->buyerName,
        ];
    }
}
