<?php

namespace App\Checkout;

/**
 * Rien à payer (déjà acheté, article gratuit, introuvable…) : on renvoie l'utilisateur ailleurs.
 */
class CheckoutRedirect extends \RuntimeException
{
    public function __construct(
        public string $url,
        public ?string $flashType = null,
        public ?string $flashMessage = null,
    ) {
        parent::__construct($flashMessage ?? 'redirect');
    }

    public static function to(string $url, ?string $type = null, ?string $message = null): self
    {
        return new self($url, $type, $message);
    }
}
