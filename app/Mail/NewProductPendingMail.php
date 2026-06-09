<?php

namespace App\Mail;

use App\Models\MarketplaceProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewProductPendingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MarketplaceProduct $product)
    {
    }

    public function build()
    {
        return $this
            ->subject('🕒 Nouveau produit en attente de review – Boursiv')
            ->view('emails.new_product_pending');
    }
}
