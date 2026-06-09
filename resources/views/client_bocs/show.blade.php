{{-- resources/views/client_bocs/show.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .boc-page { background: #060910; min-height: 100vh; }

    /* ══ Hero ══ */
    .boc-hero {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 32px 0 28px; position: relative; overflow: hidden;
    }
    .boc-hero-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }

    .boc-avatar-img { width:64px;height:64px;border-radius:50%;border:2px solid rgba(201,168,76,.3);object-fit:cover;flex-shrink:0; }
    .boc-title { font-family:'Playfair Display',serif;font-size:clamp(20px,3.5vw,30px);font-weight:900;color:#E8EAF0;margin-bottom:4px; }
    .boc-meta { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:#6B7590; }

    .boc-badge-ok   { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 12px;border-radius:100px;background:rgba(15,207,164,.1);color:#0FCFA4;border:1px solid rgba(15,207,164,.2); }
    .boc-badge-wait { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 12px;border-radius:100px;background:rgba(255,200,30,.08);color:#FFC850;border:1px solid rgba(255,200,30,.2); }

    /* Boutons hero */
    .cb-btn-audio { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:11px 22px;border:none;border-radius:3px;cursor:pointer;transition:all .3s; }
    .cb-btn-audio:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-pdf { display:inline-flex;align-items:center;gap:8px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:12px;letter-spacing:.06em;text-transform:uppercase;padding:10px 20px;border:1px solid rgba(255,255,255,.15);border-radius:3px;cursor:pointer;transition:all .3s; }
    .cb-btn-pdf:hover { border-color:#C9A84C;color:#C9A84C !important; }

    /* ══ Cards ══ */
    .boc-card { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;overflow:hidden;height:100%; }
    .boc-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,transparent); }
    .boc-card-header { background:#121A2C;border-bottom:1px solid rgba(255,255,255,.05);padding:14px 20px; }
    .boc-card-title { font-family:'Syne',sans-serif;font-size:12px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#C9A84C;margin-bottom:2px; }
    .boc-card-sub { font-size:12px;color:#6B7590; }
    .boc-card-body { padding:20px; }

    /* ══ Vidéo ══ */
    .boc-video { width:100%;border-radius:6px;background:#000;display:block; }

    /* ══ Analyse texte ══ */
    .boc-analysis-pre {
        background: rgba(6,9,16,.8);
        border: 1px solid rgba(255,255,255,.06);
        border-radius: 4px;
        padding: 20px;
        color: #9AA3B8;
        font-family: 'JetBrains Mono','Fira Code','DM Mono',monospace;
        font-size: 13px;
        line-height: 1.8;
        white-space: pre-wrap;
        margin: 0;
        max-height: 600px;
        overflow-y: auto;
    }

    /* ══ Bulles ══ */
    .boc-bubbles-card { background:#0C1120;border:1px solid rgba(255,255,255,.06);border-radius:4px;overflow:hidden;margin-top:24px; }
    .boc-bubbles-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,rgba(15,207,164,.6),transparent); }
    .boc-bubbles-header { background:#121A2C;border-bottom:1px solid rgba(255,255,255,.05);padding:14px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px; }

    .cb-btn-fullscreen { display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.04);color:#6B7590 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:10px;letter-spacing:.08em;text-transform:uppercase;padding:7px 14px;border:1px solid rgba(255,255,255,.1);border-radius:3px;cursor:pointer;transition:all .25s; }
    .cb-btn-fullscreen:hover { border-color:rgba(201,168,76,.3);color:#C9A84C !important; }

    /* ── Style toggle (identique radar) ── */
    .style-toggle {
        display: inline-flex;
        background: rgba(255,255,255,.04);
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 4px;
        overflow: hidden;
    }
    .style-toggle-btn {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 6px 12px;
        font-family: 'Syne', sans-serif; font-size: 10px;
        font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
        color: #6B7590; background: transparent; border: none;
        cursor: pointer; transition: all .2s;
    }
    .style-toggle-btn:hover { color: #E8EAF0; background: rgba(255,255,255,.04); }
    .style-toggle-btn.active {
        color: #C9A84C;
        background: rgba(201,168,76,.1);
        border-left: 1px solid rgba(201,168,76,.2);
        border-right: 1px solid rgba(201,168,76,.2);
    }
    .style-toggle-btn:first-child { border-right: 1px solid rgba(255,255,255,.06); }

    /* Bulle container */
    #brvm-bubbles {
        width: 100%; height: 80vh; min-height: 650px;
        background: #060910; border-radius: 6px; overflow: hidden;
        border: 1px solid rgba(255,255,255,.04);
        transition: background .3s;
    }
    #brvm-bubbles.mode-crypto { background: #000; }
    #brvm-bubbles.mode-crypto:fullscreen { background: #000; padding: 12px; }

    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
    .cbr2 { transition-delay:.08s; }
    .cbr3 { transition-delay:.16s; }
</style>
@endpush

@section('content')
<div class="boc-page">

    {{-- ══ HERO ══ --}}
    <div class="boc-hero">
        <div class="boc-hero-grid"></div>
        <div class="container py-2" style="max-width:1100px;position:relative;z-index:1;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

                {{-- Titre + meta --}}
                <div class="d-flex align-items-center gap-3">
                    <img src="{{ asset('avatars/coach.png') }}" alt="Boursiv" class="boc-avatar-img">
                    <div>
                        <h1 class="boc-title">{{ $boc->title }}</h1>
                        <div class="boc-meta">BOC du {{ optional($boc->boc_date)->format('d/m/Y') }} · Boursiv</div>
                        <div style="margin-top:8px;">
                            @if(!empty($boc->interpreted_markdown))
                                <span class="boc-badge-ok">✅ Analyse prête</span>
                            @elseif($boc->status === 'failed')
                                <span class="boc-badge-wait" style="background:rgba(255,107,107,.1);color:#FF6B6B;border-color:rgba(255,107,107,.2);">⚠️ Analyse indisponible</span>
                            @elseif($boc->status === 'processing')
                                <span class="boc-badge-wait">⏳ Génération en cours…</span>
                            @else
                                <span class="boc-badge-wait">⏳ Analyse en cours…</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Boutons --}}
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if(!empty($audioPath))
                        <button id="playAudioBtn" class="cb-btn-audio">
                            🔊 Me lire l'analyse
                        </button>
                    @endif
                    <form id="pdfForm" method="POST" action="{{ route('client-bocs.pdf', $boc) }}">
                        @csrf
                        <input type="hidden" name="chart_image" id="chartImageInput">
                        <button type="button" id="btn-download-pdf" class="cb-btn-pdf">
                            📄 Télécharger le PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Audio caché --}}
    @if(!empty($audioPath))
        <audio id="summaryAudio" src="{{ asset('storage/' . $audioPath) }}" preload="auto"></audio>
    @endif

    <div class="container py-4" style="max-width:1100px;">

        {{-- ══ VIDÉO + ANALYSE ══ --}}
        <div class="row g-4 cbr">

            {{-- Vidéo avatar --}}
            @if($boc->avatar_video_url)
                <div class="col-lg-5">
                    <div class="boc-card">
                        <div class="boc-card-header">
                            <div class="boc-card-title">🎥 Avatar vidéo</div>
                            <div class="boc-card-sub">Le coach commente ton BOC</div>
                        </div>
                        <div class="boc-card-body d-flex align-items-center justify-content-center">
                            <video class="boc-video"
                                   src="{{ $boc->avatar_video_url }}"
                                   controls
                                   playsinline>
                            </video>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Analyse texte --}}
            <div class="{{ $boc->avatar_video_url ? 'col-lg-7' : 'col-12' }}">
                <div class="boc-card">
                    <div class="boc-card-header">
                        <div class="boc-card-title">📝 Interprétation détaillée</div>
                        <div class="boc-card-sub">Analyse IA · conseils de lecture</div>
                    </div>
                    <div class="boc-card-body">
                        @if(!empty($boc->interpreted_markdown))
                            <pre class="boc-analysis-pre">{{ $boc->interpreted_markdown }}</pre>
                        @elseif($boc->status === 'failed')
                            <div style="padding:24px;text-align:center;color:#FF6B6B;font-family:'Syne',sans-serif;font-size:13px;line-height:1.7;">
                                ⚠️ L'analyse IA n'a pas pu être générée pour ce BOC.<br>
                                <span style="color:#6B7590;font-size:12px;">Contactez le support ou revenez plus tard.</span>
                            </div>
                        @elseif($boc->status === 'processing')
                            <div style="padding:24px;text-align:center;color:#C9A84C;font-family:'Syne',sans-serif;font-size:13px;line-height:1.7;">
                                ⏳ Analyse IA en cours de génération…<br>
                                <span style="color:#6B7590;font-size:12px;">Cette page se rafraîchit automatiquement dans quelques secondes.</span>
                            </div>
                        @else
                            <div style="padding:24px;text-align:center;color:#6B7590;font-family:'Syne',sans-serif;font-size:13px;line-height:1.7;">
                                ⏳ Analyse en cours de génération… Rafraîchis dans quelques secondes.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>

        {{-- ══ BULLES BRVM ══ --}}
        <div class="boc-bubbles-card cbr cbr2" id="bubbles-wrapper">
            <div class="boc-bubbles-header">
                <div>
                    <div class="boc-card-title">🌐 Vue d'ensemble du marché</div>
                    <div class="boc-card-sub">Variations du jour des actions BRVM</div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">

                    {{-- Toggle style — identique au radar ── --}}
                    <div class="style-toggle" title="Changer le style des bulles">
                        <button class="style-toggle-btn active" id="btn-style-solid" title="Bulles solides">
                            <i class="bi bi-circle-fill" style="font-size:10px;"></i> Solide
                        </button>
                        <button class="style-toggle-btn" id="btn-style-crypto" title="Style CryptoBubbles">
                            <i class="bi bi-circle" style="font-size:10px;"></i> Crypto
                        </button>
                    </div>

                    <button id="btn-bubbles-fullscreen" class="cb-btn-fullscreen">
                        ⛶ Plein écran
                    </button>
                </div>
            </div>
            <div style="padding:16px;">
                <div id="brvm-bubbles"></div>
            </div>
        </div>

    </div>
</div>

{{-- Scripts --}}
@if($boc->interpreted_markdown)
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {

    @if(empty($boc->interpreted_markdown) && $boc->status !== 'failed')
        setTimeout(() => window.location.reload(), 8000);
    @endif

    // ══ AUDIO ══
    const btnAudio = document.getElementById('playAudioBtn');
    const audio    = document.getElementById('summaryAudio');
    if (btnAudio && audio) {
        let isPlaying = false;
        btnAudio.addEventListener('click', () => {
            isPlaying ? audio.pause() : audio.play();
        });
        audio.addEventListener('play',  () => { isPlaying = true;  btnAudio.innerHTML = '⏸️ Mettre en pause'; });
        audio.addEventListener('pause', () => { isPlaying = false; btnAudio.innerHTML = '🔊 Me lire l\'analyse'; });
        audio.addEventListener('ended', () => { isPlaying = false; btnAudio.innerHTML = '🔊 Me lire l\'analyse'; });
    }

    // ══ BULLES ══
    const bubblesDiv    = document.getElementById('brvm-bubbles');
    const fullscreenBtn = document.getElementById('btn-bubbles-fullscreen');
    const btnSolid      = document.getElementById('btn-style-solid');
    const btnCrypto     = document.getElementById('btn-style-crypto');

    let lastData     = null;
    let currentStyle = 'solid'; // 'solid' | 'crypto'

    /* ── Fonctions couleur selon style ── */
    function colorFn(d) {
        const c = Number(d.change ?? 0);
        if (currentStyle === 'crypto') return 'rgba(0,0,0,0)';
        if (c > 0.1)  return '#1fbf4a';
        if (c < -0.1) return '#e53935';
        return '#374151';
    }
    function strokeFn(d) {
        const c = Number(d.change ?? 0);
        if (c > 0.1)  return '#1fbf4a';
        if (c < -0.1) return '#e53935';
        return '#555';
    }
    function fillOpacityFn(d) {
        if (currentStyle === 'crypto') {
            return Math.min(0.18, Math.abs(Number(d.change ?? 0)) * 0.03);
        }
        return 0.88;
    }
    function textColorFn(d) {
        const c = Number(d.change ?? 0);
        if (currentStyle === 'crypto') {
            if (c > 0.1)  return '#1fbf4a';
            if (c < -0.1) return '#e53935';
            return '#aaa';
        }
        return '#fff';
    }
    function subTextColorFn(d) {
        const c = Number(d.change ?? 0);
        if (currentStyle === 'crypto') {
            if (c > 0.1)  return 'rgba(31,191,74,.9)';
            if (c < -0.1) return 'rgba(229,57,53,.9)';
            return 'rgba(170,170,170,.8)';
        }
        return 'rgba(255,255,255,.8)';
    }

    /* ── Draw ── */
    function drawBubbles(data) {
        bubblesDiv.innerHTML = '';
        bubblesDiv.classList.toggle('mode-crypto', currentStyle === 'crypto');

        if (!Array.isArray(data) || data.length === 0) {
            bubblesDiv.innerHTML = '<p style="color:#6B7590;padding:24px;font-family:\'Syne\',sans-serif;font-size:12px;">Aucune donnée disponible.</p>';
            return;
        }

        const width  = bubblesDiv.clientWidth  || 800;
        const height = bubblesDiv.clientHeight || 600;

        const svg = d3.select('#brvm-bubbles').append('svg').attr('width', width).attr('height', height);

        // Fond dégradé en mode crypto
        if (currentStyle === 'crypto') {
            const defs = svg.append('defs');
            const grad = defs.append('radialGradient').attr('id', 'bgGrad').attr('cx','50%').attr('cy','50%').attr('r','60%');
            grad.append('stop').attr('offset','0%').attr('stop-color','#0a0a14');
            grad.append('stop').attr('offset','100%').attr('stop-color','#000');
            svg.append('rect').attr('width', width).attr('height', height).attr('fill','url(#bgGrad)');
        }

        const maxAbsChange = d3.max(data, d => Math.abs(Number(d.change ?? 0))) || 1;
        const radiusScale  = d3.scaleSqrt().domain([0, maxAbsChange]).range(data.length >= 40 ? [20, 90] : [30, 120]);

        const nodes = data.map(d => ({
            ...d,
            change: Number(d.change ?? 0),
            radius: Math.max(22, radiusScale(Math.abs(Number(d.change ?? 0)))),
            x: width/2 + (Math.random()-.5)*50,
            y: height/2 + (Math.random()-.5)*50,
        }));

        const node = svg.append('g').selectAll('g.node').data(nodes).enter().append('g')
            .attr('class','node').style('cursor','grab')
            .call(d3.drag()
                .on('start',(e,d)=>{ if(!e.active) sim.alphaTarget(.3).restart(); d.fx=d.x; d.fy=d.y; })
                .on('drag', (e,d)=>{ d.fx=e.x; d.fy=e.y; })
                .on('end',  (e,d)=>{ if(!e.active) sim.alphaTarget(0); d.fx=null; d.fy=null; })
            );

        // Halo en mode crypto
        if (currentStyle === 'crypto') {
            node.append('circle')
                .attr('r', d => d.radius + 4)
                .attr('fill', 'none')
                .attr('stroke', d => strokeFn(d))
                .attr('stroke-width', 0.5)
                .attr('stroke-opacity', 0.2);
        }

        node.append('circle')
            .attr('r', d => d.radius)
            .attr('fill', d => colorFn(d))
            .attr('stroke', d => strokeFn(d))
            .attr('stroke-width', currentStyle === 'crypto' ? 2 : 1.5)
            .attr('fill-opacity', d => fillOpacityFn(d));

        node.append('text')
            .attr('text-anchor','middle').attr('dy','-.2em')
            .style('fill', d => textColorFn(d))
            .style('font-weight','700').style('pointer-events','none')
            .style('font-size', d => Math.max(11, d.radius/3) + 'px')
            .style('font-family','Syne,sans-serif')
            .text(d => d.ticker || d.label || '');

        node.append('text')
            .attr('text-anchor','middle').attr('dy','1.2em')
            .style('fill', d => subTextColorFn(d))
            .style('font-weight', currentStyle === 'crypto' ? '600' : '500')
            .style('pointer-events','none')
            .style('font-size', d => Math.max(9, d.radius/4) + 'px')
            .text(d => `${d.change >= 0 ? '+' : ''}${d.change.toFixed(1)}%`);

        node.append('title')
            .text(d => `${d.name || d.ticker} (${d.ticker})\nVariation : ${d.change.toFixed(2)}%`);

        const sim = d3.forceSimulation(nodes)
            .force('center',    d3.forceCenter(width/2, height/2))
            .force('charge',    d3.forceManyBody().strength(10))
            .force('collision', d3.forceCollide().radius(d => d.radius + 4))
            .force('x',         d3.forceX(width/2).strength(.02))
            .force('y',         d3.forceY(height/2).strength(.02))
            .alpha(1).alphaDecay(.02)
            .on('tick', () => node.attr('transform', d => `translate(${d.x},${d.y})`));
    }

    /* ── Toggle ── */
    btnSolid?.addEventListener('click', () => {
        currentStyle = 'solid';
        btnSolid.classList.add('active');
        btnCrypto.classList.remove('active');
        if (lastData) drawBubbles(lastData);
    });
    btnCrypto?.addEventListener('click', () => {
        currentStyle = 'crypto';
        btnCrypto.classList.add('active');
        btnSolid.classList.remove('active');
        if (lastData) drawBubbles(lastData);
    });

    /* ── Fetch data ── */
    if (bubblesDiv && window.d3) {
        fetch('{{ route('client-bocs.bubbles', $boc) }}')
            .then(r => { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
            .then(payload => {
                const data = Array.isArray(payload) ? payload : (payload.data || []);
                lastData = data;
                drawBubbles(data);
            })
            .catch(() => {
                bubblesDiv.innerHTML = '<p style="color:#6B7590;padding:24px;font-family:\'Syne\',sans-serif;font-size:12px;letter-spacing:.08em;text-transform:uppercase;">Impossible de charger les données du marché.</p>';
            });
    }

    /* ── Fullscreen ── */
    fullscreenBtn?.addEventListener('click', () => {
        document.fullscreenElement ? document.exitFullscreen() : bubblesDiv.requestFullscreen?.();
    });
    document.addEventListener('fullscreenchange', () => {
        const isFs = !!document.fullscreenElement;
        fullscreenBtn.textContent = isFs ? '❌ Quitter' : '⛶ Plein écran';
        if (lastData) setTimeout(() => drawBubbles(lastData), 100);
    });
    window.addEventListener('resize', () => { if (lastData) drawBubbles(lastData); });

    // ══ PDF ══
    const pdfBtn     = document.getElementById('btn-download-pdf');
    const pdfForm    = document.getElementById('pdfForm');
    const chartInput = document.getElementById('chartImageInput');
    if (pdfBtn && pdfForm && chartInput && bubblesDiv && window.html2canvas) {
        pdfBtn.addEventListener('click', async () => {
            try {
                pdfBtn.disabled = true;
                pdfBtn.textContent = '⏳ Génération…';
                const canvas = await html2canvas(bubblesDiv, { backgroundColor: '#060910' });
                chartInput.value = canvas.toDataURL('image/png');
                pdfForm.submit();
            } catch(e) {
                alert("Impossible de préparer le PDF. Réessaie.");
                pdfBtn.disabled = false;
                pdfBtn.textContent = '📄 Télécharger le PDF';
            }
        });
    }

    // Anims
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) el.classList.add('on'); }, { threshold: .06 }).observe(el);
    });
});
</script>
@endsection
