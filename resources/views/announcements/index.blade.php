{{-- resources/views/announcements/index.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .ann-page { background: #060910; min-height: 100vh; }

    /* Hero */
    .ann-hero {
        background:
            radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%),
            #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 48px 0 36px;
        position: relative; overflow: hidden;
    }
    .ann-hero-grid {
        position: absolute; inset: 0;
        background-image:
            linear-gradient(rgba(201,168,76,.04) 1px, transparent 1px),
            linear-gradient(90deg, rgba(201,168,76,.04) 1px, transparent 1px);
        background-size: 56px 56px;
        mask-image: radial-gradient(ellipse 80% 70% at 50% 50%, black 0%, transparent 70%);
        pointer-events: none;
    }
    .ann-hero-tag {
        font-family: 'Syne', sans-serif; font-size: 11px; font-weight: 600;
        letter-spacing: .2em; text-transform: uppercase; color: #0FCFA4;
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    }
    .ann-hero-tag::before { content:''; width:28px; height:1px; background:#0FCFA4; }
    .ann-hero-title {
        font-family: 'Playfair Display', serif;
        font-size: clamp(28px, 5vw, 48px); font-weight: 900;
        color: #E8EAF0; line-height: 1.08; margin-bottom: 10px;
    }
    .ann-hero-title em { font-style: italic; color: #C9A84C; }
    .ann-hero-desc { font-size: 14px; color: #6B7590; font-weight: 300; line-height: 1.7; }

    /* Cards */
    .ann-card {
        background: #0C1120;
        border: 1px solid rgba(255,255,255,.05);
        border-radius: 4px;
        padding: 22px 24px;
        text-decoration: none;
        color: inherit;
        display: block;
        transition: all .32s;
        position: relative;
        overflow: hidden;
    }
    .ann-card::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 2px;
        background: linear-gradient(90deg, #C9A84C, transparent);
        opacity: 0; transition: opacity .32s;
    }
    .ann-card:hover {
        border-color: rgba(201,168,76,.2);
        transform: translateY(-3px);
        box-shadow: 0 12px 32px rgba(0,0,0,.35);
        color: inherit;
    }
    .ann-card:hover::before { opacity: 1; }

    .ann-card-date {
        font-family: 'Syne', sans-serif; font-size: 10px; font-weight: 700;
        letter-spacing: .12em; text-transform: uppercase; color: #6B7590;
        margin-bottom: 8px; display: flex; align-items: center; gap: 8px;
    }
    .ann-card-date::before {
        content: '';
        width: 6px; height: 6px; border-radius: 50%;
        background: #C9A84C; flex-shrink: 0;
    }
    .ann-card-title {
        font-family: 'Syne', sans-serif; font-size: 15px; font-weight: 700;
        color: #E8EAF0; line-height: 1.4; margin-bottom: 8px;
        transition: color .25s;
    }
    .ann-card:hover .ann-card-title { color: #C9A84C; }
    .ann-card-excerpt { font-size: 13px; color: #6B7590; line-height: 1.65; }
    .ann-card-arrow {
        position: absolute; right: 22px; top: 50%;
        transform: translateY(-50%);
        font-family: 'Syne', sans-serif; font-size: 18px;
        color: rgba(201,168,76,.2);
        transition: all .25s;
    }
    .ann-card:hover .ann-card-arrow { color: #C9A84C; transform: translateY(-50%) translateX(4px); }

    /* Empty */
    .ann-empty {
        text-align: center; padding: 80px 20px;
        background: rgba(12,17,32,.6);
        border: 1px solid rgba(201,168,76,.08);
        border-radius: 4px;
        font-family: 'Syne', sans-serif; font-size: 13px;
        letter-spacing: .08em; text-transform: uppercase; color: #6B7590;
    }

    /* Pagination */
    .pagination .page-link { background: #0C1120 !important; border-color: rgba(255,255,255,.08) !important; color: #6B7590 !important; font-family: 'Syne', sans-serif; font-size: 12px; }
    .pagination .page-link:hover { background: rgba(201,168,76,.1) !important; color: #C9A84C !important; border-color: rgba(201,168,76,.2) !important; }
    .pagination .active .page-link { background: linear-gradient(135deg,#C9A84C,#9B6B15) !important; border-color: transparent !important; color: #050810 !important; }

    /* Reveal */
    .cbr { opacity:0; transform:translateY(18px); transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="ann-page">

    <div class="ann-hero">
        <div class="ann-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <p class="ann-hero-tag">Marché BRVM</p>
            <h1 class="ann-hero-title">Annonces <em>officielles</em></h1>
            <p class="ann-hero-desc">
                Calendrier des assemblées générales, communiqués, informations marché importantes.
            </p>
        </div>
    </div>

    <div class="container py-5" style="max-width:1100px;">

        @forelse($announcements as $a)
            <a href="{{ route('announcements.show', $a->id) }}" class="ann-card mb-3 cbr">
                <div class="ann-card-date">{{ $a->public_date }}</div>
                <div class="ann-card-title">{{ $a->title }}</div>
                @if($a->excerpt)
                    <div class="ann-card-excerpt">{{ $a->excerpt }}</div>
                @else
                    <div class="ann-card-excerpt">
                        {{ \Illuminate\Support\Str::limit(strip_tags($a->content), 160) }}
                    </div>
                @endif
                <span class="ann-card-arrow">→</span>
            </a>
        @empty
            <div class="ann-empty cbr">
                <div style="font-size:32px;margin-bottom:12px;opacity:.4;">📢</div>
                Aucune annonce pour le moment
            </div>
        @endforelse

        @if($announcements->hasPages())
            <div class="mt-4 d-flex justify-content-center cbr">
                {{ $announcements->links() }}
            </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script>
    const cbEls = document.querySelectorAll('.cbr');
    const cbObs = new IntersectionObserver(e => {
        e.forEach(x => { if(x.isIntersecting) x.target.classList.add('on'); });
    }, { threshold: 0.06 });
    cbEls.forEach(el => cbObs.observe(el));
</script>
@endpush
