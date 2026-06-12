{{-- resources/views/vendor/products/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .vp-page { background:var(--cb-paper); min-height: 100vh; }

    /* Hero */
    .vp-hero { background:var(--cb-card);border-bottom:1px solid rgba(176,134,46,.08);padding:36px 0 28px; }
    .vp-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--cb-gold);display:flex;align-items:center;gap:10px;margin-bottom:10px; }
    .vp-hero-tag::before { content:'';width:28px;height:1px;background:var(--cb-gold); }
    .vp-hero-title { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:var(--cb-ink); }
    .vp-hero-sub { font-size:13px;color:var(--cb-muted);margin-top:4px; }

    /* Filtre */
    .vp-filter { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;padding:16px 20px;margin-bottom:24px; }
    .vp-select { background:var(--cb-paper) !important;border:1px solid var(--cb-border) !important;color:var(--cb-ink) !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:13px !important;padding:9px 12px !important;outline:none;transition:border-color .25s;min-width:200px; }
    .vp-select:focus { border-color:rgba(176,134,46,.4) !important;box-shadow:0 0 0 3px rgba(176,134,46,.07) !important; }
    .vp-select option { background:var(--cb-card); }

    /* Table card */
    .vp-table-card { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;overflow:hidden; }
    .vp-table-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,var(--cb-gold),transparent); }

    /* Table */
    .vp-table { width:100%;border-collapse:collapse; }
    .vp-table thead tr { background:var(--cb-paper); }
    .vp-table th { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--cb-muted);padding:13px 16px;border-bottom:1px solid var(--cb-border);white-space:nowrap; }
    .vp-table th:last-child { text-align:right; }
    .vp-table td { padding:14px 16px;border-bottom:1px solid rgba(15,92,67,.04);font-size:13px;color:var(--cb-muted);vertical-align:middle; }
    .vp-table tr:last-child td { border-bottom:none; }
    .vp-table tr:hover td { background:rgba(176,134,46,.025); }
    .vp-table td:first-child { font-family:'Syne',sans-serif;font-weight:700;color:var(--cb-ink); }
    .vp-table td:last-child { text-align:right; }

    /* Badges statut */
    .st-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 10px;border-radius:100px; }
    .st-draft     { background:rgba(15,92,67,.04);    color:var(--cb-muted); border:1px solid var(--cb-border); }
    .st-pending   { background:rgba(176,134,46,.08);  color:var(--cb-gold);  border:1px solid rgba(176,134,46,.2); }
    .st-published { background:rgba(15,92,67,.08);    color:var(--cb-forest);border:1px solid rgba(15,92,67,.2); }
    .st-rejected  { background:rgba(192,57,43,.08);   color:var(--cb-down);  border:1px solid rgba(192,57,43,.2); }

    /* Badges type */
    .type-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:2px 8px;border-radius:100px;background:rgba(15,92,67,.04);color:var(--cb-muted);border:1px solid var(--cb-border); }

    /* Prix */
    .vp-price { font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:var(--cb-gold); }

    /* Empty */
    .vp-empty { text-align:center;padding:48px 20px;font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-muted); }

    /* Boutons */
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--cb-gold),#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:10px 20px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(176,134,46,.3);transform:translateY(-1px); }
    .cb-btn-manage { display:inline-flex;align-items:center;gap:6px;background:rgba(15,92,67,.04);color:var(--cb-ink) !important;font-family:'Syne',sans-serif;font-weight:600;font-size:10px;letter-spacing:.07em;text-transform:uppercase;padding:7px 14px;border:1px solid var(--cb-border);border-radius:3px;text-decoration:none;transition:all .25s; }
    .cb-btn-manage:hover { border-color:rgba(176,134,46,.3);color:var(--cb-gold) !important;background:rgba(176,134,46,.05); }
    .cb-btn-filter { display:inline-flex;align-items:center;gap:7px;background:rgba(15,92,67,.04);color:var(--cb-ink) !important;font-family:'Syne',sans-serif;font-weight:700;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:9px 16px;border:1px solid var(--cb-border);border-radius:3px;cursor:pointer;transition:all .25s; }
    .cb-btn-filter:hover { border-color:rgba(176,134,46,.2);color:var(--cb-gold) !important; }

    /* Pagination */
    .pagination .page-link { background:var(--cb-card) !important;border-color:var(--cb-border) !important;color:var(--cb-muted) !important;font-family:'Syne',sans-serif;font-size:12px; }
    .pagination .page-link:hover { background:rgba(176,134,46,.08) !important;color:var(--cb-gold) !important;border-color:rgba(176,134,46,.2) !important; }
    .pagination .active .page-link { background:linear-gradient(135deg,var(--cb-gold),#9B6B15) !important;border-color:transparent !important;color:#050810 !important; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.08s; }
</style>
@endpush

@section('content')
<div class="vp-page">

    <div class="vp-hero">
        <div class="container" style="max-width:1100px;">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <p class="vp-hero-tag">Vendeur</p>
                    <h1 class="vp-hero-title">📦 Mes produits</h1>
                    <p class="vp-hero-sub">Brouillon → Soumettre → Admin valide → Marketplace.</p>
                </div>
                <a href="{{ route('vendor.products.create') }}" class="cb-btn-gold">
                    <i class="bi bi-plus-circle"></i> Nouveau produit
                </a>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        {{-- Filtre --}}
        <form class="vp-filter cbr d-flex gap-2 align-items-center flex-wrap" method="GET">
            <select class="vp-select form-select" name="status">
                <option value="">Tous les statuts</option>
                @foreach(['draft','pending','published','rejected'] as $st)
                    <option value="{{ $st }}" @selected(request('status')===$st)>
                        {{ ucfirst($st) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="cb-btn-filter">
                <i class="bi bi-funnel"></i> Filtrer
            </button>
            @if(request('status'))
                <a href="{{ route('vendor.products.index') }}"
                   style="font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--cb-muted);text-decoration:none;">
                    ✕ Réinitialiser
                </a>
            @endif
        </form>

        {{-- Table --}}
        <div class="vp-table-card cbr cbr2">
            <div style="overflow-x:auto;">
                <table class="vp-table">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $p)
                            @php
                                $stClass = match($p->status) {
                                    'draft'     => 'st-draft',
                                    'pending'   => 'st-pending',
                                    'published' => 'st-published',
                                    'rejected'  => 'st-rejected',
                                    default     => 'st-draft',
                                };
                                $stIcon = match($p->status) {
                                    'draft'     => '✏️',
                                    'pending'   => '⏳',
                                    'published' => '✅',
                                    'rejected'  => '❌',
                                    default     => '—',
                                };
                            @endphp
                            <tr>
                                <td>{{ $p->title }}</td>
                                <td>
                                    <span style="font-size:12px;color:var(--cb-muted);">
                                        {{ $p->category?->name ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="type-badge">{{ $p->type }}</span>
                                </td>
                                <td>
                                    <span class="vp-price">{{ number_format((float)$p->price,0,',',' ') }}</span>
                                    <span style="font-size:11px;color:var(--cb-muted);margin-left:2px;">F</span>
                                </td>
                                <td>
                                    <span class="st-badge {{ $stClass }}">
                                        {{ $stIcon }} {{ $p->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('vendor.products.edit', $p) }}" class="cb-btn-manage">
                                        Gérer <i class="bi bi-pencil-square"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="vp-empty">
                                    <div style="font-size:28px;margin-bottom:10px;opacity:.4;">📦</div>
                                    Aucun produit pour l'instant
                                    <div style="margin-top:14px;">
                                        <a href="{{ route('vendor.products.create') }}"
                                           style="font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cb-gold);text-decoration:none;">
                                            + Créer mon premier produit
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($products->hasPages())
            <div class="mt-4 d-flex justify-content-center cbr">
                {{ $products->links() }}
            </div>
        @endif

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
