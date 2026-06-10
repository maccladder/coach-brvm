{{-- ════════ courses/my.blade.php ════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .my-page { background:var(--cb-paper);min-height:100vh; }
    .my-hero { background:var(--cb-card);border-bottom:1px solid var(--cb-border);padding:36px 0 28px; }
    .my-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--cb-gold);display:flex;align-items:center;gap:10px;margin-bottom:12px; }
    .my-hero-tag::before { content:'';width:28px;height:1px;background:var(--cb-gold); }
    .my-hero-title { font-family:'Playfair Display',serif;font-size:clamp(24px,4vw,36px);font-weight:900;color:var(--cb-ink); }
    .my-course-card { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;padding:22px;height:100%;display:flex;flex-direction:column;transition:all .32s; }
    .my-course-card:hover { border-color:rgba(15,92,67,.2);transform:translateY(-3px);box-shadow:0 6px 18px rgba(0,0,0,.07); }
    .my-course-title { font-family:'Syne',sans-serif;font-size:14px;font-weight:700;color:var(--cb-ink);margin-bottom:8px; }
    .my-course-desc { font-size:13px;color:var(--cb-muted);line-height:1.6;flex:1;margin-bottom:16px; }
    .cb-btn-green { display:inline-flex;align-items:center;gap:8px;background:rgba(15,92,67,.08);color:var(--cb-forest) !important;font-family:'Syne',sans-serif;font-weight:700;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:10px 20px;border:1px solid rgba(15,92,67,.2);border-radius:3px;text-decoration:none;transition:all .3s;margin-top:auto; }
    .cb-btn-green:hover { background:rgba(15,92,67,.14);border-color:rgba(15,92,67,.4); }
    .my-empty { text-align:center;padding:80px 20px;background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px; }
    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="my-page">
    <div class="my-hero">
        <div class="container" style="max-width:1100px;">
            <p class="my-hero-tag">Mon espace</p>
            <h1 class="my-hero-title">📚 Mes formations</h1>
        </div>
    </div>
    <div class="container py-5" style="max-width:1100px;">
        @if($courses->isEmpty())
            <div class="my-empty cbr">
                <div style="font-size:32px;margin-bottom:12px;opacity:.4;">🎓</div>
                <div style="font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-muted);">Aucune formation achetée pour le moment.</div>
                <div style="margin-top:20px;">
                    <a href="{{ route('formations.brvm') }}" style="font-family:'Syne',sans-serif;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--cb-gold);text-decoration:none;">Voir les formations →</a>
                </div>
            </div>
        @else
            <div class="row g-4 cbr">
                @foreach($courses as $course)
                    <div class="col-md-4">
                        <div class="my-course-card">
                            <div class="my-course-title">{{ $course->title }}</div>
                            <div class="my-course-desc">{{ \Illuminate\Support\Str::limit($course->description,120) }}</div>
                            <a href="{{ route('courses.show',$course->slug) }}" class="cb-btn-green">▶ Continuer le cours</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.cbr').forEach(el => {
    new IntersectionObserver(([e]) => {
        if (e.isIntersecting) el.classList.add('on');
    }, { threshold: .06 }).observe(el);
});

// Purchase pixel après retour Paystack
@if(session()->has('fb_purchase'))
    if (typeof fbq === 'function') {
        fbq('track', 'Purchase', @json(session('fb_purchase')));
    }
@endif
</script>
@endpush
