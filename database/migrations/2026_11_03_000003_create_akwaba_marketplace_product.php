<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Produit marketplace d'Akwaba : son achat (ou une attribution admin) débloque les
     * chapitres 3 à 7. Créé en brouillon, sans game_html (le jeu est servi depuis
     * resources/games/akwaba.html) ; publié depuis l'admin une fois la page hôte en ligne.
     * Ne touche à rien si le produit existe déjà.
     */
    public function up(): void
    {
        if (DB::table('marketplace_products')->where('slug', 'akwaba')->exists()) {
            return;
        }

        // Même propriétaire et même catégorie que les autres jeux maison
        $reference = DB::table('marketplace_products')->where('slug', 'garba-master')->first(['user_id', 'category_id']);

        DB::table('marketplace_products')->insert([
            'title'              => 'Akwaba : le mystère de la lagune Ébrié',
            'slug'               => 'akwaba',
            'type'               => 'game',
            'description'        => "Gratuit jusqu'au chapitre 2 · Histoire complète 1 000 FCFA.",
            'price'              => 1000,
            'status'             => 'draft',
            'is_featured'        => false,
            'affiliate_eligible' => false,
            'user_id'            => $reference->user_id ?? null,
            'category_id'        => $reference->category_id ?? null,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);
    }

    public function down(): void
    {
        // Ne supprime le produit que s'il n'a jamais été acheté
        $id = DB::table('marketplace_products')->where('slug', 'akwaba')->value('id');

        if ($id && !DB::table('marketplace_purchases')->where('product_id', $id)->exists()) {
            DB::table('marketplace_products')->where('id', $id)->delete();
        }
    }
};
