<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px">

<div style="max-width:600px; background:white; padding:30px; border-radius:8px; margin:0 auto">

    <h2 style="color:#0d6efd">Bienvenue sur Boursiv, {{ $user->name }} ! 🎉</h2>

    <p>Nous sommes ravis de vous compter parmi nos membres.</p>

    <p>Votre compte a été créé avec succès avec l'adresse email <strong>{{ $user->email }}</strong>.</p>

    <div style="background:#f0f9ff; border-left:4px solid #0d6efd; padding:15px; margin:20px 0; border-radius:4px">
        <p style="margin:0"><strong>Que pouvez-vous faire sur Boursiv ?</strong></p>
        <ul style="margin:10px 0 0 0; padding-left:20px">
            <li>Accéder à nos formations sur les marchés financiers africains</li>
            <li>Simuler des investissements sur la BRVM</li>
            <li>Suivre les cours et analyses du marché</li>
        </ul>
    </div>

    <p>
        <a href="{{ url('/dashboard') }}"
           style="display:inline-block; background:#0d6efd; color:white; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:bold">
            Accéder à mon espace
        </a>
    </p>

    <p>Si vous avez des questions, n'hésitez pas à nous contacter.</p>

    <p>À bientôt,<br><strong>L'équipe Boursiv</strong></p>

    <hr style="margin-top:30px">

    <p style="font-size:12px; color:#666">
        Notification automatique – Boursiv<br>
        Cet email a été envoyé à {{ $user->email }} suite à votre inscription.
    </p>

</div>

</body>
</html>
