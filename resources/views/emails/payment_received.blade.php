<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px">

<div style="max-width:600px; background:white; padding:20px; border-radius:8px">

    <h2 style="color:#0d6efd">💰 Nouveau paiement reçu</h2>

    <p><strong>Nom :</strong> {{ $payment->user->name ?? 'N/A' }}</p>
    <p><strong>Email :</strong> {{ $payment->user->email ?? 'N/A' }}</p>

    <hr>

    <p><strong>Montant :</strong>
        {{ number_format($payment->amount_paid, 0, ',', ' ') }} FCFA
    </p>

    <p><strong>Objet du paiement :</strong>
        {{ $payment->description }}
    </p>

    <p><strong>Date du paiement :</strong>
        {{ optional($payment->credited_at)->format('d/m/Y à H:i') }}
    </p>

    <p><strong>Transaction ID :</strong>
        {{ $payment->transaction_id }}
    </p>

    <hr>

    <p style="font-size:12px;color:#666">
        Notification automatique – Coach-BRVM
    </p>

</div>

</body>
</html>
