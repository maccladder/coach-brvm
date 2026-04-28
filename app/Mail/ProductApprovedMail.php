<?php

namespace App\Mail;

use App\Models\MarketplaceProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MarketplaceProduct $product)
    {
    }

    public function build()
    {
        return $this
            ->subject('🎉 Votre produit a été approuvé – Coach BRVM')
            ->view('emails.product_approved');
    }
}
