@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1100px;">

  {{-- Bandeau haut : avatar + titre + bouton audio --}}
  <div class="card shadow-sm mb-4 border-0">
    <div class="card-body d-flex align-items-center flex-wrap gap-3">

      {{-- Coach + titre --}}
      <div class="d-flex align-items-center gap-3 flex-grow-1">
        <img src="{{ asset('avatars/coach.png') }}"
             alt="Coach BRVM"
             class="rounded-circle border shadow-sm"
             style="width:70px;height:70px;object-fit:cover;">
        <div>
          <h4 class="mb-1">{{ $boc->title }}</h4>
          <div class="text-muted small">
            BOC du {{ optional($boc->boc_date)->format('d/m/Y') }} · Coach BRVM
          </div>
        </div>
      </div>

      {{-- Bouton audio --}}
      @if(!empty($audioPath))
        <button id="playAudioBtn" class="btn btn-primary ms-auto d-flex align-items-center gap-2">
          <span>🔊</span>
          <span>Me lire l’analyse</span>
        </button>
      @endif
    </div>
  </div>

  {{-- Audio généré --}}
  @if(!empty($audioPath))
    <audio
      id="summaryAudio"
      src="{{ asset('storage/' . $audioPath) }}"
      preload="auto">
    </audio>
  @endif

  {{-- SI L’ANALYSE N’EST PAS ENCORE PRÊTE --}}
  @if(!$boc->interpreted_markdown)
    <div class="d-flex flex-column align-items-center justify-content-center py-5">
      <img src="{{ asset('img/loader-dots.gif') }}" alt="Chargement..."
           style="width:120px;max-width:100%;" class="mb-3">

      <h5 class="mb-2 text-center">L’IA analyse ton BOC…</h5>
      <p class="text-muted text-center small mb-0">
        Cela prend généralement moins d’une minute.<br>
        Laisse simplement cette page ouverte, nous actualiserons le résultat automatiquement.
      </p>
    </div>

    <script>
      setTimeout(() => window.location.reload(), 8000);
    </script>

  @else

    {{-- L’analyse est prête --}}
    <div class="row g-4">

      {{-- Colonne gauche : avatar vidéo --}}
      @if($boc->avatar_video_url)
        <div class="col-lg-5">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-white border-0 pb-0">
              <h6 class="mb-1">🎥 Avatar vidéo</h6>
              <small class="text-muted">
                Le coach commente ton BOC
              </small>
            </div>
            <div class="card-body text-center">
              <video
                src="{{ $boc->avatar_video_url }}"
                controls
                playsinline
                style="max-width: 100%; border-radius: 12px;">
              </video>

              {{-- Bouton bulles --}}
              <button id="btn-bubbles"
                      class="btn btn-outline-primary w-100 mt-3">
                  👁️ Voir le marché en un coup d’œil
              </button>
            </div>
          </div>
        </div>
      @endif

      {{-- Colonne droite : analyse texte --}}
      <div class="{{ $boc->avatar_video_url ? 'col-lg-7' : 'col-12' }}">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white border-0">
            <h6 class="mb-1">📝 Interprétation détaillée</h6>
            <small class="text-muted">
              Analyse IA de ton BOC + conseils de lecture
            </small>
          </div>

          <div class="card-body">
            <pre class="p-3 bg-light rounded border small mb-0"
                 style="white-space:pre-wrap; font-family: 'JetBrains Mono','Fira Code',monospace;">
{{ $boc->interpreted_markdown }}
            </pre>
          </div>
        </div>
      </div>
    </div>

    {{-- SECTION : bulles BRVM --}}
    <div class="mt-4" id="bubbles-wrapper" style="display:none;">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
          <div>
            <h6 class="mb-1">🌐 Vue d’ensemble du marché</h6>
            <small class="text-muted">
              Variations du jour des actions BRVM (d’après ton BOC)
            </small>
          </div>
          <button id="btn-bubbles-fullscreen"
                  class="btn btn-sm btn-outline-secondary">
            ⛶ Plein écran
          </button>
        </div>
        <div class="card-body">
          <div id="brvm-bubbles"
               style="width:100%;height:80vh;min-height:650px;background:#111;border-radius:12px;overflow:hidden;">
          </div>
        </div>
      </div>
    </div>
  @endif
</div>

{{-- Scripts --}}
@if($boc->interpreted_markdown)
  <script src="https://d3js.org/d3.v7.min.js"></script>
@endif

<script>
document.addEventListener('DOMContentLoaded', () => {

  // === AUDIO ===
  const btnAudio = document.getElementById('playAudioBtn');
  const audio    = document.getElementById('summaryAudio');
  if (btnAudio && audio) {
    let isPlaying = false;

    btnAudio.addEventListener('click', () => {
      if (!isPlaying) {
        audio.play();
      } else {
        audio.pause();
      }
    });

    audio.addEventListener('play', () => {
      isPlaying = true;
      btnAudio.innerHTML = '<span>⏸️</span><span>Mettre en pause</span>';
    });

    audio.addEventListener('pause', () => {
      isPlaying = false;
      btnAudio.innerHTML = '<span>🔊</span><span>Me lire l’analyse</span>';
    });

    audio.addEventListener('ended', () => {
      isPlaying = false;
      btnAudio.innerHTML = '<span>🔊</span><span>Me lire l’analyse</span>';
    });
  }

  // === BULLES BRVM ===
  const btnBubbles   = document.getElementById('btn-bubbles');
  const wrapper      = document.getElementById('bubbles-wrapper');
  const fullscreenBtn = document.getElementById('btn-bubbles-fullscreen');
  const bubblesDiv   = document.getElementById('brvm-bubbles');

  // on gardera en mémoire la dernière data pour redessiner
  let loaded   = false;
  let lastData = null;

  if (btnBubbles && wrapper && window.d3) {

    btnBubbles.addEventListener('click', () => {
      if (!loaded) {
        fetch('{{ route('client-bocs.bubbles', $boc) }}')
          .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
          })
          .then(payload => {
            console.log('Bubbles payload:', payload);

            const data = Array.isArray(payload) ? payload : (payload.data || []);
            lastData = data;
            wrapper.style.display = 'block';
            drawBubbles(data);
            loaded = true;
            btnBubbles.innerHTML = '🔁 Recharger la vue du marché';
          })
          .catch(err => {
            console.error('Bubbles fetch error:', err);
            alert('Impossible de charger les données du marché.');
          });
      } else {
        // si déjà chargé, on toggle simplement l’affichage
        wrapper.style.display =
          (wrapper.style.display === 'none') ? 'block' : 'none';
      }
    });

    // === FULLSCREEN ===
    if (fullscreenBtn && bubblesDiv) {
      fullscreenBtn.addEventListener('click', () => {
        if (!document.fullscreenElement) {
          if (bubblesDiv.requestFullscreen) {
            bubblesDiv.requestFullscreen();
          }
        } else {
          if (document.exitFullscreen) {
            document.exitFullscreen();
          }
        }
      });

      document.addEventListener('fullscreenchange', () => {
        const isFs = !!document.fullscreenElement;
        fullscreenBtn.textContent = isFs
          ? '❌ Quitter le plein écran'
          : '⛶ Plein écran';

        // quand la taille change radicalement (entrée / sortie FS),
        // on redessine le graphe pour l’adapter
        if (lastData) {
          drawBubbles(lastData);
        }
      });

      // optionnel : redessiner si l’utilisateur redimensionne la fenêtre
      window.addEventListener('resize', () => {
        if (lastData && wrapper.style.display !== 'none') {
          drawBubbles(lastData);
        }
      });
    }

    // === Fonction de dessin des bulles (avec animation + drag) ===
    function drawBubbles(data) {
      const container = document.getElementById('brvm-bubbles');
      container.innerHTML = '';

      if (!Array.isArray(data) || data.length === 0) {
        container.innerHTML = '<p class="text-white p-3">Aucune donnée de marché disponible.</p>';
        return;
      }

      const width  = container.clientWidth;
      const height = container.clientHeight || 600;

      const svg = d3.select('#brvm-bubbles')
        .append('svg')
        .attr('width', width)
        .attr('height', height);

      const maxAbsChange = d3.max(data, d => Math.abs(d.change)) || 1;

      const radiusScale = d3.scaleSqrt()
        .domain([0, maxAbsChange])
        .range(data.length >= 40 ? [20, 90] : [30, 120]);

      const colorFn = d => {
        if (d.change > 0.1)  return '#1fbf4a';
        if (d.change < -0.1) return '#e53935';
        return '#555555';
      };

      const nodes = data.map(d => ({
        ...d,
        radius: radiusScale(Math.abs(d.change)),
        x: width  / 2 + (Math.random() - 0.5) * 50,
        y: height / 2 + (Math.random() - 0.5) * 50
      }));

      const nodeGroup = svg.append('g');

      const node = nodeGroup.selectAll('g.node')
        .data(nodes)
        .enter()
        .append('g')
        .attr('class', 'node')
        .style('cursor', 'grab')
        .call(
          d3.drag()
            .on('start', dragstarted)
            .on('drag', dragged)
            .on('end', dragended)
        );

      // Cercle
      node.append('circle')
        .attr('r', d => d.radius)
        .attr('fill', d => colorFn(d))
        .attr('stroke', '#ffffff')
        .attr('stroke-width', 2)
        .attr('fill-opacity', 0.85);

      // Ticker
      node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '-0.2em')
        .style('fill', '#ffffff')
        .style('font-weight', 'bold')
        .style('pointer-events', 'none')
        .style('font-size', d => Math.max(12, d.radius / 3) + 'px')
        .text(d => d.ticker || d.label);

      // Variation
      node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '1.2em')
        .style('fill', '#ffffff')
        .style('pointer-events', 'none')
        .style('font-size', d => Math.max(10, d.radius / 4) + 'px')
        .text(d => `${d.change > 0 ? '+' : ''}${d.change.toFixed(1)} %`);

      // Tooltip
      node.append('title')
        .text(d => `${d.name} (${d.ticker})\nVariation jour : ${d.change.toFixed(2)} %`);

      // Simulation D3 (animation + collisions)
      const simulation = d3.forceSimulation(nodes)
        .force('center', d3.forceCenter(width / 2, height / 2))
        .force('charge', d3.forceManyBody().strength(10))
        .force('collision', d3.forceCollide().radius(d => d.radius + 4))
        .force('x', d3.forceX(width / 2).strength(0.02))
        .force('y', d3.forceY(height / 2).strength(0.02))
        .alpha(1)
        .alphaDecay(0.02)
        .on('tick', () => {
          node.attr('transform', d => `translate(${d.x},${d.y})`);
        });

      function dragstarted(event, d) {
        if (!event.active) simulation.alphaTarget(0.3).restart();
        d.fx = d.x;
        d.fy = d.y;
      }

      function dragged(event, d) {
        d.fx = event.x;
        d.fy = event.y;
      }

      function dragended(event, d) {
        if (!event.active) simulation.alphaTarget(0);
        d.fx = null;
        d.fy = null;
      }
    }
  }
});
</script>
@endsection
