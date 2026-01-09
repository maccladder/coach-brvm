<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Book;
use App\Models\BookPage;
use Illuminate\Support\Str;

class BooksSeeder extends Seeder
{
    public function run(): void
    {
        // 1) Action vs Obligation
        $book1 = Book::updateOrCreate(
            ['slug' => 'action-vs-obligation'],
            [
                'title' => 'Action vs Obligation',
                'description' => 'Comprendre clairement la différence : rendement, risque, dividendes et intérêts.',
                'is_free' => true,
                'is_published' => true,
                'estimated_minutes' => 7,
            ]
        );

        $pages1 = [
            [1, 'Objectif', '<p>Comprendre rapidement la différence entre <strong>action</strong> et <strong>obligation</strong> : risque, rendement, revenus.</p>'],
            [2, 'Définition', '<ul><li><strong>Action</strong> : tu deviens copropriétaire d’une entreprise.</li><li><strong>Obligation</strong> : tu prêtes ton argent (État/entreprise).</li></ul>'],
            [3, 'Ce que tu gagnes', '<ul><li>Action : <strong>dividendes</strong> + <strong>hausse du cours</strong> (si ça monte).</li><li>Obligation : <strong>intérêts</strong> (coupon) + remboursement à l’échéance.</li></ul>'],
            [4, 'Risques', '<ul><li>Action : cours variable, peut baisser.</li><li>Obligation : souvent plus stable, mais risque existe (défaut, liquidité, taux).</li></ul>'],
            [5, 'Exemple simple', '<p>10 actions à 5 000 FCFA = 50 000 FCFA. Dividende 300 FCFA/action ⇒ 3 000 FCFA/an + potentiel hausse.</p><p>Obligation 50 000 FCFA à 7% ⇒ 3 500 FCFA/an (plus prévisible).</p>'],
            [6, 'Pour quel profil', '<ul><li>Action : long terme, croissance, dividendes.</li><li>Obligation : prudence, stabilité, revenus réguliers.</li></ul>'],
            [7, 'Choisir vite', '<ul><li>“Je veux dormir tranquille” ⇒ obligations / fonds obligataires.</li><li>“Je veux croissance + dividendes” ⇒ actions solides.</li></ul>'],
            [8, 'Résumé', '<p><strong>Action</strong> = propriété + dividendes (variable). <br><strong>Obligation</strong> = prêt + intérêt (plus prévisible).</p>'],
        ];

        BookPage::where('book_id', $book1->id)->delete();
        foreach ($pages1 as [$no, $title, $content]) {
            BookPage::create([
                'book_id' => $book1->id,
                'page_no' => $no,
                'title' => $title,
                'content' => $content,
            ]);
        }

        // 2) Dividendes
        $book2 = Book::updateOrCreate(
            ['slug' => 'dividendes-comment-ca-marche'],
            [
                'title' => 'Dividendes : comment ça marche vraiment ?',
                'description' => 'Dates clés, règles, rendement, et erreurs fréquentes à éviter.',
                'is_free' => true,
                'is_published' => true,
                'estimated_minutes' => 10,
            ]
        );

        $pages2 = [
            [1, 'Pourquoi c’est important', '<p>Le <strong>dividende</strong> est une partie du bénéfice redistribuée aux actionnaires.</p>'],
            [2, 'Qui décide ?', '<p>Le Conseil propose, l’Assemblée Générale vote.</p>'],
            [3, 'Dates clés', '<ul><li>Date d’annonce</li><li>Date de détachement</li><li>Date de paiement</li></ul>'],
            [4, 'La règle simple', '<p>Pour toucher, il faut être actionnaire <strong>avant</strong> la date de détachement (selon les règles du marché).</p>'],
            [5, 'Exemple', '<p>Action à 10 000 FCFA, dividende 500 FCFA ⇒ rendement brut ≈ <strong>5%</strong>.</p>'],
            [6, 'Pourquoi le cours baisse ?', '<p>Le jour du détachement, le marché intègre le “sortie d’argent” : le cours peut baisser d’environ le dividende.</p>'],
            [7, 'Dividende ≠ garantie', '<p>Une société peut réduire ou supprimer le dividende si la situation change.</p>'],
            [8, 'Dividendes + stratégie', '<ul><li>Long terme : réinvestir.</li><li>Revenu : encaisser régulièrement.</li></ul>'],
            [9, 'Erreurs fréquentes', '<ul><li>Acheter juste avant en pensant “gain gratuit”.</li><li>Ignorer la régularité et la capacité de paiement.</li></ul>'],
            [10, 'Résumé', '<p>Dividende = bénéfice partagé. Les <strong>dates</strong> sont cruciales. Rendement = dividende / prix d’achat.</p>'],
        ];

        BookPage::where('book_id', $book2->id)->delete();
        foreach ($pages2 as [$no, $title, $content]) {
            BookPage::create([
                'book_id' => $book2->id,
                'page_no' => $no,
                'title' => $title,
                'content' => $content,
            ]);
        }
    }
}

