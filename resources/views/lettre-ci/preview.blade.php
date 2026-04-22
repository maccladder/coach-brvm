{{-- resources/views/lettre-ci/preview.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .lci-page { background: #060910; min-height: 100vh; padding-bottom: 80px; }

    .lci-header {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 32px 0 28px; position: relative; overflow: hidden;
    }
    .lci-header-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .lci-header-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;display:flex;align-items:center;gap:10px;margin-bottom:10px; }
    .lci-header-tag::before { content:'';width:28px;height:1px;background:#C9A84C; }
    .lci-header-title { font-family:'Playfair Display',serif;font-size:clamp(20px,3vw,30px);font-weight:900;color:#E8EAF0;margin-bottom:3px; }
    .lci-header-sub { font-size:13px;color:#6B7590; }

    /* Buttons */
    .cb-btn-gold {
        display:inline-flex;align-items:center;gap:8px;
        background:linear-gradient(135deg,#C9A84C,#9B6B15);
        color:#050810 !important;font-family:'Syne',sans-serif;
        font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;
        padding:10px 22px;border:none;border-radius:3px;cursor:pointer;
        text-decoration:none;transition:all .3s;
    }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px);color:#050810 !important; }
    .cb-btn-outline {
        display:inline-flex;align-items:center;gap:7px;background:transparent;color:#E8EAF0 !important;
        font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;
        padding:9px 18px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .25s;
    }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }

    /* Editor card */
    .lci-editor-card {
        background: #0C1120; border: 1px solid rgba(201,168,76,.1);
        border-radius: 6px; overflow: hidden; margin-top: 28px;
    }
    .lci-editor-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,transparent); }
    .lci-editor-inner { padding: 28px 28px 24px; }
    .lci-editor-lbl {
        font-family:'Syne',sans-serif;font-size:11px;font-weight:700;
        letter-spacing:.14em;text-transform:uppercase;color:#6B7590;margin-bottom:12px;
    }
    .lci-editor {
        width:100%;background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);
        border-radius:3px;padding:20px;color:#E8EAF0;font-size:14px;
        font-family:'DM Sans',sans-serif;line-height:1.85;
        min-height:420px;resize:vertical;transition:border-color .2s;
    }
    .lci-editor:focus { outline:none;border-color:rgba(201,168,76,.3); }
    .lci-save-status {
        font-size:12px;color:#6B7590;margin-top:8px;
        font-family:'Syne',sans-serif;letter-spacing:.06em;
    }
    .lci-save-status.saved { color:#0FCFA4; }
    .lci-toolbar {
        display:flex;align-items:center;gap:10px;flex-wrap:wrap;
        margin-top:20px;padding-top:20px;
        border-top:1px solid rgba(255,255,255,.05);
    }

    .lci-breadcrumb {
        padding: 20px 0 0; display: flex; align-items: center; gap: 10px;
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .08em; text-transform: uppercase;
    }
    .lci-breadcrumb a { color: #6B7590; text-decoration: none; transition: color .25s; }
    .lci-breadcrumb a:hover { color: #C9A84C; }
    .lci-breadcrumb span { color: #6B7590; }
    .lci-breadcrumb .current { color: #E8EAF0; }

    .cbr { opacity:0;transform:translateY(16px);transition:all .6s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="lci-page">
    <div class="lci-header">
        <div class="lci-header-grid"></div>
        <div class="container" style="position:relative;z-index:1;">
            <div class="lci-header-tag">LettreCI · Aperçu</div>
            @php $tpl = config("lettreci_templates.types.{$letter->template_slug}"); @endphp
            <h1 class="lci-header-title">{{ $tpl['icon'] ?? '📄' }} {{ $tpl['name'] ?? $letter->template_slug }}</h1>
            <p class="lci-header-sub">Générée {{ $letter->created_at->diffForHumans() }}</p>
        </div>
    </div>

    <div class="container">
        <nav class="lci-breadcrumb">
            <a href="{{ route('lettreci.dashboard') }}">Mon espace</a>
            <span>›</span>
            <span class="current">{{ $tpl['name'] ?? 'Lettre' }}</span>
        </nav>

        @if(session('success'))
        <div class="alert alert-success mt-3 cbr">{{ session('success') }}</div>
        @endif

        <div class="lci-editor-card cbr">
            <div class="lci-editor-inner">
                <div class="lci-editor-lbl">Votre lettre — modifiable avant export PDF</div>

                <form id="saveForm" method="POST" action="{{ route('lettreci.update', $letter) }}">
                    @csrf
                    @method('PATCH')
                    <textarea name="content_final" id="letterEditor" class="lci-editor">{{ $letter->content }}</textarea>
                    <div class="lci-save-status" id="saveStatus">Non sauvegardé</div>

                    <div class="lci-toolbar">
                        <a href="{{ route('lettreci.pdf', $letter) }}" class="cb-btn-gold" target="_blank">
                            📥 Télécharger le PDF
                        </a>
                        <button type="submit" class="cb-btn-outline" id="saveBtn">
                            💾 Sauvegarder
                        </button>
                        <a href="{{ route('lettreci.new') }}" class="cb-btn-outline">
                            + Nouvelle lettre
                        </a>
                        <a href="{{ route('lettreci.history') }}" class="cb-btn-outline ms-auto">
                            Historique →
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) e.target.classList.add('on'); }, {threshold:.05}).observe(el);
    });

    (function () {
        const form   = document.getElementById('saveForm');
        const status = document.getElementById('saveStatus');
        const btn    = document.getElementById('saveBtn');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            btn.textContent = 'Sauvegarde…';
            status.textContent = ''; status.className = 'lci-save-status';

            fetch(form.action, {
                method: 'POST', body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) { status.textContent = '✓ Sauvegardé'; status.className = 'lci-save-status saved'; }
                else { status.textContent = 'Erreur lors de la sauvegarde'; }
                btn.textContent = '💾 Sauvegarder';
            })
            .catch(() => { status.textContent = 'Erreur réseau'; btn.textContent = '💾 Sauvegarder'; });
        });
    })();
</script>
@endpush
