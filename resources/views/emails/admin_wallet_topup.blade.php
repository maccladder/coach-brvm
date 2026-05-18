<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px; margin:0">

<div style="max-width:600px; background:white; padding:30px; border-radius:8px; margin:0 auto">

    <div style="text-align:center; margin-bottom:24px">
        <div style="font-size:48px">💰</div>
        <h2 style="color:#198754; margin:8px 0 0">Portefeuille rechargé</h2>
    </div>

    <p>Bonjour <strong>{{ $user->name }}</strong>,</p>

    <p>Bonne nouvelle ! Votre portefeuille virtuel Coach BRVM vient d'être rechargé.</p>

    <div style="background:#f0fdf4; border-left:4px solid #198754; padding:16px 20px; margin:20px 0; border-radius:4px">
        <p style="margin:0 0 8px; font-size:15px; color:#555">Montant crédité</p>
        <p style="margin:0; font-size:26px; font-weight:bold; color:#198754">
            + {{ number_format($amount, 0, ',', ' ') }} FCFA
        </p>
    </div>

    <div style="background:#f8f9fa; border:1px solid #dee2e6; padding:14px 20px; border-radius:4px; margin-bottom:20px">
        <p style="margin:0; font-size:14px; color:#555">
            Nouveau solde disponible :
            <strong style="font-size:16px; color:#212529">{{ number_format($newBalance, 0, ',', ' ') }} FCFA</strong>
        </p>
    </div>

    @if($motif)
        <p style="color:#444; font-style:italic; border-left:3px solid #dee2e6; padding-left:12px; margin:16px 0">
            Motif : "{{ $motif }}"
        </p>
    @endif

    <p style="text-align:center; margin:28px 0">
        <a href="{{ route('wallet.index') }}"
           style="display:inline-block; background:#198754; color:white; padding:14px 28px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:16px">
            Voir mon portefeuille →
        </a>
    </p>

    <p>Si vous avez des questions, notre équipe est disponible pour vous aider.</p>

    <p>À bientôt sur Coach BRVM,<br><strong>L'équipe Coach BRVM</strong></p>

    <hr style="margin-top:30px; border:none; border-top:1px solid #eee">

    <p style="font-size:12px; color:#888; margin-top:15px">
        Notification officielle – Coach BRVM<br>
        Cet email a été envoyé à {{ $user->email }}.
    </p>

</div>

</body>
</html>
