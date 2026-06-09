<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class VendorPayoutStatusNotification extends Notification
{
    public function __construct(
        public int $payoutId,
        public int $amount,
        public string $status,   // approved|rejected|paid
        public string $message,
        public string $url
    ) {}

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $amount = number_format((int) $this->amount, 0, ',', ' ');

        $subject = match ($this->status) {
            'approved' => '✅ Reversement approuvé — Boursiv',
            'paid'     => '💸 Reversement payé — Boursiv',
            'rejected' => '⛔ Reversement rejeté — Boursiv',
            default    => 'Notification reversement — Boursiv',
        };

        $title = match ($this->status) {
            'approved' => 'Votre reversement a été approuvé.',
            'paid'     => 'Votre reversement a été payé.',
            'rejected' => 'Votre reversement a été rejeté.',
            default    => 'Mise à jour sur votre reversement.',
        };

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . ($notifiable->name ?? ''))
            ->line($title)
            ->line("Montant : {$amount} FCFA")
            ->line($this->message)
            ->action('Voir le détail', $this->url)
            ->line("Réf reversement : #{$this->payoutId}");
    }

    public function toArray($notifiable): array
    {
        $title = match ($this->status) {
            'approved' => '✅ Reversement approuvé',
            'paid'     => '💸 Reversement payé',
            'rejected' => '⛔ Reversement rejeté',
            default    => 'Notification reversement',
        };

        return [
            'type'      => 'vendor_payout_status',
            'title'     => $title,
            'message'   => $this->message,
            'url'       => $this->url,
            'payout_id' => $this->payoutId,
            'amount'    => $this->amount,
            'status'    => $this->status,
        ];
    }
}
