{{-- ════════ dashboard.blade.php ════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .dash-page { background:#060910;min-height:100vh; }
    .dash-hero { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:36px 0 28px; }
    .dash-welcome { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:#C9A84C;margin-bottom:8px; }
    .dash-name { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:#E8EAF0; }

    .dash-card { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;padding:24px;height:100%;transition:all .32s;position:relative;overflow:hidden; }
    .dash-card::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#C9A84C,transparent);opacity:0;transition:opacity .32s; }
    .dash-card:hover { border-color:rgba(201,168,76,.2);transform:translateY(-3px);box-shadow:0 10px 28px rgba(0,0,0,.3); }
    .dash-card:hover::before { opacity:1; }
    .dash-card-icon { font-size:24px;margin-bottom:14px;display:block; }
    .dash-card-title { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:6px; }
    .dash-card-desc { font-size:12.5px;color:#6B7590;line-height:1.6;margin-bottom:18px; }
    .dash-card-btn { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:9px 18px;border:none;border-radius:3px;text-decoration:none;transition:all .3s; }
    .dash-card-btn:hover { box-shadow:0 5px 16px rgba(201,168,76,.3);transform:translateY(-1px); }
    .dash-card-btn.disabled { background:rgba(255,255,255,.06);color:#6B7590 !important;cursor:default;pointer-events:none; }

    .dash-cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#6B7590 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:8px 16px;border:1px solid rgba(255,255,255,.1);border-radius:3px;text-decoration:none;transition:all .3s; }
    .dash-cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.05s; }
    .cbr3 { transition-delay:.1s; }
    .cbr4 { transition-delay:.15s; }
    .cbr5 { transition-delay:.2s; }
</style>
@endpush

@section('content')
<div class="dash-page">
    <div class="dash-hero">
        <div class="container" style="max-width:1100px;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="dash-welcome">Mon espace</div>
                    <h1 class="dash-name">Bienvenue, {{ auth()->user()->name }} 👋</h1>
                </div>
                <a href="{{ route('landing') }}" class="dash-cb-btn-outline">← Accueil</a>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">
        <div class="row g-3">
            <div class="col-md-4 cbr">
                <div class="dash-card">
                    <span class="dash-card-icon">🎓</span>
                    <div class="dash-card-title">Mes cours</div>
                    <div class="dash-card-desc">Accès à tes formations payées. Continue là où tu t'es arrêté.</div>
                    <a href="{{ route('courses.my') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr2">
                <div class="dash-card">
                    <span class="dash-card-icon">🧾</span>
                    <div class="dash-card-title">Mes produits</div>
                    <div class="dash-card-desc">Livres PDF, logiciels et contenus achetés sur la Marketplace.</div>
                    <a href="{{ route('my.products') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr3">
                <div class="dash-card">
                    <span class="dash-card-icon">💼</span>
                    <div class="dash-card-title">Mon portefeuille</div>
                    <div class="dash-card-desc">Solde virtuel, positions et historique de tes transactions.</div>
                    <a href="{{ route('wallet.index') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr2">
                <div class="dash-card">
                    <span class="dash-card-icon">📚</span>
                    <div class="dash-card-title">Mes documents</div>
                    <div class="dash-card-desc">Études de marché & business plans achetés.</div>
                    <a href="{{ route('documents.mine') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr3">
                <div class="dash-card">
                    <span class="dash-card-icon">📄</span>
                    <div class="dash-card-title">
                        Mes analyses
                        @if(!empty($myAnalysesCount) && $myAnalysesCount > 0)
                            <span style="font-size:.75rem;background:var(--cb-gold);color:#060910;border-radius:999px;padding:1px 8px;margin-left:6px;font-weight:700;">{{ $myAnalysesCount }}</span>
                        @endif
                    </div>
                    <div class="dash-card-desc">BOC & états financiers commandés et analysés par l'IA.</div>
                    <a href="{{ route('client-financials.index') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
            <div class="col-md-4 cbr cbr4">
                <div class="dash-card">
                    <span class="dash-card-icon">📡</span>
                    <div class="dash-card-title">Radar Marché</div>
                    <div class="dash-card-desc">Performance 7 jours des sociétés cotées à la BRVM.</div>
                    <a href="{{ route('radar.index') }}" class="dash-card-btn">Ouvrir →</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});</script>
@endpush
