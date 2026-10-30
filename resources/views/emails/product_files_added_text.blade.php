Bonjour {{ $user->name }},

De nouveaux fichiers ont été ajoutés à un produit que vous possédez sur Boursiv :

{{ $product->title }}
@foreach($fileLabels as $label)
- {{ $label }}
@endforeach

Ils sont déjà inclus dans votre achat : aucun paiement supplémentaire.

Téléchargez la nouvelle version ici : {{ $productUrl }}

À bientôt,
L'équipe Boursiv
