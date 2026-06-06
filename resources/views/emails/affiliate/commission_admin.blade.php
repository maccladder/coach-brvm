<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#f7f7f7;padding:20px;margin:0">
<div style="max-width:600px;background:#fff;padding:28px;border-radius:8px;margin:0 auto">

    <h2 style="color:#C9A84C;margin-top:0;">🤝 Vente affiliée</h2>

    @php
        $typeLabel = match($commission->product_type) {
            'pack_brvm'   => 'Pack BRVM',
            'course'      => 'Formation',
            'marketplace' => 'Produit Marketplace',
            default       => $commission->product_type,
        };
    @endphp

    <p><strong>Apporteur :</strong> {{ $commission->affiliate->user->name ?? '—' }} (code <code>{{ $commission->affiliate->code }}</code>)</p>
    <p><strong>Acheteur :</strong> {{ $commission->buyer->name ?? '—' }}</p>
    <p><strong>Type :</strong> {{ $typeLabel }}</p>
    <p><strong>Base :</strong> {{ number_format($commission->base_amount, 0, ',', ' ') }} FCFA</p>
    <p><strong>Commission :</strong> <span style="color:#C9A84C;font-weight:bold;">{{ number_format($commission->commission_amount, 0, ',', ' ') }} FCFA</span></p>
    <p><strong>Remise acheteur :</strong> {{ number_format($commission->discount_amount, 0, ',', ' ') }} FCFA</p>
    <p><strong>Statut commission :</strong> {{ $commission->status }}</p>

    <hr style="border:none;border-top:1px solid #eee;margin:20px 0">

    <p>
        <a href="{{ route('admin.affiliates.commissions', $commission->affiliate) }}"
           style="display:inline-block;background:#C9A84C;color:#050810;text-decoration:none;padding:10px 20px;border-radius:4px;font-weight:bold;font-size:13px;">
            Voir les commissions →
        </a>
    </p>

    <p style="font-size:12px;color:#999;margin-top:24px">Notification automatique – Coach BRVM</p>
</div>
</body>
</html>
