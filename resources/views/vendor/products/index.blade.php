{{-- resources/views/vendor/products/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .vp-page { background: #060910; min-height: 100vh; }

    /* Hero */
    .vp-hero { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:36px 0 28px; }
    .vp-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;display:flex;align-items:center;gap:10px;margin-bottom:10px; }
    .vp-hero-tag::before { content:'';width:28px;height:1px;background:#C9A84C; }
    .vp-hero-title { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:#E8EAF0; }
    .vp-hero-sub { font-size:13px;color:#6B7590;margin-top:4px; }

    /* Filtre */
    .vp-filter { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;padding:16px 20px;margin-bottom:24px; }
    .vp-select { background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;color:#E8EAF0 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:13px !important;padding:9px 12px !important;outline:none;transition:border-color .25s;min-width:200px; }
    .vp-select:focus { border-color:rgba(201,168,76,.4) !important;box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .vp-select option { background:#0C1120; }

    /* Table card */
    .vp-table-card { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;overflow:hidden; }
    .vp-table-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,transparent); }

    /* Table */
    .vp-table { width:100%;border-collapse:collapse; }
    .vp-table thead tr { background:#121A2C; }
    .vp-table th { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#6B7590;padding:13px 16px;border-bottom:1px solid rgba(255,255,255,.06);white-space:nowrap; }
    .vp-table th:last-child { text-align:right; }
    .vp-table td { padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.04);font-size:13px;color:#9AA3B8;vertical-align:middle; }
    .vp-table tr:last-child td { border-bottom:none; }
    .vp-table tr:hover td { background:rgba(201,168,76,.02); }
    .vp-table td:first-child { font-family:'Syne',sans-serif;font-weight:700;color:#E8EAF0; }
    .vp-table td:last-child { text-align:right; }

    /* Badges statut */
    .st-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:3px 10px;border-radius:100px; }
    .st-draft     { background:rgba(255,255,255,.06);    color:#9AA3B8; border:1px solid rgba(255,255,255,.1); }
    .st-pending   { background:rgba(255,200,30,.08);     color:#FFC850; border:1px solid rgba(255,200,30,.2); }
    .st-published { background:rgba(15,207,164,.08);     color:#0FCFA4; border:1px solid rgba(15,207,164,.2); }
    .st-rejected  { background:rgba(255,107,107,.08);    color:#FF6B6B; border:1px solid rgba(255,107,107,.2); }

    /* Badges type */
    .type-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:2px 8px;border-radius:100px;background:rgba(255,255,255,.04);color:#6B7590;border:1px solid rgba(255,255,255,.08); }

    /* Prix */
    .vp-price { font-family:'Playfair Display',serif;font-size:16px;font-weight:700;color:#C9A84C; }

    /* Empty */
    .vp-empty { text-align:center;padding:48px 20px;font-family:'Syne',sans-serif;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }

    /* Boutons */
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:10px 20px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-manage { display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.04);color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:10px;letter-spacing:.07em;text-transform:uppercase;padding:7px 14px;border:1px solid rgba(255,255,255,.1);border-radius:3px;text-decoration:none;transition:all .25s; }
    .cb-btn-manage:hover { border-color:rgba(201,168,76,.3);color:#C9A84C !important;background:rgba(201,168,76,.06); }
    .cb-btn-filter { display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.05);color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:700;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:9px 16px;border:1px solid rgba(255,255,255,.1);border-radius:3px;cursor:pointer;transition:all .25s; }
    .cb-btn-filter:hover { border-color:rgba(201,168,76,.2);color:#C9A84C !important; }

    /* Pagination */
    .pagination .page-link { background:#0C1120 !important;border-color:rgba(255,255,255,.08) !important;color:#6B7590 !important;font-family:'Syne',sans-serif;font-size:12px; }
    .pagination .page-link:hover { background:rgba(201,168,76,.1) !important;color:#C9A84C !important;border-color:rgba(201,168,76,.2) !important; }
    .pagination .active .page-link { background:linear-gradient(135deg,#C9A84C,#9B6B15) !important;border-color:transparent !important;color:#050810 !important; }

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
                   style="font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:#6B7590;text-decoration:none;">
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
                                    <span style="font-size:12px;color:#6B7590;">
                                        {{ $p->category?->name ?? '—' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="type-badge">{{ $p->type }}</span>
                                </td>
                                <td>
                                    <span class="vp-price">{{ number_format((float)$p->price,0,',',' ') }}</span>
                                    <span style="font-size:11px;color:#6B7590;margin-left:2px;">F</span>
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
                                           style="font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#C9A84C;text-decoration:none;">
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
