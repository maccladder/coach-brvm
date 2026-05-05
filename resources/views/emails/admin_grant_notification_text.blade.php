Bonjour {{ $grant->user->name }},

Bonne nouvelle ! L'équipe Coach BRVM vous offre un accès gratuit à :

{{ $itemName }}

@if($grant->reason)
Raison : {{ $grant->reason }}

@endif
@if($grant->expires_at)
Cet accès est valable jusqu'au {{ $grant->expires_at->format('d/m/Y') }}.

@endif
Accédez à votre contenu ici : {{ $itemUrl }}

À bientôt,
L'équipe Coach BRVM
