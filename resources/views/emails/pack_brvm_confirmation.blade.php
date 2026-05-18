<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px; margin:0">

<div style="max-width:600px; background:white; padding:30px; border-radius:8px; margin:0 auto">

    <div style="text-align:center; margin-bottom:28px">
        <div style="font-size:52px; margin-bottom:8px">🎓</div>
        <h2 style="color:#0d3264; margin:0; font-size:22px; font-weight:bold;">
            Votre Pack BRVM Complet est activé !
        </h2>
    </div>

    <p>Bonjour <strong>{{ $user->name }}</strong>,</p>

    <p>
        Félicitations ! Votre paiement a été confirmé. Voici ce qui est maintenant disponible dans votre espace :
    </p>

    {{-- Cours inclus --}}
    <div style="background:#f8f9fa; border-radius:6px; padding:18px 20px; margin:20px 0">
        <p style="margin:0 0 12px; font-size:13px; font-weight:bold; color:#555; text-transform:uppercase; letter-spacing:.05em">3 formations incluses</p>
        @foreach($courses as $course)
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px; font-size:14px; color:#212529;">
                <span style="color:#198754; font-weight:bold;">✓</span>
                <span>{{ $course->title }}</span>
            </div>
        @endforeach
        <div style="margin-top:10px; padding-top:10px; border-top:1px solid #dee2e6;">
            <div style="display:flex; align-items:center; gap:10px; font-size:14px; color:#212529;">
                <span style="color:#0d6efd; font-weight:bold;">✓</span>
                <span>Groupe WhatsApp privé d'accompagnement</span>
            </div>
        </div>
        <div style="margin-top:8px;">
            <div style="display:flex; align-items:center; gap:10px; font-size:14px; color:#212529;">
                <span style="color:#0d6efd; font-weight:bold;">✓</span>
                <span>Aide à l'ouverture de compte titre</span>
            </div>
        </div>
    </div>

    {{-- Lien WhatsApp --}}
    <div style="background:#f0f9ff; border-left:4px solid #C9A84C; padding:20px 22px; margin:24px 0; border-radius:4px">
        <p style="margin:0 0 6px; font-size:13px; font-weight:bold; color:#0d3264;">
            🔗 Votre lien d'accès au groupe WhatsApp
        </p>
        <p style="margin:0 0 16px; font-size:13px; color:#555; line-height:1.6;">
            Ce lien est <strong>personnel et à usage unique</strong>. Ne le partagez pas.
        </p>
        <a href="{{ route('pack.groupe', $token) }}"
           style="display:inline-block; background:#C9A84C; color:white; padding:12px 24px; border-radius:5px; text-decoration:none; font-weight:bold; font-size:14px;">
            Rejoindre le groupe WhatsApp →
        </a>
    </div>

    <p style="color:#555; font-size:13px; line-height:1.7;">
        Pour accéder à vos cours, connectez-vous sur <a href="{{ config('app.url') }}" style="color:#C9A84C;">Coach BRVM</a>
        et rendez-vous dans <strong>Mes formations</strong>.
    </p>

    <p>À bientôt sur Coach BRVM,<br><strong>L'équipe Coach BRVM</strong></p>

    <hr style="margin-top:30px; border:none; border-top:1px solid #eee">

    <p style="font-size:11px; color:#aaa; margin-top:14px; line-height:1.6">
        Notification officielle – Coach BRVM<br>
        Cet email a été envoyé à {{ $user->email }}.<br>
        Le lien WhatsApp est à usage unique. En cas de problème, contactez notre équipe.
    </p>

</div>

</body>
</html>
