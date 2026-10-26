<?php

namespace App\Mail;

use App\Models\MarketplaceProduct;
use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AbandonedPaymentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    // Support WhatsApp dédié aux achats marketplace (activation avant paiement)
    public const MARKETPLACE_WHATSAPP = '2250767123451';

    public string $whatsappUrl;
    public bool $isMarketplace = false;
    public bool $isGame = false;
    public ?string $productTitle = null;
    public ?string $productUrl = null;

    public function __construct(public Payment $payment)
    {
        if ($payment->purpose === 'marketplace') {
            $product = MarketplaceProduct::find((int) data_get($payment->meta, 'product_id'));

            $this->isMarketplace = true;
            $this->isGame        = $product?->type === 'game';
            $this->productTitle  = $product?->title ?? data_get($payment->meta, 'product_title', 'ton achat');

            $slug = $product?->slug ?? data_get($payment->meta, 'product_slug');
            $this->productUrl = $slug ? route('marketplace.show', $slug) : route('marketplace.index');

            $message = urlencode("Bonjour, j'ai eu un souci pour payer « {$this->productTitle} » sur Boursiv.");
            $this->whatsappUrl = 'https://wa.me/' . self::MARKETPLACE_WHATSAPP . "?text={$message}";

            return;
        }

        $phone   = config('services.whatsapp.support_number');
        $message = urlencode("Bonjour, j'ai eu un souci pour finaliser mon achat sur Boursiv.");

        $this->whatsappUrl = "https://wa.me/{$phone}?text={$message}";
    }

    public function build(): static
    {
        $subject = $this->isMarketplace
            ? "{$this->productTitle} — un souci pour payer ? On t'aide"
            : 'Votre achat sur Boursiv — on est là pour vous aider';

        return $this
            ->subject($subject)
            ->view('emails.abandoned_payment');
    }
}
