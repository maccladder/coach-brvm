{{-- resources/views/lettre-ci/dashboard.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .lci-page { background: #060910; min-height: 100vh; padding-bottom: 80px; }

    /* Hero header */
    .lci-header {
        background:
            radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%),
            #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px; position: relative; overflow: hidden;
    }
    .lci-header-grid {
        position:absolute;inset:0;
        background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);
        background-size:56px 56px;
        mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);
        pointer-events:none;
    }
    .lci-header-tag {
        font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;
        text-transform:uppercase;color:#C9A84C;display:flex;align-items:center;gap:10px;margin-bottom:14px;
    }
    .lci-header-tag::before { content:'';width:28px;height:1px;background:#C9A84C; }
    .lci-header-title {
        font-family:'Playfair Display',serif;font-size:clamp(26px,4vw,40px);
        font-weight:900;color:#E8EAF0;margin-bottom:4px;
    }
    .lci-header-sub { font-size:14px;color:#6B7590;font-weight:300; }

    /* Buttons */
    .cb-btn-gold {
        display:inline-flex;align-items:center;gap:8px;
        background:linear-gradient(135deg,#C9A84C,#9B6B15);
        color:#050810 !important;font-family:'Syne',sans-serif;
        font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;
        padding:11px 22px;border:none;border-radius:3px;cursor:pointer;
        text-decoration:none;transition:all .3s;
    }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px);color:#050810 !important; }
    .cb-btn-outline {
        display:inline-flex;align-items:center;gap:8px;
        background:transparent;color:#E8EAF0 !important;
        font-family:'Syne',sans-serif;font-weight:600;
        font-size:12px;letter-spacing:.06em;text-transform:uppercase;
        padding:10px 18px;border:1px solid rgba(255,255,255,.12);
        border-radius:3px;text-decoration:none;transition:all .3s;
    }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }

    /* Stat cards */
    .lci-stat-card {
        background:#0C1120;border:1px solid rgba(201,168,76,.08);
        border-radius:4px;padding:22px 24px;
        position:relative;overflow:hidden;
    }
    .lci-stat-card::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#C9A84C,transparent); }
    .lci-stat-n {
        font-family:'Playfair Display',serif;font-size:36px;
        font-weight:900;color:#C9A84C;line-height:1;display:block;
    }
    .lci-stat-lbl {
        font-family:'Syne',sans-serif;font-size:10px;
        letter-spacing:.12em;text-transform:uppercase;color:#6B7590;margin-top:6px;display:block;
    }

    /* Section label */
    .lci-section-lbl {
        font-family:'Syne',sans-serif;font-size:11px;font-weight:700;
        letter-spacing:.16em;text-transform:uppercase;color:#6B7590;
        margin-bottom:14px;padding-bottom:10px;
        border-bottom:1px solid rgba(255,255,255,.05);
    }

    /* Letter rows */
    .lci-letter-row {
        background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.05);
        border-radius:4px;padding:14px 18px;
        display:flex;align-items:center;gap:14px;
        margin-bottom:6px;transition:border-color .2s;
        text-decoration:none;color:inherit;
    }
    .lci-letter-row:hover { border-color:rgba(201,168,76,.2);color:inherit;text-decoration:none; }
    .lci-letter-name { font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#E8EAF0;margin-bottom:2px; }
    .lci-letter-meta { font-size:12px;color:#6B7590; }

    .lci-status {
        font-family:'Syne',sans-serif;font-size:10px;font-weight:700;
        letter-spacing:.08em;text-transform:uppercase;padding:3px 9px;border-radius:3px;
        margin-left:auto;flex-shrink:0;
    }
    .lci-status-completed { background:rgba(15,207,164,.1);color:#0FCFA4; }
    .lci-status-pending, .lci-status-processing { background:rgba(201,168,76,.1);color:#C9A84C; }
    .lci-status-failed { background:rgba(255,107,107,.1);color:#FF6B6B; }

    /* Quick actions */
    .lci-action-card {
        background:#0C1120;border:1px solid rgba(255,255,255,.06);
        border-radius:4px;padding:18px 20px;
        display:flex;align-items:center;gap:14px;
        text-decoration:none;color:inherit;
        transition:border-color .25s,background .25s;margin-bottom:8px;
    }
    .lci-action-card:hover { border-color:rgba(201,168,76,.2);background:rgba(201,168,76,.03);color:inherit;text-decoration:none; }
    .lci-action-icon { font-size:22px;flex-shrink:0; }
    .lci-action-name { font-family:'Syne',sans-serif;font-size:13px;font-weight:600;color:#E8EAF0;margin-bottom:2px; }
    .lci-action-desc { font-size:12px;color:#6B7590; }
    .lci-action-arrow { color:#C9A84C;margin-left:auto;flex-shrink:0; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.1s; }
</style>
@endpush

@section('content')
<div class="lci-page">

    {{-- Header --}}
    <div class="lci-header">
        <div class="lci-header-grid"></div>
        <div class="container" style="position:relative;z-index:1;">
            <div class="lci-header-tag">LettreCI · Mon espace</div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="lci-header-title">Tableau de bord</h1>
                    <p class="lci-header-sub">Bonjour {{ auth()->user()->name }} — accès à vie actif</p>
                </div>
                <a href="{{ route('lettreci.new') }}" class="cb-btn-gold">+ Nouvelle lettre</a>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        @if(session('success'))
        <div class="alert alert-success mb-4 cbr">{{ session('success') }}</div>
        @endif

        {{-- Stats --}}
        <div class="row g-3 mb-4 cbr">
            <div class="col-6 col-md-3">
                <div class="lci-stat-card">
                    <span class="lci-stat-n">{{ $letterCount }}</span>
                    <span class="lci-stat-lbl">Lettres générées</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lci-stat-card">
                    <span class="lci-stat-n">15</span>
                    <span class="lci-stat-lbl">Modèles dispo</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lci-stat-card" style="border-color:rgba(15,207,164,.1);">
                    <span class="lci-stat-n" style="color:#0FCFA4;">∞</span>
                    <span class="lci-stat-lbl">Générations restantes</span>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lci-stat-card">
                    <span class="lci-stat-n" style="font-size:24px;">À vie</span>
                    <span class="lci-stat-lbl">Durée d'accès</span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Dernières lettres --}}
            <div class="col-lg-8 cbr cbr2">
                <div class="lci-section-lbl">Dernières lettres</div>
                @forelse($recentLetters as $letter)
                @php $tpl = config("lettreci_templates.types.{$letter->template_slug}"); @endphp
                <a href="{{ $letter->status === 'completed' ? route('lettreci.preview', $letter) : route('lettreci.generating', $letter) }}"
                   class="lci-letter-row">
                    <span style="font-size:22px;flex-shrink:0;">{{ $tpl['icon'] ?? '📄' }}</span>
                    <div class="flex-grow-1 min-width-0">
                        <div class="lci-letter-name">{{ $tpl['name'] ?? $letter->template_slug }}</div>
                        <div class="lci-letter-meta">{{ $letter->created_at->diffForHumans() }}</div>
                    </div>
                    <span class="lci-status lci-status-{{ $letter->status }}">{{ $letter->status }}</span>
                </a>
                @empty
                <div style="text-align:center;padding:48px 20px;color:#6B7590;font-size:14px;">
                    Aucune lettre générée pour l'instant.<br>
                    <a href="{{ route('lettreci.new') }}" style="color:#C9A84C;margin-top:10px;display:inline-block;font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;">
                        Créer ma première lettre →
                    </a>
                </div>
                @endforelse
                @if($letterCount > 5)
                <div class="mt-3">
                    <a href="{{ route('lettreci.history') }}" class="cb-btn-outline">
                        Voir tout l'historique ({{ $letterCount }})
                    </a>
                </div>
                @endif
            </div>

            {{-- Actions rapides --}}
            <div class="col-lg-4 cbr cbr2">
                <div class="lci-section-lbl">Actions rapides</div>
                <a href="{{ route('lettreci.new') }}" class="lci-action-card">
                    <span class="lci-action-icon">✏️</span>
                    <div>
                        <div class="lci-action-name">Nouvelle lettre</div>
                        <div class="lci-action-desc">Choisir un modèle et générer</div>
                    </div>
                    <span class="lci-action-arrow">→</span>
                </a>
                <a href="{{ route('lettreci.history') }}" class="lci-action-card">
                    <span class="lci-action-icon">📂</span>
                    <div>
                        <div class="lci-action-name">Historique complet</div>
                        <div class="lci-action-desc">Toutes mes lettres</div>
                    </div>
                    <span class="lci-action-arrow">→</span>
                </a>
                <a href="{{ route('lettreci.showcase') }}" class="lci-action-card">
                    <span class="lci-action-icon">ℹ️</span>
                    <div>
                        <div class="lci-action-name">À propos de LettreCI</div>
                        <div class="lci-action-desc">Vitrine et catalogue</div>
                    </div>
                    <span class="lci-action-arrow">→</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) e.target.classList.add('on'); }, {threshold:.1}).observe(el);
    });
</script>
@endpush
