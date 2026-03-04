<?php

namespace App\Mail;

use App\Models\MarketplaceProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SoftwareActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MarketplaceProduct $product)
    {
    }

    public function build()
    {
        // sujet simple et clair
        $subject = "Finalisez votre achat : activation de {$this->product->title}";

        return $this->subject($subject)
            ->view('emails.software_activation');
    }
}
