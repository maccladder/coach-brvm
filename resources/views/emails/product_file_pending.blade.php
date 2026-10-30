<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px; margin:0">

<div style="max-width:600px; background:white; padding:30px; border-radius:8px; margin:0 auto">

    <h2 style="color:#b0862e; margin:0 0 16px">🕒 Fichier vendeur à valider</h2>

    <p>
        {{ $file->product->vendor?->name ?? 'Un vendeur' }} a
        {{ $file->replaces ? 'remplacé un fichier' : 'ajouté un fichier' }}
        sur un produit <strong>publié</strong>. Il reste invisible pour les acheteurs tant que tu ne l'as pas approuvé.
    </p>

    <div style="background:#faf6ee; border-left:4px solid #b0862e; padding:16px 20px; margin:20px 0; border-radius:4px; font-size:14px; color:#333">
        <p style="margin:0 0 6px"><strong>Produit :</strong> {{ $file->product->title }}</p>
        <p style="margin:0 0 6px"><strong>Fichier :</strong> {{ $file->original_name }} @if($file->human_size)({{ $file->human_size }})@endif</p>
        <p style="margin:0 0 6px"><strong>Libellé :</strong> {{ $file->label }} @if($file->platform_label)· {{ $file->platform_label }}@endif</p>
        @if($file->replaces)
            <p style="margin:0"><strong>Remplace :</strong> {{ $file->replaces->label }} ({{ $file->replaces->original_name }})</p>
        @endif
    </div>

    <p style="text-align:center; margin:28px 0">
        <a href="{{ $reviewUrl }}"
           style="display:inline-block; background:#b0862e; color:#050810; padding:14px 28px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:16px">
            Examiner le fichier →
        </a>
    </p>

    <p style="font-size:12px; color:#888">Notification automatique – Boursiv Marketplace</p>

</div>

</body>
</html>
