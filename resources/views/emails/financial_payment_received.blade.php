<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family: Arial, sans-serif; background:#f7f7f7; padding:20px">

<div style="max-width:600px; background:white; padding:24px; border-radius:8px; margin:auto">

    <h2 style="color:#0d6efd; margin-top:0">💰 Nouveau paiement – États financiers</h2>

    <p><strong>Entreprise :</strong> {{ $financial->company }}</p>
    <p><strong>Période :</strong> {{ $financial->period }}</p>
    <p><strong>Titre :</strong> {{ $financial->title }}</p>

    <hr>

    <p><strong>Client :</strong> {{ $financial->user->name ?? 'Non connecté' }}</p>
    <p><strong>Email :</strong> {{ $financial->client_email ?? $financial->user->email ?? 'N/A' }}</p>

    <hr>

    <p><strong>Montant :</strong> {{ number_format($financial->amount, 0, ',', ' ') }} FCFA</p>
    <p><strong>Statut :</strong> {{ $financial->status }}</p>
    <p><strong>Transaction ID :</strong> {{ $financial->transaction_id }}</p>
    <p><strong>Date :</strong> {{ $financial->created_at->format('d/m/Y à H:i') }}</p>

    <hr>

    <p style="font-size:12px; color:#666">Notification automatique – Coach-BRVM</p>

</div>

</body>
</html>
