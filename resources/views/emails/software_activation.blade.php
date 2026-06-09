<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activation logiciel</title>
</head>
<body style="margin:0; padding:0; background:#f6f7fb; font-family:Arial, sans-serif;">
    <div style="max-width:640px; margin:0 auto; padding:24px;">
        <div style="background:#ffffff; border-radius:12px; padding:24px; border:1px solid #e9ecf3;">

            <h2 style="margin:0 0 12px; font-size:20px; color:#111827;">
                ✅ Merci pour votre achat !
            </h2>

            <p style="margin:0 0 14px; color:#374151; font-size:14px; line-height:1.6;">
                Votre paiement a été confirmé. Vous avez acheté le logiciel :
                <strong>{{ $product->title }}</strong>
            </p>

            <div style="background:#f0f9ff; border:1px solid #bae6fd; padding:14px; border-radius:10px; margin:16px 0;">
                <p style="margin:0; color:#0c4a6e; font-size:14px; line-height:1.6;">
                    Pour activer votre licence, contactez le support via WhatsApp :
                </p>

                <p style="margin:10px 0 0; font-size:14px; color:#0c4a6e;">
                    <strong>
                    Bonjour, j’ai acheté “{{ $product->title }}” sur Boursiv.
                    Je souhaite recevoir ma licence d’activation.
                    </strong>
                </p>
            </div>

            {{-- BOUTON WHATSAPP --}}
            <div style="text-align:center; margin:20px 0;">
                <a href="https://wa.me/2250160577521"
   style="
        display:inline-block;
        background:#25D366;
        color:#ffffff;
        text-decoration:none;
        padding:12px 22px;
        border-radius:8px;
        font-weight:bold;
        font-size:14px;
   "
   target="_blank">
💬 Contacter le support sur WhatsApp
</a>
            </div>

            <p style="margin:0 0 12px; color:#374151; font-size:14px; line-height:1.6;">
                Notre support vous répondra avec :
                <br>• votre clé/licence
                <br>• les instructions d’installation
                <br>• et l’assistance si nécessaire
            </p>

            <p style="margin:18px 0 0; color:#6b7280; font-size:12px; line-height:1.6;">
                Boursiv — Marketplace<br>
                Si vous n’êtes pas à l’origine de cet achat, vous pouvez ignorer ce mail.
            </p>

        </div>
    </div>
</body>
</html>
