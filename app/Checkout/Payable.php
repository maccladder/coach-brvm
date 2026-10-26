<?php

namespace App\Checkout;

/**
 * Ce que l'utilisateur est en train de payer, tel qu'affiché par le checkout
 * Mobile Money. Toujours reconstruit côté serveur à partir de $params :
 * le montant ne vient jamais du navigateur.
 */
final class Payable
{
    /**
     * @param string      $type          clé du type (marketplace, course, pack…)
     * @param string      $itemKey       identifiant de l'article (une tentative en cours par user + article)
     * @param array       $badges        petits libellés sous le titre
     * @param array       $params        paramètres de requête qui reconstruisent ce payable
     * @param bool        $offerActivation  WhatsApp "On t'active l'accès, tu paies après" en cas d'échec
     * @param array       $context       données internes au type (code apporteur, ids…)
     */
    public function __construct(
        public string $type,
        public string $itemKey,
        public string $title,
        public int $amount,
        public string $email,
        public string $successUrl,
        public string $successLabel,
        public ?string $image = null,
        public array $badges = [],
        public ?string $note = null,
        public array $params = [],
        public bool $offerActivation = true,
        public string $icon = '🛒',
        public array $context = [],
        public string $successTitle = "C'est payé !",
    ) {}

    /** Données figées sur la tentative (affichage des écrans attente / succès / échec). */
    public function snapshot(): array
    {
        return [
            'title'            => $this->title,
            'image'            => $this->image,
            'icon'             => $this->icon,
            'success_url'      => $this->successUrl,
            'success_label'    => $this->successLabel,
            'success_title'    => $this->successTitle,
            'note'             => $this->note,
            'offer_activation' => $this->offerActivation,
            'params'           => $this->params,
        ];
    }
}
