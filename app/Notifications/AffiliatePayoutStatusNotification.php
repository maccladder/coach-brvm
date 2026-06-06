<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AffiliatePayoutStatusNotification extends Notification
{
    public function __construct(
        public readonly int     $payoutId,
        public readonly int     $amount,
        public readonly string  $status,   // approved | rejected | paid
        public readonly string  $message,
        public readonly ?string $url = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $amountFmt = number_format($this->amount, 0, ',', ' ');

        $subject = match ($this->status) {
            'approved' => '✅ Reversement approuvé — Coach BRVM',
            'paid'     => '💸 Reversement payé — Coach BRVM',
            'rejected' => '⛔ Reversement rejeté — Coach BRVM',
            default    => 'Mise à jour reversement — Coach BRVM',
        };

        $headline = match ($this->status) {
            'approved' => 'Votre reversement a été approuvé.',
            'paid'     => 'Votre reversement a été effectué.',
            'rejected' => 'Votre reversement a été rejeté.',
            default    => 'Mise à jour sur votre reversement.',
        };

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour ' . ($notifiable->name ?? '') . ',')
            ->line($headline)
            ->line("Montant : {$amountFmt} FCFA")
            ->line($this->message);

        if ($this->url) {
            $mail->action('Voir mon dashboard', $this->url);
        }

        return $mail->line("Référence demande : #{$this->payoutId}");
    }

    public function toArray(object $notifiable): array
    {
        $title = match ($this->status) {
            'approved' => '✅ Reversement approuvé',
            'paid'     => '💸 Reversement payé',
            'rejected' => '⛔ Reversement rejeté',
            default    => 'Notification reversement',
        };

        return [
            'payout_id' => $this->payoutId,
            'amount'    => $this->amount,
            'status'    => $this->status,
            'title'     => $title,
            'message'   => $this->message,
            'url'       => $this->url,
        ];
    }
}
