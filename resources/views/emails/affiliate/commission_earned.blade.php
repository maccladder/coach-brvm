<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"></head>
<body style="font-family:Arial,sans-serif;background:#f7f7f7;padding:20px;margin:0">
<div style="max-width:600px;background:#fff;padding:28px;border-radius:8px;margin:0 auto">

    <h2 style="color:#C9A84C;margin-top:0;">💰 Vous avez gagné une commission !</h2>

    @php
        $amount    = number_format($commission->commission_amount, 0, ',', ' ');
        $base      = number_format($commission->base_amount, 0, ',', ' ');
        $delay     = (int) config('affiliate.security_delay_days', 10);
        $typeLabel = match($commission->product_type) {
            'pack_brvm'   => 'Pack BRVM',
            'course'      => 'Formation',
            'marketplace' => 'Produit Marketplace',
            default       => $commission->product_type,
        };
        $unlockDate = $commission->created_at->addDays($delay)->format('d/m/Y');
    @endphp

    <p>Bonjour <strong>{{ $commission->affiliate->user->name ?? '' }}</strong>,</p>

    <p>
        Une vente a été attribuée à votre code apporteur <strong>{{ $commission->affiliate->code }}</strong>.
        Voici le détail de votre commission :
    </p>

    <div style="background:#f0faf5;border:1px solid #c3e6cb;border-radius:6px;padding:20px;margin:20px 0">
        <table style="width:100%;font-size:14px;border-collapse:collapse">
            <tr>
                <td style="padding:6px 0;color:#555;">Type de produit</td>
                <td style="padding:6px 0;text-align:right;font-weight:bold;color:#212529;">{{ $typeLabel }}</td>
            </tr>
            <tr>
                <td style="padding:6px 0;color:#555;">Montant de la vente</td>
                <td style="padding:6px 0;text-align:right;font-weight:bold;color:#212529;">{{ $base }} FCFA</td>
            </tr>
            <tr style="border-top:1px solid #dee2e6">
                <td style="padding:10px 0;color:#198754;font-weight:bold;">Votre commission</td>
                <td style="padding:10px 0;text-align:right;font-weight:900;color:#C9A84C;font-size:18px;">+ {{ $amount }} FCFA</td>
            </tr>
        </table>
    </div>

    <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:14px 18px;margin:16px 0">
        <p style="margin:0;font-size:13px;color:#664d03;">
            ⏳ <strong>Période de sécurité :</strong> cette commission sera retirable à partir du <strong>{{ $unlockDate }}</strong> ({{ $delay }} jours après la vente).
        </p>
    </div>

    <p style="text-align:center;margin-top:20px">
        <a href="{{ route('affiliate.dashboard') }}"
           style="display:inline-block;background:#C9A84C;color:#050810;text-decoration:none;padding:10px 20px;border-radius:4px;font-weight:bold;font-size:13px;">
            Voir mon solde apporteur →
        </a>
    </p>

    <p style="font-size:12px;color:#999;margin-top:24px">Coach BRVM – Notification automatique</p>
</div>
</body>
</html>
