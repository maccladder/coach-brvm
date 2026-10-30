<?php

namespace App\Mail;

use App\Models\MarketplaceProduct;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductFilesAddedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param string[] $fileLabels libellés des fichiers ajoutés */
    public function __construct(
        public MarketplaceProduct $product,
        public User $user,
        public array $fileLabels,
    ) {}

    public function build()
    {
        return $this
            ->subject('Nouvelle version disponible : ' . $this->product->title)
            ->view('emails.product_files_added')
            ->text('emails.product_files_added_text')
            ->with(['productUrl' => route('marketplace.show', $this->product->slug)]);
    }
}
