{{--
    Fichiers téléchargeables côté acheteur.
    Variables : $product (files chargés), $isOwned (bool), $compact (bool, carte « Mes produits »)
--}}
@php
    $compact   = $compact ?? false;
    $pfOs      = \App\Models\ProductFile::osFromUserAgent(request()->userAgent());
    $pfFiles   = $product->files;
    // Version de l'OS détecté mise en tête (installeur Windows préféré au portable), sans masquer les autres
    $pfBest    = $pfFiles->first(fn ($f) => $f->matchesOs($pfOs) && $f->platform !== 'windows_portable')
              ?? $pfFiles->first(fn ($f) => $f->matchesOs($pfOs));
    $pfOrdered = $pfBest ? collect([$pfBest])->concat($pfFiles->reject(fn ($f) => $f->id === $pfBest->id)) : $pfFiles;
    $pfAvail   = $product->availablePlatformsLabel();
@endphp

@once
<style>
    .pfb-avail { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--cb-ink,#1d2433); padding:10px 14px; background:rgba(176,134,46,.06); border:1px solid rgba(176,134,46,.15); border-radius:3px; margin-bottom:10px; }
    .pfb-avail i { color:var(--cb-gold,#b0862e); }
    .pfb-item { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px 14px; padding:12px 14px; border:1px solid var(--cb-border,#e3e6ea); border-radius:3px; margin-bottom:8px; background:var(--cb-card,#fff); }
    .pfb-item.best { border-color:rgba(15,92,67,.45); background:rgba(15,92,67,.05); }
    .pfb-left { display:flex; align-items:center; gap:12px; min-width:0; flex:1 1 200px; }
    .pfb-icon { font-size:22px; color:var(--cb-gold,#b0862e); flex:0 0 auto; width:26px; text-align:center; }
    .pfb-item.best .pfb-icon { color:var(--cb-forest,#0f5c43); }
    .pfb-name { font-family:'Syne',sans-serif; font-size:13px; font-weight:700; color:var(--cb-ink,#1d2433); overflow-wrap:anywhere; }
    .pfb-sub { font-size:12px; color:var(--cb-muted,#6b7590); margin-top:2px; }
    .pfb-best-tag { display:inline-block; font-family:'Syne',sans-serif; font-size:9px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--cb-forest,#0f5c43); border:1px solid rgba(15,92,67,.35); border-radius:100px; padding:2px 8px; margin-left:6px; vertical-align:middle; }
    .pfb-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; background:rgba(15,92,67,.08); border:1px solid rgba(15,92,67,.25); color:var(--cb-forest,#0f5c43) !important; font-family:'Syne',sans-serif; font-weight:700; font-size:11px; letter-spacing:.06em; text-transform:uppercase; padding:8px 14px; border-radius:3px; text-decoration:none; white-space:nowrap; transition:all .25s; }
    .pfb-btn:hover { background:rgba(15,92,67,.14); border-color:rgba(15,92,67,.4); }
    .pfb-item.best .pfb-btn { background:var(--cb-forest,#0f5c43); color:#fff !important; border-color:var(--cb-forest,#0f5c43); }
    .pfb-compact .pfb-item { padding:8px 10px; margin-bottom:6px; }
    .pfb-compact .pfb-icon { font-size:18px; width:20px; }
    .pfb-compact .pfb-btn { padding:6px 10px; }
    @media(max-width:480px){ .pfb-item .pfb-btn { width:100%; } }
</style>
@endonce

<div class="pfb {{ $compact ? 'pfb-compact' : '' }}">
    @if($pfAvail && empty($isOwned))
        <div class="pfb-avail">
            <i class="bi bi-pc-display"></i>
            <span>Disponible pour <strong>{{ $pfAvail }}</strong></span>
        </div>
    @endif

    @foreach($pfOrdered as $f)
        @php $isBest = !empty($isOwned) && $pfBest && $f->id === $pfBest->id && $pfFiles->count() > 1; @endphp
        <div class="pfb-item {{ $isBest ? 'best' : '' }}">
            <div class="pfb-left">
                <i class="bi {{ $f->icon }} pfb-icon"></i>
                <div style="min-width:0;">
                    <div class="pfb-name">
                        {{ $f->label }}
                        @if($isBest)<span class="pfb-best-tag">Recommandé pour votre appareil</span>@endif
                    </div>
                    <div class="pfb-sub">
                        {{ collect([$f->platform_label, $f->human_size])->filter()->implode(' · ') ?: 'Fichier' }}
                        @if(empty($isOwned)) · Accès après achat 🔒 @endif
                    </div>
                </div>
            </div>

            @if(!empty($isOwned))
                <a href="{{ route('marketplace.files.download', [$product, $f]) }}" class="pfb-btn">
                    <i class="bi bi-download"></i> Télécharger
                </a>
            @endif
        </div>
    @endforeach
</div>
