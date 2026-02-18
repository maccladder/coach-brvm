<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

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
        // ✅ in-app + email (sans queue)
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $amount = number_format((int) $this->amount, 0, ',', ' ');

        return (new MailMessage)
            ->subject('✅ Nouveau paiement reçu — Coach BRVM')
            ->greeting('Bonjour ' . ($notifiable->name ?? ''))
            ->line("Bonne nouvelle ! {$this->buyerName} a acheté votre produit :")
            ->line("« {$this->productTitle} »")
            ->line("Montant encaissé : {$amount} FCFA")
            ->action('Voir mes revenus', $this->url)
            ->line("Réf transaction : {$this->transactionId}");
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
