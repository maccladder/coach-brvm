<?php

namespace App\Mail;

use App\Models\ProductFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductFilePendingMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProductFile $file)
    {
    }

    public function build()
    {
        $this->file->loadMissing(['product.vendor', 'replaces']);

        return $this
            ->subject('🕒 Fichier vendeur à valider : ' . $this->file->product->title . ' – Boursiv')
            ->view('emails.product_file_pending')
            ->with(['reviewUrl' => route('admin.marketplace.edit', $this->file->product) . '#fichiers']);
    }
}
