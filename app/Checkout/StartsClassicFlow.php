<?php

namespace App\Checkout;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pour les types dont l'ancien flux n'est pas un simple formulaire POST
 * (appel JSON depuis un jeu, flux en deux étapes) : "Autre moyen de paiement"
 * passe par la route checkout.mm.classic qui appelle cette méthode.
 */
interface StartsClassicFlow
{
    public function startClassic(Request $request, ?User $user): Response;
}
