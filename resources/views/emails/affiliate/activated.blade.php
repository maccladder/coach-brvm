<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#f7f7f7;padding:20px;margin:0">
<div style="max-width:600px;background:#fff;padding:28px;border-radius:8px;margin:0 auto">

    <div style="text-align:center;margin-bottom:24px">
        <div style="font-size:48px;margin-bottom:8px">🎉</div>
        <h2 style="color:#0d3264;margin:0;font-size:20px;">Votre compte apporteur est actif !</h2>
    </div>

    <p>Bonjour <strong>{{ $affiliate->user->name ?? '' }}</strong>,</p>

    <p>
        Félicitations ! Votre compte apporteur d'affaires sur <strong>Coach BRVM</strong> est maintenant activé.
        Vous pouvez dès maintenant partager votre lien et commencer à gagner des commissions.
    </p>

    @php
        $code        = $affiliate->code;
        $affiliateUrl = url('/r/' . $code);
        $dashboardUrl = route('affiliate.dashboard');
        $commRate    = (int) round(config('affiliate.commission_rate', 0.10) * 100);
        $discRate    = (int) round(config('affiliate.discount_rate', 0.10) * 100);
        $minPayout   = number_format(config('affiliate.min_payout', 10000), 0, ',', ' ');
        $delayDays   = (int) config('affiliate.security_delay_days', 10);
    @endphp

    <div style="background:#f0faf5;border:1px solid #c3e6cb;border-radius:6px;padding:20px;margin:20px 0;text-align:center">
        <p style="font-size:13px;color:#555;margin:0 0 10px;text-transform:uppercase;letter-spacing:.05em;font-weight:bold;">Votre code apporteur</p>
        <div style="font-size:32px;font-weight:900;color:#C9A84C;letter-spacing:.12em;margin-bottom:10px">{{ $code }}</div>
        <p style="font-size:13px;color:#555;margin:0 0 8px;">Votre lien de parrainage :</p>
        <a href="{{ $affiliateUrl }}" style="color:#0d6efd;font-size:14px;word-break:break-all;">{{ $affiliateUrl }}</a>
    </div>

    <div style="background:#f8f9fa;border-radius:6px;padding:16px 20px;margin:20px 0">
        <p style="font-size:14px;font-weight:bold;margin:0 0 10px;color:#212529;">Comment ça marche :</p>
        <ul style="font-size:13px;color:#555;margin:0;padding-left:20px;line-height:1.8">
            <li>Partagez votre lien — vos contacts bénéficient de <strong>−{{ $discRate }}%</strong> à l'achat</li>
            <li>Vous touchez <strong>+{{ $commRate }}%</strong> de commission sur chaque vente éligible</li>
            <li>Les commissions sont retirables après {{ $delayDays }} jours, dès <strong>{{ $minPayout }} FCFA</strong> disponibles</li>
            <li>Reversement via Wave, Orange Money, MTN ou Moov</li>
        </ul>
    </div>

    <p style="text-align:center;margin-top:24px">
        <a href="{{ $dashboardUrl }}"
           style="display:inline-block;background:#C9A84C;color:#050810;text-decoration:none;padding:12px 24px;border-radius:4px;font-weight:bold;font-size:13px;">
            Accéder à mon dashboard apporteur →
        </a>
    </p>

    <p style="font-size:12px;color:#999;margin-top:24px">Coach BRVM – Notification automatique</p>
</div>
</body>
</html>
