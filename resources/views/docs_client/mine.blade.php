{{-- ════════════════════════════════════════════════
     resources/views/docs_client/mine.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .mine-page { background: #060910; min-height: 100vh; }

    .mine-hero { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:36px 0 28px; }
    .mine-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;display:flex;align-items:center;gap:10px;margin-bottom:10px; }
    .mine-hero-tag::before { content:'';width:28px;height:1px;background:#C9A84C; }
    .mine-hero-title { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:#E8EAF0; }

    /* Cards */
    .mine-card {
        background: #0C1120; border: 1px solid rgba(255,255,255,.06);
        border-radius: 4px; padding: 22px; height: 100%;
        display: flex; flex-direction: column;
        transition: all .32s; position: relative; overflow: hidden;
    }
    .mine-card::before { content:'';position:absolute;top:0;left:0;right:0;height:2px;background:linear-gradient(90deg,#C9A84C,transparent);opacity:0;transition:opacity .32s; }
    .mine-card:hover { border-color:rgba(201,168,76,.2);transform:translateY(-3px); }
    .mine-card:hover::before { opacity:1; }

    .mine-card-title { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:#E8EAF0;margin-bottom:8px;line-height:1.35; }
    .mine-card-desc { font-size:13px;color:#6B7590;line-height:1.6;flex:1;margin-bottom:16px; }

    /* Boutons */
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:10px 20px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s;margin-top:auto; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:9px 18px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }

    /* Empty */
    .mine-empty { text-align:center;padding:80px 20px;background:rgba(12,17,32,.6);border:1px solid rgba(201,168,76,.08);border-radius:4px; }

    /* Pagination */
    .pagination .page-link { background:#0C1120 !important;border-color:rgba(255,255,255,.08) !important;color:#6B7590 !important;font-family:'Syne',sans-serif;font-size:12px; }
    .pagination .page-link:hover { background:rgba(201,168,76,.1) !important;color:#C9A84C !important;border-color:rgba(201,168,76,.2) !important; }
    .pagination .active .page-link { background:linear-gradient(135deg,#C9A84C,#9B6B15) !important;border-color:transparent !important;color:#050810 !important; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.06s; }
</style>
@endpush

@section('content')
<div class="mine-page">

    <div class="mine-hero">
        <div class="container" style="max-width:1100px;">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <p class="mine-hero-tag">Mon espace</p>
                    <h1 class="mine-hero-title">📚 Mes documents</h1>
                    <p style="font-size:14px;color:#6B7590;font-weight:300;margin-top:4px;">
                        Tes études de marché & business plans achetés.
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('dashboard') }}" class="cb-btn-outline" style="font-size:11px;padding:8px 14px;">← Mon espace</a>
                    <a href="{{ route('docs.public.index') }}" class="cb-btn-gold" style="font-size:11px;padding:9px 16px;">📄 Parcourir le catalogue</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        @if(empty($documents ?? null) || (is_countable($documents) && count($documents) === 0))
            <div class="mine-empty cbr">
                <div style="font-size:36px;margin-bottom:14px;opacity:.4;">📚</div>
                <div style="font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;margin-bottom:20px;">
                    Aucun document acheté pour l'instant
                </div>
                <div style="font-size:13px;color:#6B7590;margin-bottom:24px;">
                    Va sur le catalogue pour acheter une étude de marché ou un business plan.
                </div>
                <a href="{{ route('docs.public.index') }}" class="cb-btn-gold">
                    Ouvrir le catalogue →
                </a>
            </div>
        @else
            <div class="row g-3 cbr cbr2">
                @foreach($documents as $doc)
                    <div class="col-md-6 col-lg-4">
                        <div class="mine-card">
                            <div class="mine-card-title">{{ $doc->title }}</div>
                            @if(!empty($doc->description))
                                <div class="mine-card-desc">
                                    {{ \Illuminate\Support\Str::limit(strip_tags($doc->description), 100) }}
                                </div>
                            @endif
                            <a href="{{ route('documents.download', $doc->id) }}" class="cb-btn-gold">
                                <i class="bi bi-download"></i> Télécharger
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            @if(method_exists($documents, 'links') && $documents->hasPages())
                <div class="mt-4 d-flex justify-content-center cbr">
                    {{ $documents->links() }}
                </div>
            @endif
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
