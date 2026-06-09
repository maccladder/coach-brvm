<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#f7f7f7;padding:20px;margin:0">
<div style="max-width:600px;background:#fff;padding:28px;border-radius:8px;margin:0 auto">

    <h2 style="color:#C9A84C;margin-top:0;">💸 Nouvelle demande de reversement apporteur</h2>

    <p><strong>Apporteur :</strong> {{ $payout->affiliate->user->name ?? '—' }}</p>
    <p><strong>Code :</strong> <code>{{ $payout->affiliate->code }}</code></p>
    <p><strong>Montant :</strong> {{ number_format((int)$payout->amount, 0, ',', ' ') }} FCFA</p>
    <p><strong>Méthode :</strong> {{ strtoupper($payout->payout_method) }}</p>
    <p><strong>Numéro :</strong> {{ $payout->payout_account }}</p>
    <p><strong>Date :</strong> {{ now()->format('d/m/Y à H:i') }}</p>

    <hr style="border:none;border-top:1px solid #eee;margin:20px 0">

    <p>
        <a href="{{ route('admin.affiliate-payouts.index') }}"
           style="display:inline-block;background:#C9A84C;color:#050810;text-decoration:none;padding:10px 20px;border-radius:4px;font-weight:bold;font-size:13px;">
            Gérer les reversements →
        </a>
    </p>

    <p style="font-size:12px;color:#999;margin-top:24px">Notification automatique – Boursiv</p>
</div>
</body>
</html>
