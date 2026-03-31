<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px">

<div style="max-width:600px; background:white; padding:30px; border-radius:8px; margin:0 auto">

    <h2 style="color:#0d6efd">🕒 Nouveau produit en attente de review</h2>

    <p>Un vendeur vient de soumettre un produit pour validation.</p>

    <div style="background:#fff8e1; border-left:4px solid #f59e0b; padding:15px; margin:20px 0; border-radius:4px">
        <p style="margin:0 0 8px 0"><strong>Titre :</strong> {{ $product->title }}</p>
        <p style="margin:0 0 8px 0"><strong>Type :</strong> {{ ucfirst($product->type) }}</p>
        <p style="margin:0 0 8px 0"><strong>Prix :</strong> {{ number_format($product->price, 0, ',', ' ') }} FCFA</p>
        <p style="margin:0 0 8px 0"><strong>Vendeur :</strong> {{ $product->vendor->name ?? 'N/A' }}</p>
        <p style="margin:0"><strong>Soumis le :</strong> {{ $product->submitted_at?->format('d/m/Y à H:i') }}</p>
    </div>

    <p>
        <a href="{{ route('admin.marketplace.show', $product) }}"
           style="display:inline-block; background:#0d6efd; color:white; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:bold">
            Voir le produit
        </a>
    </p>

    <hr style="margin-top:30px">

    <p style="font-size:12px; color:#666">
        Notification automatique – Coach-BRVM
    </p>

</div>

</body>
</html>
