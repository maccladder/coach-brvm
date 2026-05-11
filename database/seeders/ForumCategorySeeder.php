<?php

namespace Database\Seeders;

use App\Models\ForumCategory;
use Illuminate\Database\Seeder;

class ForumCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Analyses',
                'slug'        => 'analyses',
                'description' => 'Analyses fondamentales et techniques, revues de sociétés cotées à la BRVM. Partagez vos études et discutez des perspectives.',
                'icon'        => '📊',
                'order'       => 1,
            ],
            [
                'name'        => 'Questions débutants',
                'slug'        => 'questions-debutants',
                'description' => 'Vous débutez en bourse ? Posez toutes vos questions ici sans hésiter. La communauté est là pour vous aider.',
                'icon'        => '💡',
                'order'       => 2,
            ],
            [
                'name'        => 'Actualités BRVM',
                'slug'        => 'actualites-brvm',
                'description' => 'Informations, nouvelles et événements autour de la Bourse Régionale des Valeurs Mobilières et des marchés africains.',
                'icon'        => '📰',
                'order'       => 3,
            ],
            [
                'name'        => "Expériences d'investisseurs",
                'slug'        => 'experiences-investisseurs',
                'description' => "Partagez vos retours d'expérience, stratégies et témoignages en tant qu'investisseur à la BRVM. Apprenez des autres.",
                'icon'        => '💼',
                'order'       => 4,
            ],
        ];

        foreach ($categories as $data) {
            ForumCategory::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
