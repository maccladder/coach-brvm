<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px; margin:0">

<div style="max-width:600px; background:white; padding:30px; border-radius:8px; margin:0 auto">

    <div style="text-align:center; margin-bottom:24px">
        <div style="font-size:48px">🎁</div>
        <h2 style="color:#0d6efd; margin:8px 0 0">Coach BRVM vous offre un accès gratuit</h2>
    </div>

    <p>Bonjour <strong>{{ $grant->user->name }}</strong>,</p>

    <p>
        Bonne nouvelle ! L'équipe Coach BRVM vous offre un accès <strong>gratuit</strong> à&nbsp;:
    </p>

    <div style="background:#f0f9ff; border-left:4px solid #0d6efd; padding:16px 20px; margin:20px 0; border-radius:4px">
        <p style="margin:0; font-size:18px; font-weight:bold; color:#0d3264">
            {{ $itemName }}
        </p>
        @if($grant->grantable_type !== 'lettreci')
            <p style="margin:6px 0 0; color:#555; font-size:13px">
                Catégorie : {{ $grant->type_label }}
            </p>
        @endif
    </div>

    @if($grant->reason)
        <p style="color:#444">
            <em>"{{ $grant->reason }}"</em>
        </p>
    @endif

    @if($grant->expires_at)
        <p style="color:#856404; background:#fff3cd; border:1px solid #ffc107; padding:10px 14px; border-radius:4px; font-size:14px">
            ⏳ Cet accès est valable jusqu'au <strong>{{ $grant->expires_at->format('d/m/Y') }}</strong>.
        </p>
    @endif

    <p style="text-align:center; margin:28px 0">
        <a href="{{ $itemUrl }}"
           style="display:inline-block; background:#0d6efd; color:white; padding:14px 28px; border-radius:6px; text-decoration:none; font-weight:bold; font-size:16px">
            Accéder maintenant →
        </a>
    </p>

    <p>Si vous avez des questions, notre équipe est disponible pour vous aider.</p>

    <p>À bientôt sur Coach BRVM,<br><strong>L'équipe Coach BRVM</strong></p>

    <hr style="margin-top:30px; border:none; border-top:1px solid #eee">

    <p style="font-size:12px; color:#888; margin-top:15px">
        Notification officielle – Coach BRVM<br>
        Cet email a été envoyé à {{ $grant->user->email }}.
    </p>

</div>

</body>
</html>
