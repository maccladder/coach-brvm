{{-- ════════ courses/show.blade.php ════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .course-show-page { background:#060910;min-height:100vh; }
    .course-show-header { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:28px 0; }
    .course-show-title { font-family:'Playfair Display',serif;font-size:clamp(22px,4vw,34px);font-weight:900;color:#E8EAF0;margin-bottom:8px; }
    .course-show-desc { font-size:14px;color:#6B7590;font-weight:300;line-height:1.7; }
    .course-video-card { background:#0C1120;border:1px solid rgba(201,168,76,.1);border-radius:4px;overflow:hidden;margin-top:28px; }
    .course-video-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,#0FCFA4,transparent); }
    .course-video-wrap { background:#060910; }
    .course-video-wrap iframe { width:100%;aspect-ratio:16/9;border:none;display:block; }
    .course-protected-box { margin:16px 20px 20px;background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:3px;padding:12px 16px;font-size:12px;color:#6B7590;font-family:'Syne',sans-serif;letter-spacing:.06em; }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:9px 18px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }
    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="course-show-page">
    <div class="course-show-header">
        <div class="container" style="max-width:1000px;">
            <a href="{{ route('courses.my') }}" style="font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6B7590;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:16px;">← Mes cours</a>
            <h1 class="course-show-title">{{ $course->title }}</h1>
            @if($course->description)<p class="course-show-desc">{{ $course->description }}</p>@endif
        </div>
    </div>
    <div class="container py-4" style="max-width:1000px;">
        <div class="course-video-card cbr">
            <div class="course-video-wrap">
                <iframe src="{{ $iframe }}" loading="lazy" allow="accelerometer;gyroscope;autoplay;encrypted-media;picture-in-picture;" allowfullscreen></iframe>
            </div>
            <div class="course-protected-box">🔒 Cette vidéo est protégée et accessible uniquement depuis votre compte.</div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});</script>
@endpush
