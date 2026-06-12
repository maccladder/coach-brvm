{{-- ════════════════════════════════════════════════
     resources/views/vendor/dashboard.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .vd-page { background:var(--cb-paper);min-height:100vh; }
    .vd-hero { background:var(--cb-card);border-bottom:1px solid rgba(176,134,46,.08);padding:36px 0 28px; }
    .vd-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--cb-gold);display:flex;align-items:center;gap:10px;margin-bottom:10px; }
    .vd-hero-tag::before { content:'';width:28px;height:1px;background:var(--cb-gold); }
    .vd-hero-title { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:var(--cb-ink); }
    .vd-hero-sub { font-size:13px;color:var(--cb-muted);margin-top:4px; }

    .vd-kpi-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px; }
    @media(max-width:768px){ .vd-kpi-grid{grid-template-columns:1fr 1fr;} }
    .vd-kpi { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;padding:18px 20px;position:relative;overflow:hidden;transition:border-color .25s; }
    .vd-kpi::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,var(--kc),transparent); }
    .vd-kpi:hover { border-color:rgba(176,134,46,.25); }
    .vd-kpi-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.12em;text-transform:uppercase;color:var(--cb-muted);margin-bottom:8px; }
    .vd-kpi-value { font-family:'Playfair Display',serif;font-size:clamp(22px,3vw,30px);font-weight:900;color:var(--kc);line-height:1; }

    .vd-section { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;overflow:hidden;margin-bottom:20px; }
    .vd-section-header { background:var(--cb-paper);border-bottom:1px solid var(--cb-border);padding:14px 20px;display:flex;justify-content:space-between;align-items:center; }
    .vd-section-title { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-gold);display:flex;align-items:center;gap:8px; }

    .vd-table { width:100%;border-collapse:collapse; }
    .vd-table th { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-muted);padding:12px 16px;border-bottom:1px solid var(--cb-border);white-space:nowrap; }
    .vd-table th:last-child { text-align:right; }
    .vd-table td { padding:13px 16px;border-bottom:1px solid rgba(15,92,67,.04);font-size:13px;color:var(--cb-muted);vertical-align:middle; }
    .vd-table tr:last-child td { border-bottom:none; }
    .vd-table tr:hover td { background:rgba(176,134,46,.025); }
    .vd-table td:first-child { font-family:'Syne',sans-serif;font-weight:700;color:var(--cb-ink); }
    .vd-table td:last-child { text-align:right; }
    .vd-table-footer { padding:14px 20px;border-top:1px solid var(--cb-border); }

    .vd-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 10px;border-radius:100px; }
    .vd-draft     { background:rgba(15,92,67,.04);color:var(--cb-muted);border:1px solid var(--cb-border); }
    .vd-pending   { background:rgba(176,134,46,.08);color:var(--cb-gold);border:1px solid rgba(176,134,46,.2); }
    .vd-published { background:rgba(15,92,67,.08);color:var(--cb-forest);border:1px solid rgba(15,92,67,.2); }
    .vd-rejected  { background:rgba(192,57,43,.08);color:var(--cb-down);border:1px solid rgba(192,57,43,.2); }
    .vd-validated { background:rgba(15,92,67,.08);color:var(--cb-forest);border:1px solid rgba(15,92,67,.2); }
    .vd-waiting   { background:rgba(176,134,46,.08);color:var(--cb-gold);border:1px solid rgba(176,134,46,.2); }

    .vd-price { font-family:'Playfair Display',serif;font-size:15px;font-weight:700;color:var(--cb-gold); }
    .vd-empty { text-align:center;padding:36px;font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-muted); }

    .cb-btn-gold { display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--cb-gold),#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:10px 18px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(176,134,46,.3);transform:translateY(-1px); }
    .cb-btn-green { display:inline-flex;align-items:center;gap:7px;background:rgba(15,92,67,.1);color:var(--cb-forest) !important;font-family:'Syne',sans-serif;font-weight:700;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:9px 14px;border:1px solid rgba(15,92,67,.2);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-green:hover { background:rgba(15,92,67,.16);border-color:rgba(15,92,67,.35); }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:7px;background:transparent;color:var(--cb-ink) !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:8px 14px;border:1px solid var(--cb-border);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:var(--cb-gold);color:var(--cb-gold) !important;background:rgba(176,134,46,.05); }
    .cb-btn-manage { display:inline-flex;align-items:center;gap:5px;background:rgba(15,92,67,.04);color:var(--cb-ink) !important;font-family:'Syne',sans-serif;font-weight:600;font-size:10px;letter-spacing:.07em;text-transform:uppercase;padding:6px 12px;border:1px solid var(--cb-border);border-radius:3px;text-decoration:none;transition:all .25s; }
    .cb-btn-manage:hover { border-color:rgba(176,134,46,.3);color:var(--cb-gold) !important; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.08s; } .cbr3 { transition-delay:.16s; }
</style>
@endpush

@section('content')
<div class="vd-page">

    <div class="vd-hero">
        <div class="container" style="max-width:1100px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="vd-hero-tag">Espace Vendeur</p>
                    <h1 class="vd-hero-title">🛍️ Tableau de bord</h1>
                    <p class="vd-hero-sub">Crée des produits, soumets-les, l'admin valide puis c'est en ligne.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('vendor.earnings') }}" class="cb-btn-outline">
                        <i class="bi bi-cash-coin"></i> Revenus
                    </a>
                    <a href="{{ route('vendor.products.create') }}" class="cb-btn-gold">
                        <i class="bi bi-plus-circle"></i> Nouveau produit
                    </a>
                    <a href="{{ route('vendor.documents.create') }}" class="cb-btn-green">
                        <i class="bi bi-file-earmark-plus"></i> Nouvelle étude
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        {{-- KPIs produits --}}
        @php
            $kpis = [
                ['label'=>'Brouillons',  'val'=>(int)($stats->drafts ?? 0),    'color'=>'var(--cb-muted)'],
                ['label'=>'En attente',  'val'=>(int)($stats->pendings ?? 0),  'color'=>'var(--cb-gold)'],
                ['label'=>'Publiés',     'val'=>(int)($stats->published ?? 0), 'color'=>'var(--cb-forest)'],
                ['label'=>'Rejetés',     'val'=>(int)($stats->rejected ?? 0),  'color'=>'var(--cb-down)'],
            ];
        @endphp
        <div class="vd-kpi-grid cbr">
            @foreach($kpis as $k)
                <div class="vd-kpi" style="--kc:{{ $k['color'] }};">
                    <div class="vd-kpi-label">{{ $k['label'] }}</div>
                    <div class="vd-kpi-value">{{ $k['val'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Études de marché --}}
        <div class="vd-section cbr cbr2">
            <div class="vd-section-header">
                <div class="vd-section-title">📊 Mes études de marché</div>
                <div class="d-flex gap-2">
                    <a href="{{ route('vendor.documents.index') }}" class="cb-btn-outline" style="font-size:10px;padding:6px 12px;">Voir toutes</a>
                    <a href="{{ route('vendor.documents.create') }}" class="cb-btn-green" style="font-size:10px;padding:6px 12px;">➕ Ajouter</a>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="vd-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Titre</th>
                            <th style="text-align:left;">Type</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latestDocuments ?? [] as $doc)
                            <tr>
                                <td>{{ $doc->title }}</td>
                                <td style="text-align:left;"><span style="font-size:12px;color:var(--cb-muted);">{{ ucfirst($doc->type) }}</span></td>
                                <td><span class="vd-price">{{ number_format((float)$doc->price,0,',',' ') }}</span> <span style="font-size:11px;color:var(--cb-muted);">F</span></td>
                                <td><span class="vd-badge {{ $doc->is_active ? 'vd-validated' : 'vd-waiting' }}">{{ $doc->is_active ? '✅ Validé' : '⏳ En attente' }}</span></td>
                                <td><a href="{{ route('vendor.documents.edit', $doc) }}" class="cb-btn-manage">Gérer</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="vd-empty">Aucune étude pour l'instant.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Produits --}}
        <div class="vd-section cbr cbr3">
            <div class="vd-section-header">
                <div class="vd-section-title">📦 Derniers produits</div>
            </div>
            <div style="overflow-x:auto;">
                <table class="vd-table">
                    <thead>
                        <tr>
                            <th style="text-align:left;">Titre</th>
                            <th style="text-align:left;">Catégorie</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($latest as $p)
                            @php
                                $cls = match($p->status) {
                                    'draft'=>'vd-draft','pending'=>'vd-pending',
                                    'published'=>'vd-published','rejected'=>'vd-rejected',default=>'vd-draft'
                                };
                                $ico = match($p->status) {
                                    'draft'=>'✏️','pending'=>'⏳','published'=>'✅','rejected'=>'❌',default=>'—'
                                };
                            @endphp
                            <tr>
                                <td>{{ $p->title }}</td>
                                <td style="text-align:left;font-size:12px;color:#6B7590;">{{ $p->category?->name ?? '—' }}</td>
                                <td><span class="vd-price">{{ number_format((float)$p->price,0,',',' ') }}</span> <span style="font-size:11px;color:var(--cb-muted);">F</span></td>
                                <td><span class="vd-badge {{ $cls }}">{{ $ico }} {{ $p->status }}</span></td>
                                <td><a href="{{ route('vendor.products.edit', $p) }}" class="cb-btn-manage">Gérer</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="vd-empty">Aucun produit pour l'instant.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="vd-table-footer">
                <a href="{{ route('vendor.products.index') }}"
                   style="font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:var(--cb-gold);text-decoration:none;">
                    Voir tous mes produits →
                </a>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); }, { threshold: .06 }).observe(el);
    });
</script>
@endpush
