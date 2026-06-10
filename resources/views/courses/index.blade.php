{{-- ════════ courses/index.blade.php ════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .courses-page { background:var(--cb-paper);min-height:100vh; }
    .courses-hero { background:radial-gradient(ellipse 80% 50% at 50% 0%,rgba(176,134,46,.08) 0%,transparent 55%),var(--cb-paper);border-bottom:1px solid var(--cb-border);padding:48px 0 36px;position:relative;overflow:hidden; }
    .courses-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(176,134,46,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(176,134,46,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .courses-hero-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:var(--cb-forest);display:flex;align-items:center;gap:10px;margin-bottom:14px; }
    .courses-hero-tag::before { content:'';width:28px;height:1px;background:var(--cb-forest); }
    .courses-hero-title { font-family:'Playfair Display',serif;font-size:clamp(28px,5vw,48px);font-weight:900;color:var(--cb-ink);line-height:1.08;margin-bottom:10px; }
    .courses-hero-title em { font-style:italic;color:var(--cb-gold); }

    .course-card { background:var(--cb-card);border:1px solid var(--cb-border);border-radius:4px;overflow:hidden;height:100%;display:flex;flex-direction:column;transition:all .32s; }
    .course-card:hover { border-color:rgba(176,134,46,.2);transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,.08); }
    .course-cover { position:relative;background:var(--cb-paper);aspect-ratio:16/9;overflow:hidden; }
    .course-cover img { width:100%;height:100%;object-fit:cover;transition:transform .4s; }
    .course-card:hover .course-cover img { transform:scale(1.03); }
    .course-play { position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:54px;height:54px;background:rgba(0,0,0,.45);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;color:#fff;backdrop-filter:blur(4px);transition:all .25s; }
    .course-card:hover .course-play { background:rgba(176,134,46,.85);transform:translate(-50%,-50%) scale(1.1); }
    .course-badge-bought { position:absolute;top:10px;left:10px;font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;background:rgba(15,92,67,.9);color:#fff;padding:4px 10px;border-radius:100px; }
    .course-body { padding:22px;flex:1;display:flex;flex-direction:column; }
    .course-title { font-family:'Syne',sans-serif;font-size:15px;font-weight:700;color:var(--cb-ink);margin-bottom:8px;line-height:1.35; }
    .course-desc { font-size:12.5px;color:var(--cb-muted);line-height:1.65;flex:1;margin-bottom:16px; }
    .course-footer { display:flex;justify-content:space-between;align-items:center;padding-top:14px;border-top:1px solid var(--cb-border); }
    .course-price { font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--cb-gold); }
    .cb-btn-gold { display:inline-flex;align-items:center;gap:7px;background:linear-gradient(135deg,var(--cb-gold),#7A5412);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:11px;letter-spacing:.07em;text-transform:uppercase;padding:9px 16px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(176,134,46,.3);transform:translateY(-1px); }
    .cb-btn-green { display:inline-flex;align-items:center;gap:7px;background:rgba(15,92,67,.08);color:var(--cb-forest) !important;font-family:'Syne',sans-serif;font-weight:700;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:8px 14px;border:1px solid rgba(15,92,67,.2);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-green:hover { background:rgba(15,92,67,.14);border-color:rgba(15,92,67,.4); }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:7px;background:transparent;color:var(--cb-ink) !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:8px 14px;border:1px solid var(--cb-border);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:var(--cb-gold);color:var(--cb-gold) !important;background:rgba(176,134,46,.05); }
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
            <h1 class="courses-hero-title">🎓 Formations <em>Boursiv</em></h1>
            <p style="font-size:14px;color:var(--cb-muted);font-weight:300;">Formations pratiques pour investir intelligemment à la BRVM.</p>
        </div>
    </div>
    <div class="container py-5" style="max-width:1100px;">

        @if(session('success'))
        <div style="background:rgba(15,92,67,.06);border:1px solid rgba(15,92,67,.15);border-radius:3px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:var(--cb-forest);font-family:'Syne',sans-serif;">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div style="background:rgba(192,57,43,.06);border:1px solid rgba(192,57,43,.2);border-radius:3px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:var(--cb-down);font-family:'Syne',sans-serif;">{{ session('error') }}</div>
        @endif
        @if(session('warning'))
        <div style="background:rgba(176,134,46,.06);border:1px solid rgba(176,134,46,.2);border-radius:3px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:var(--cb-gold);font-family:'Syne',sans-serif;">{!! session('warning') !!}</div>
        @endif

        {{-- Champ code apporteur global --}}
        @php $cookieCode = strtoupper(trim(request()->cookie('affiliate_code', ''))); @endphp
        @if(!auth()->user()?->coursePurchases()->count())
        <div style="margin-bottom:20px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                <input type="text"
                       id="global-affiliate-code"
                       value="{{ old('affiliate_code', $cookieCode) }}"
                       placeholder="Code apporteur (optionnel, −10% si éligible)"
                       maxlength="20"
                       style="background:var(--cb-paper);border:1px solid var(--cb-border);color:var(--cb-ink);border-radius:3px;font-family:'DM Sans',sans-serif;font-size:12px;padding:9px 14px;width:240px;outline:none;">
                <span style="font-size:11px;color:var(--cb-muted);">Appliqué à l'achat si éligible</span>
            </div>
            <div id="courses-affiliate-preview" style="min-height:16px;font-size:12px;"></div>
        </div>
        @endif

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
                                            <input type="hidden" name="affiliate_code" class="js-affiliate-code-hidden" value="{{ $cookieCode }}">
                                            <button type="submit"
                                                    class="cb-btn-gold js-init-checkout"
                                                    data-course-id="{{ $course->id }}"
                                                    data-course-title="{{ e($course->title) }}"
                                                    data-course-price="{{ (int)$course->price_fcfa }}">
                                                Payer
                                            </button>
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
                <div class="col-12"><div style="text-align:center;padding:80px;font-family:'Syne',sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:var(--cb-muted);">Aucune formation disponible pour le moment.</div></div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.cbr').forEach(function(el) {
        new IntersectionObserver(function([e]) {
            if (e.isIntersecting) el.classList.add('on');
        }, { threshold: .06 }).observe(el);
    });

    var globalInput = document.getElementById('global-affiliate-code');
    if (globalInput) {
        globalInput.addEventListener('input', function() {
            var val = this.value.toUpperCase().trim();
            document.querySelectorAll('.js-affiliate-code-hidden').forEach(function(h) { h.value = val; });
        });
        document.querySelectorAll('.js-paystack-form').forEach(function(f) {
            f.addEventListener('submit', function() {
                var h = f.querySelector('.js-affiliate-code-hidden');
                if (h && globalInput) h.value = globalInput.value.toUpperCase().trim();
            });
        });
    }

    document.querySelectorAll('.js-init-checkout').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (btn.dataset.fired === '1') return;
            btn.dataset.fired = '1';
            e.preventDefault();
            var form = btn.closest('form');
            if (typeof fbq === 'function') {
                fbq('track', 'InitiateCheckout', {
                    content_type: 'product',
                    content_ids: [String(btn.dataset.courseId)],
                    content_name: btn.dataset.courseTitle || '',
                    value: Number(btn.dataset.coursePrice || 0),
                    currency: 'XOF',
                    num_items: 1
                });
            }
            setTimeout(function() { form.submit(); }, 400);
        });
    });

});
</script>

@auth
<script>
(function () {
    const VALIDATE = '{{ route("affiliate.validate-code") }}';
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const globalIn = document.getElementById('global-affiliate-code');
    const preview  = document.getElementById('courses-affiliate-preview');

    if (!globalIn) return;

    function fmt(n) { return new Intl.NumberFormat('fr-FR').format(n); }

    function validateForCourse(code, btn, priceSpan) {
        const courseId    = parseInt(btn.dataset.courseId, 10);
        const coursePrice = parseInt(btn.dataset.coursePrice, 10);
        if (!courseId || !coursePrice) return;

        fetch(VALIDATE, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ code, product_type: 'course', product_id: courseId }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.applicable) {
                priceSpan.innerHTML =
                    '<span style="text-decoration:line-through;color:var(--cb-muted);font-size:.8em;">'
                    + fmt(coursePrice) + ' F</span> '
                    + '<span style="color:var(--cb-gold);">' + fmt(data.new_price) + '</span>';
                priceSpan.dataset.discounted = '1';
            } else {
                priceSpan.innerHTML = fmt(coursePrice) + ' F';
                priceSpan.dataset.discounted = '';
            }
        })
        .catch(() => {
            priceSpan.innerHTML = fmt(coursePrice) + ' F';
        });
    }

    function validateAll(code) {
        let anyPreviewSet = false;
        document.querySelectorAll('.js-paystack-form').forEach(function (form) {
            const btn       = form.querySelector('.js-init-checkout');
            const priceSpan = form.closest('.course-body')?.querySelector('.course-price');
            if (!btn || !priceSpan) return;

            if (code.length < 2) {
                const orig = parseInt(btn.dataset.coursePrice, 10);
                if (orig) priceSpan.innerHTML = fmt(orig) + ' F';
                return;
            }
            validateForCourse(code, btn, priceSpan);
            anyPreviewSet = true;
        });

        if (preview) {
            if (code.length < 2) { preview.innerHTML = ''; }
            else if (!anyPreviewSet) { preview.innerHTML = ''; }
        }
    }

    function setGlobalMessage(data, code) {
        if (!preview) return;
        if (!data) { preview.innerHTML = ''; return; }
        if (data.applicable) {
            preview.innerHTML = '<span style="color:var(--cb-forest);">✅ Code valide — −'
                + Math.round(data.discount_rate * 100) + '% sur les formations éligibles</span>';
        } else if (data.reason === 'self_referral') {
            preview.innerHTML = '<span style="color:var(--cb-gold);">⚠️ ' + data.message + '</span>';
        } else {
            preview.innerHTML = '<span style="color:var(--cb-gold);">⚠️ ' + (data.message || 'Code invalide ou non éligible.') + '</span>';
        }
    }

    let timer;
    function onCodeChange() {
        const code = globalIn.value.trim().toUpperCase();
        document.querySelectorAll('.js-affiliate-code-hidden').forEach(h => { h.value = code; });

        clearTimeout(timer);
        timer = setTimeout(() => {
            if (code.length < 2) { validateAll(''); setGlobalMessage(null, ''); return; }

            const firstBtn = document.querySelector('.js-paystack-form .js-init-checkout');
            if (firstBtn) {
                fetch(VALIDATE, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ code, product_type: 'course', product_id: parseInt(firstBtn.dataset.courseId, 10) }),
                })
                .then(r => r.json())
                .then(data => {
                    setGlobalMessage(data, code);
                    validateAll(code);
                })
                .catch(() => { setGlobalMessage(null, code); validateAll(code); });
            } else {
                validateAll(code);
            }
        }, 400);
    }

    globalIn.addEventListener('input', onCodeChange);
    globalIn.addEventListener('blur',  onCodeChange);
    if (globalIn.value.trim().length >= 2) onCodeChange();
})();
</script>
@endauth
@endpush
