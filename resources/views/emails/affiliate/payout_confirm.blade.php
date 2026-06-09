<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#f7f7f7;padding:20px;margin:0">
<div style="max-width:600px;background:#fff;padding:28px;border-radius:8px;margin:0 auto">

    <h2 style="color:#0d3264;margin-top:0;">📤 Demande de reversement reçue</h2>

    <p>Bonjour <strong>{{ $payout->affiliate->user->name ?? '' }}</strong>,</p>

    <p>
        Votre demande de reversement a bien été enregistrée. Notre équipe va la traiter dès que possible.
    </p>

    <div style="background:#f8f9fa;border-radius:6px;padding:16px 20px;margin:20px 0">
        <table style="width:100%;font-size:14px;border-collapse:collapse">
            <tr>
                <td style="padding:5px 0;color:#555;">Montant demandé</td>
                <td style="padding:5px 0;text-align:right;font-weight:bold;color:#212529;">{{ number_format((int)$payout->amount, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td style="padding:5px 0;color:#555;">Méthode</td>
                <td style="padding:5px 0;text-align:right;color:#212529;">{{ strtoupper($payout->payout_method) }}</td>
            </tr>
            <tr>
                <td style="padding:5px 0;color:#555;">Numéro</td>
                <td style="padding:5px 0;text-align:right;color:#212529;">{{ $payout->payout_account }}</td>
            </tr>
        </table>
    </div>

    <p style="font-size:13px;color:#555;">
        Vous recevrez un email dès que votre demande est approuvée ou rejetée. Vous pouvez suivre l'avancement dans votre dashboard.
    </p>

    <p style="text-align:center;margin-top:20px">
        <a href="{{ route('affiliate.dashboard') }}"
           style="display:inline-block;background:#C9A84C;color:#050810;text-decoration:none;padding:10px 20px;border-radius:4px;font-weight:bold;font-size:13px;">
            Mon dashboard apporteur →
        </a>
    </p>

    <p style="font-size:12px;color:#999;margin-top:24px">Boursiv – Notification automatique</p>
</div>
</body>
</html>
