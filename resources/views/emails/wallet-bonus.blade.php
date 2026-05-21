<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ton bonus Coach BRVM</title>
</head>
<body style="margin:0;padding:0;background-color:#060910;font-family:Helvetica,Arial,sans-serif;">

<div style="max-width:600px;margin:0 auto;padding:32px 16px;">

    {{-- ── Barre or en haut ──────────────────────────────────────── --}}
    <div style="height:3px;background:linear-gradient(90deg,#C9A84C,#9B6B15);border-radius:2px 2px 0 0;margin-bottom:0;"></div>

    {{-- ── Carte principale ──────────────────────────────────────── --}}
    <div style="background:#0C1120;border:1px solid rgba(201,168,76,.18);border-top:none;border-radius:0 0 6px 6px;padding:40px 36px 36px;">

        {{-- Header --}}
        <div style="text-align:center;margin-bottom:28px;">
            <div style="font-size:44px;margin-bottom:12px;">🎁</div>
            <div style="display:inline-block;background:#0FCFA4;color:#060910;font-family:Helvetica,Arial,sans-serif;font-size:10px;font-weight:700;letter-spacing:.14em;text-transform:uppercase;padding:3px 12px;border-radius:2px;margin-bottom:14px;">
                Cadeau Coach BRVM
            </div>
            <h1 style="margin:0;font-size:26px;font-weight:900;color:#E8EAF0;line-height:1.25;">
                Tu as reçu<br>
                <span style="color:#C9A84C;">{{ number_format(config('otp.wallet_bonus_amount'), 0, ',', ' ') }} FCFA virtuels</span>
            </h1>
        </div>

        {{-- Sous-titre --}}
        <p style="margin:0 0 28px;font-size:15px;color:#9AA3B8;text-align:center;line-height:1.6;">
            Découvre le simulateur BRVM sans risquer un seul franc.<br>
            Ton bonus t'attend dans ton portefeuille.
        </p>

        {{-- Montant mis en avant --}}
        <div style="background:rgba(15,207,164,.07);border:1px solid rgba(15,207,164,.2);border-radius:4px;padding:20px 24px;margin-bottom:28px;text-align:center;">
            <div style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#6B7590;margin-bottom:6px;">
                Bonus crédité sur ton compte
            </div>
            <div style="font-size:36px;font-weight:900;color:#0FCFA4;line-height:1;">
                + {{ number_format(config('otp.wallet_bonus_amount'), 0, ',', ' ') }} FCFA
            </div>
            <div style="font-size:12px;color:#6B7590;margin-top:4px;">virtuels — disponibles maintenant</div>
        </div>

        {{-- 3 étapes --}}
        <div style="margin-bottom:32px;">
            <div style="font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#6B7590;margin-bottom:16px;">
                Comment utiliser ton bonus ?
            </div>

            <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:14px;">
                <div style="flex-shrink:0;width:32px;height:32px;background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.25);border-radius:50%;text-align:center;line-height:32px;font-size:15px;">💹</div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:2px;">Achète des actions simulées</div>
                    <div style="font-size:13px;color:#6B7590;line-height:1.5;">Utilise tes FCFA virtuels pour acheter des titres cotés à la BRVM au cours réel du jour.</div>
                </div>
            </div>

            <div style="display:flex;align-items:flex-start;gap:14px;margin-bottom:14px;">
                <div style="flex-shrink:0;width:32px;height:32px;background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.25);border-radius:50%;text-align:center;line-height:32px;font-size:15px;">📊</div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:2px;">Suis tes performances</div>
                    <div style="font-size:13px;color:#6B7590;line-height:1.5;">Observe l'évolution de ton portefeuille en temps réel. Analyse tes gains et pertes sans aucun risque.</div>
                </div>
            </div>

            <div style="display:flex;align-items:flex-start;gap:14px;">
                <div style="flex-shrink:0;width:32px;height:32px;background:rgba(201,168,76,.1);border:1px solid rgba(201,168,76,.25);border-radius:50%;text-align:center;line-height:32px;font-size:15px;">🎓</div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:2px;">Apprends à investir</div>
                    <div style="font-size:13px;color:#6B7590;line-height:1.5;">Quand tu te sens prêt, recharge ton portefeuille avec 1 000 FCFA réels pour obtenir 100 000 FCFA virtuels supplémentaires.</div>
                </div>
            </div>
        </div>

        {{-- CTA --}}
        <div style="text-align:center;margin-bottom:32px;">
            <a href="{{ url('/wallet') }}"
               style="display:inline-block;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810;font-family:Helvetica,Arial,sans-serif;font-size:13px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;padding:14px 32px;border-radius:3px;text-decoration:none;">
                Découvrir le simulateur →
            </a>
        </div>

        {{-- Salutation --}}
        <p style="margin:0 0 4px;font-size:14px;color:#9AA3B8;line-height:1.6;">
            Bonne exploration,<br>
            <strong style="color:#E8EAF0;">L'équipe Coach BRVM</strong>
        </p>

    </div>

    {{-- ── Footer ─────────────────────────────────────────────────── --}}
    <div style="padding:20px 0 0;text-align:center;">
        <p style="margin:0;font-size:11px;color:rgba(107,117,144,.5);line-height:1.6;">
            Cet email a été envoyé à {{ $user->email }}.<br>
            © {{ date('Y') }} Coach BRVM — Simulateur pédagogique, non destiné à des fins d'investissement réel.
        </p>
    </div>

</div>
</body>
</html>
