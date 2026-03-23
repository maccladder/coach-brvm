{{-- ════════ courses/index.blade.php ════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .courses-page { background:#060910;min-height:100vh; }
    .courses-hero { background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(201,168,76,.1) 0%,transparent 55%),#060910;border-bottom:1px solid rgba(201,168,76,.08);padding:48px 0 36px;position:relative;overflow:hidden; }
    .courses-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .courses-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#0FCFA4;display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .courses-hero-tag::before { content:'';width:28px;height:1px;background:#0FCFA4; }
    .courses-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:#E8EAF0;line-height:1.08;margin-bottom:10px; }
    .courses-hero-title em { font-style:italic;color:#C9A84C; }

    .course-card { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;overflow:hidden;height:100%;display:flex;flex-direction:column;transition:all .32s; }
    .course-card:hover { border-color:rgba(201,168,76,.2);transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.35); }
    .course-cover { position:relative;background:#121A2C;aspect-ratio:16/9;overflow:hidden; }
    .course-cover img { width:100%;height:100%;object-fit:cover;transition:transform .4s; }
    .course-card:hover .course-cover img { transform:scale(1.03); }
    .course-play { position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:54px;height:54px;background:rgba(0,0,0,.6);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;backdrop-filter:blur(4px);transition:all .25s; }
    .course-card:hover .course-play { background:rgba(201,168,76,.8);transform:translate(-50%,-50%) scale(1.1); }
    .course-badge-bought { position:absolute;top:10px;left:10px;font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;background:rgba(15,207,164,.9);color:#050810;padding:4px 10px;border-radius:100px; }
    .course-body { padding:22px;flex:1;display:flex;flex-direction:column; }
    .course-title { font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:#E8EAF0;margin-bottom:8px;line-height:1.35; }
    .course-desc { font-size:12.5px;color:#6B7590;line-height:1.65;flex:1;margin-bottom:16px; }
    .course-footer { display:flex;justify-content:space-between;align-items:center;padding-top:14px;border-top:1px solid rgba(255,255,255,.05); }
    .course-price { font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:#C9A84C; }
    .cb-btn-gold { display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:9px 16px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-green { display:inline-flex;align-items:center;gap:7px;background:rgba(15,207,164,.1);color:#0FCFA4 !important;font-family:'Syne',sans-serif;font-weight:700;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:8px 14px;border:1px solid rgba(15,207,164,.2);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-green:hover { background:rgba(15,207,164,.16);border-color:rgba(15,207,164,.4); }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:7px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:8px 14px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }
    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="courses-page">
    <div class="courses-hero">
        <div class="courses-hero-grid"></div>
        <div class="container" style="max-width:1100px;position:relative;z-index:1;">
            <p class="courses-hero-tag">Formations</p>
            <h1 class="courses-hero-title">🎓 Formations <em>Coach BRVM</em></h1>
            <p style="font-size:14px;color:#6B7590;font-weight:300;">Formations pratiques pour investir intelligemment à la BRVM.</p>
        </div>
    </div>
    <div class="container py-5" style="max-width:1100px;">
        @if(session('success'))<div style="background:rgba(15,207,164,.08);border:1px solid rgba(15,207,164,.2);border-radius:3px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#0FCFA4;font-family:'Syne',sans-serif;">{{ session('success') }}</div>@endif
        @if(session('error'))<div style="background:rgba(255,107,107,.08);border:1px solid rgba(255,107,107,.2);border-radius:3px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#FF6B6B;font-family:'Syne',sans-serif;">{{ session('error') }}</div>@endif

        <div class="row g-4 cbr">
            @forelse($courses as $course)
                @php
                    $isBought = auth()->check() && auth()->user()->coursePurchases()->where('course_id',$course->id)->whereNotNull('paid_at')->exists();
                    $covers = ['brvm-debutant'=>asset('courses/brvm-debutant.jpg'),'brvm-intermediaire'=>asset('courses/brvm-intermediaire.jpg'),'brvm-pratique-outils-analyse-portefeuille-virtuel'=>asset('courses/brvm-pratique.jpg')];
                    $cover = $covers[$course->slug] ?? asset('courses/brvm-debutant.jpg');
                @endphp
                <div class="col-md-4">
                    <div class="course-card">
                        <div class="course-cover">
                            <img src="{{ $cover }}" alt="{{ $course->title }}">
                            <div class="course-play">▶</div>
                            @if($isBought)<span class="course-badge-bought">✓ Acheté</span>@endif
                        </div>
                        <div class="course-body">
                            <div class="course-title">{{ $course->title }}</div>
                            <div class="course-desc">{{ \Illuminate\Support\Str::limit($course->description,130) }}</div>
                            <div class="course-footer">
                                <span class="course-price">{{ number_format($course->price_fcfa,0,',',' ') }} F</span>
                                @auth
                                    @if($isBought)
                                        <a href="{{ route('courses.show',$course->slug) }}" class="cb-btn-green">▶ Continuer</a>
                                    @else
                                        <form method="POST" action="{{ route('courses.buy.paystack',$course) }}" class="js-paystack-form">
                                            @csrf
                                            <button type="submit" class="cb-btn-gold js-init-checkout" data-course-id="{{ $course->id }}" data-course-title="{{ e($course->title) }}" data-course-price="{{ (int)$course->price_fcfa }}">Payer</button>
                                        </form>
                                    @endif
                                @else
                                    <a href="{{ route('login') }}" class="cb-btn-outline">Se connecter</a>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12"><div style="text-align:center;padding:80px;font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#6B7590;">Aucune formation disponible pour le moment.</div></div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('submit',function(e){const form=e.target.closest('.js-paystack-form');if(!form)return;const btn=form.querySelector('.js-init-checkout');if(!btn||typeof fbq!=='function')return;fbq('track','InitiateCheckout',{content_type:'product',content_ids:[String(btn.dataset.courseId)],content_name:btn.dataset.courseTitle||'',value:Number(btn.dataset.coursePrice||0),currency:'XOF',num_items:1});},true);
document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});
</script>
@endpush
