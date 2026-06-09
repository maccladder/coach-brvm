<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px; margin:0">

<div style="max-width:600px; background:white; padding:30px; border-radius:8px; margin:0 auto">

    <div style="text-align:center; margin-bottom:24px">
        <div style="font-size:48px">{{ $direction === 'received' ? '📥' : '📤' }}</div>
        <h2 style="color:#0d6efd; margin:8px 0 0">
            {{ $direction === 'received' ? 'Transfert reçu' : 'Transfert envoyé' }}
        </h2>
    </div>

    <p>Bonjour <strong>{{ $user->name }}</strong>,</p>

    @if($direction === 'received')
        <p>Votre portefeuille Boursiv vient de recevoir un transfert.</p>
    @else
        <p>Un transfert a été effectué depuis votre portefeuille Boursiv.</p>
    @endif

    <div style="background:{{ $direction === 'received' ? '#f0fdf4' : '#fff8f0' }}; border-left:4px solid {{ $direction === 'received' ? '#198754' : '#fd7e14' }}; padding:16px 20px; margin:20px 0; border-radius:4px">
        <p style="margin:0 0 6px; font-size:14px; color:#555">
            {{ $direction === 'received' ? 'Reçu de' : 'Envoyé vers' }} :
            <strong>{{ $counterpart->name }}</strong>
        </p>
        <p style="margin:0; font-size:26px; font-weight:bold; color:{{ $direction === 'received' ? '#198754' : '#fd7e14' }}">
            {{ $direction === 'received' ? '+' : '-' }}{{ number_format($amount, 0, ',', ' ') }} FCFA
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
           style="display:inline-block; background:#0d6efd; color:white; padding:14px 28px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:16px">
            Voir mon portefeuille →
        </a>
    </p>

    <p>Si vous avez des questions, notre équipe est disponible pour vous aider.</p>

    <p>À bientôt sur Boursiv,<br><strong>L'équipe Boursiv</strong></p>

    <hr style="margin-top:30px; border:none; border-top:1px solid #eee">

    <p style="font-size:12px; color:#888; margin-top:15px">
        Notification officielle – Boursiv<br>
        Cet email a été envoyé à {{ $user->email }}.
    </p>

</div>

</body>
</html>
