<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbandonedPaymentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $whatsappUrl;

    public function __construct(public Payment $payment)
    {
        $phone   = config('services.whatsapp.support_number');
        $message = urlencode("Bonjour, j'ai eu un souci pour finaliser mon achat sur Boursiv.");

        $this->whatsappUrl = "https://wa.me/{$phone}?text={$message}";
    }

    public function build(): static
    {
        return $this
            ->subject('Votre achat sur Boursiv — on est là pour vous aider')
            ->view('emails.abandoned_payment');
    }
}
