<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px; margin:0">

<div style="max-width:600px; background:white; padding:30px; border-radius:8px; margin:0 auto">

    <div style="text-align:center; margin-bottom:24px">
        <div style="font-size:48px">⬇️</div>
        <h2 style="color:#0f5c43; margin:8px 0 0">Nouvelle version disponible</h2>
    </div>

    <p>Bonjour <strong>{{ $user->name }}</strong>,</p>

    <p>
        De nouveaux fichiers ont été ajoutés à un produit que vous possédez sur Boursiv&nbsp;:
    </p>

    <div style="background:#f0f7f4; border-left:4px solid #0f5c43; padding:16px 20px; margin:20px 0; border-radius:4px">
        <p style="margin:0; font-size:18px; font-weight:bold; color:#0f3d2e">
            {{ $product->title }}
        </p>
        @if(!empty($fileLabels))
            <ul style="margin:10px 0 0; padding-left:18px; color:#444; font-size:14px">
                @foreach($fileLabels as $label)
                    <li>{{ $label }}</li>
                @endforeach
            </ul>
        @endif
    </div>

    <p>Ils sont déjà inclus dans votre achat : aucun paiement supplémentaire.</p>

    <p style="text-align:center; margin:28px 0">
        <a href="{{ $productUrl }}"
           style="display:inline-block; background:#0f5c43; color:white; padding:14px 28px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:16px">
            Télécharger la nouvelle version →
        </a>
    </p>

    <p>À bientôt sur Boursiv,<br><strong>L'équipe Boursiv</strong></p>

    <hr style="margin-top:30px; border:none; border-top:1px solid #eee">

    <p style="font-size:12px; color:#888; margin-top:15px">
        Vous recevez cet email parce que vous avez accès à ce produit sur Boursiv.<br>
        Cet email a été envoyé à {{ $user->email }}.
    </p>

</div>

</body>
</html>
