{{-- ════════════════════════════════════════════
     aide/glossaire.blade.php
════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .gloss-page { background: var(--cb-paper); min-height: 100vh; }
    .gloss-hero { background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(15,92,67,.08) 0%,transparent 55%),var(--cb-paper);border-bottom:1px solid var(--cb-border);padding:48px 0 36px;position:relative;overflow:hidden; }
    .gloss-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(15,92,67,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(15,92,67,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .gloss-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--cb-forest);display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .gloss-hero-tag::before { content:'';width:28px;height:1px;background:var(--cb-forest); }
    .gloss-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:var(--cb-ink);line-height:1.08;margin-bottom:10px; }
    .gloss-hero-title em { font-style:italic;color:var(--cb-gold); }

    .gloss-search { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;padding:18px 22px;margin-bottom:28px; }
    .gloss-search-inner { display:flex;align-items:center;gap:0;background:var(--cb-paper);border:1px solid var(--cb-border);border-radius:3px;overflow:hidden; }
    .gloss-search-inner input { background:transparent !important;border:none !important;color:var(--cb-ink) !important;font-family:'DM Sans',sans-serif !important;font-size:14px !important;padding:11px 14px !important;flex:1;outline:none; }
    .gloss-search-inner input::placeholder { color:var(--cb-muted) !important; }
    .gloss-search-reset { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;background:rgba(176,134,46,.1);color:var(--cb-gold);border:none;border-left:1px solid var(--cb-border);padding:11px 14px;cursor:pointer;transition:all .25s;white-space:nowrap;text-decoration:none; }
    .gloss-search-reset:hover { background:rgba(176,134,46,.18); }

    /* Alphabet */
    .gloss-alpha { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;padding:16px 20px;margin-bottom:32px;display:flex;flex-wrap:wrap;gap:8px;align-items:center; }
    .gloss-alpha-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-muted);flex-shrink:0; }
    .gloss-alpha-btn { font-family:'Syne',sans-serif;font-size:11px;font-weight:700;width:34px;height:30px;display:flex;align-items:center;justify-content:center;border-radius:3px;text-decoration:none;transition:all .25s; }
    .gloss-alpha-btn.active { background:rgba(176,134,46,.1);color:var(--cb-gold);border:1px solid rgba(176,134,46,.2); }
    .gloss-alpha-btn.inactive { background:rgba(26,33,29,.04);color:rgba(108,114,105,.4);pointer-events:none; }

    /* Letters sections */
    .gloss-letter-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;margin-top:4px; }
    .gloss-letter { font-family:'Playfair Display',serif;font-size:32px;font-weight:900;color:var(--cb-gold); }
    .gloss-top-link { font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--cb-muted);text-decoration:none;transition:color .25s; }
    .gloss-top-link:hover { color:var(--cb-gold); }

    .term-card { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;padding:18px 20px;height:100%;transition:border-color .25s; }
    .term-card:hover { border-color:rgba(176,134,46,.2); }
    .term-title { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:var(--cb-ink);margin-bottom:8px; }
    .term-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:2px 8px;border-radius:100px;background:rgba(176,134,46,.08);color:var(--cb-gold);border:1px solid rgba(176,134,46,.15); }
    .term-def { font-size:13px;color:var(--cb-muted);line-height:1.65; }

    .gloss-empty { text-align:center;padding:80px 20px;font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-muted); }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    html { scroll-behavior:smooth; }
</style>
@endpush

@section('content')
@php
    $alphabet  = range('A','Z');
    $available = $items->keys()->map(fn($k) => strtoupper($k))->toArray();
@endphp
<div class="gloss-page" id="top">
    <div class="gloss-hero">
        <div class="gloss-hero-grid"></div>
        <div class="container" style="max-width:1000px;position:relative;z-index:1;">
            <p class="gloss-hero-tag">Aide</p>
            <h1 class="gloss-hero-title">📘 Glossaire <em>BRVM</em></h1>
            <p style="font-size:14px;color:var(--cb-muted);font-weight:300;">Comprends facilement les termes techniques de la bourse régionale.</p>
        </div>
    </div>

    <div class="container py-5" style="max-width:1000px;">

        <form method="GET" class="gloss-search cbr">
            <div class="gloss-search-inner">
                <input type="text" name="q" value="{{ $search }}" placeholder="Rechercher un terme (ex: dividende, PER, obligation…)">
                @if(!empty($search))
                    <a href="{{ route('aide.glossaire') }}" class="gloss-search-reset">✕ Effacer</a>
                @endif
            </div>
        </form>

        <div class="gloss-alpha cbr">
            <span class="gloss-alpha-label">Aller à :</span>
            @foreach($alphabet as $L)
                @if(in_array($L,$available))
                    <a href="#lettre-{{ $L }}" class="gloss-alpha-btn active">{{ $L }}</a>
                @else
                    <span class="gloss-alpha-btn inactive">{{ $L }}</span>
                @endif
            @endforeach
        </div>

        @forelse($items as $lettre => $termes)
            @php $L = strtoupper($lettre); @endphp
            <div class="cbr" style="margin-bottom:32px;">
                <div class="gloss-letter-head">
                    <div class="gloss-letter" id="lettre-{{ $L }}">{{ $L }}</div>
                    <a href="#top" class="gloss-top-link">↑ Haut</a>
                </div>
                <div class="row g-3">
                    @foreach($termes as $item)
                        <div class="col-12 col-md-6">
                            <div class="term-card">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-8" style="margin-bottom:8px;">
                                    <div class="term-title">{{ $item->terme }}</div>
                                    <span class="term-badge">{{ $L }}</span>
                                </div>
                                <div class="term-def">{{ $item->definition }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="gloss-empty cbr">
                <div style="font-size:32px;margin-bottom:12px;opacity:.4;">📘</div>
                Aucun terme trouvé
                <div style="font-size:11px;margin-top:8px;color:var(--cb-muted);">Essaie un autre mot-clé</div>
            </div>
        @endforelse

    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); },{threshold:.06}).observe(el);
    });
</script>
@endpush
