<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

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
        return ['database'];
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
