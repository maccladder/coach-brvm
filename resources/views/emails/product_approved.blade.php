<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px; margin:0;">

<div style="max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden; margin:0 auto; border:1px solid #e5e7eb;">

    <!-- Header -->
    <div style="background:#060910; padding:28px 32px; text-align:center;">
        <p style="font-family:Georgia,serif; font-size:22px; font-weight:900; color:#C9A84C; margin:0; letter-spacing:-.01em;">
            Boursiv
        </p>
    </div>

    <!-- Body -->
    <div style="padding:36px 32px;">

        <p style="font-size:15px; color:#374151; margin:0 0 8px;">Bonjour <strong>{{ $product->vendor->name ?? 'Vendeur' }}</strong>,</p>
        <p style="font-size:15px; color:#374151; margin:0 0 24px;">Bonne nouvelle ! Votre produit a été approuvé.</p>

        <!-- Product card -->
        <div style="background:#f9fafb; border:1px solid #e5e7eb; border-left:4px solid #C9A84C; border-radius:6px; padding:20px 24px; margin:0 0 28px;">
            <p style="margin:0 0 6px; font-size:13px; color:#6b7280; text-transform:uppercase; letter-spacing:.08em;">Produit</p>
            <p style="margin:0 0 16px; font-size:18px; font-weight:700; color:#111827;">« {{ $product->title }} »</p>
            <p style="margin:0; font-size:14px; color:#374151;">
                🎉 Félicitations ! Ton produit <strong>« {{ $product->title }} »</strong> a été approuvé par l'admin et est maintenant visible sur la marketplace.
            </p>
        </div>

        <!-- CTA -->
        <p style="text-align:center; margin:0 0 12px;">
            <a href="{{ url('/marketplace/' . $product->slug) }}"
               style="display:inline-block; background:#C9A84C; color:#060910; padding:14px 32px; border-radius:4px; text-decoration:none; font-weight:700; font-size:14px; letter-spacing:.04em;">
                Voir le produit
            </a>
        </p>

        <p style="font-size:12px; color:#9ca3af; text-align:center; margin:0 0 28px;">
            Si vous avez du mal à cliquer sur le bouton, copiez-collez l'URL ci-dessous dans votre navigateur :<br>
            <a href="{{ url('/marketplace/' . $product->slug) }}" style="color:#C9A84C;">
                {{ url('/marketplace/' . $product->slug) }}
            </a>
        </p>

        <hr style="border:none; border-top:1px solid #e5e7eb; margin:0 0 20px;">

        <p style="font-size:13px; color:#6b7280; margin:0;">
            Regards,<br>
            <strong style="color:#374151;">Boursiv</strong>
        </p>
    </div>

</div>

</body>
</html>
