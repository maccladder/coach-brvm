@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1100px;">

  {{-- Bandeau haut : avatar + titre + boutons audio + PDF --}}
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

      {{-- Zone boutons à droite --}}
      <div class="ms-auto d-flex align-items-center gap-2 flex-wrap">

        {{-- Bouton audio --}}
        @if(!empty($audioPath))
          <button id="playAudioBtn" class="btn btn-primary d-flex align-items-center gap-2">
            <span>🔊</span>
            <span>Me lire l’analyse</span>
          </button>
        @endif

        {{-- Bouton PDF --}}
        <form id="pdfForm"
              method="POST"
              action="{{ route('client-bocs.pdf', $boc) }}">
          @csrf
          <input type="hidden" name="chart_image" id="chartImageInput">
          <button type="button" id="btn-download-pdf" class="btn btn-outline-secondary">
            📄 Télécharger le PDF
          </button>
        </form>
      </div>
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

  {{-- L’analyse --}}
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
{{ $boc->interpreted_markdown ?? 'Analyse en cours…' }}
          </pre>
        </div>
      </div>
    </div>
  </div>

  {{-- SECTION : bulles BRVM (toujours visible) --}}
  <div class="mt-4" id="bubbles-wrapper">
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
</div>

{{-- Scripts --}}
@if($boc->interpreted_markdown)
  <script src="https://d3js.org/d3.v7.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
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
  const wrapper       = document.getElementById('bubbles-wrapper');
  const fullscreenBtn = document.getElementById('btn-bubbles-fullscreen');
  const bubblesDiv    = document.getElementById('brvm-bubbles');

  let lastData = null;

  if (wrapper && bubblesDiv && window.d3) {
    // Charger automatiquement les données au chargement de la page
    fetch('{{ route('client-bocs.bubbles', $boc) }}')
      .then(r => {
        if (!r.ok) throw new Error('HTTP ' + r.status);
        return r.json();
      })
      .then(payload => {
        console.log('Bubbles payload:', payload);
        const data = Array.isArray(payload) ? payload : (payload.data || []);
        lastData = data;
        drawBubbles(data);
      })
      .catch(err => {
        console.error('Bubbles fetch error:', err);
        bubblesDiv.innerHTML =
          '<p class="text-white p-3">Impossible de charger les données du marché.</p>';
      });

    // FULLSCREEN
    if (fullscreenBtn) {
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

        if (lastData) {
          drawBubbles(lastData);
        }
      });

      window.addEventListener('resize', () => {
        if (lastData) {
          drawBubbles(lastData);
        }
      });
    }

    // Fonction de dessin des bulles
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

      node.append('circle')
        .attr('r', d => d.radius)
        .attr('fill', d => colorFn(d))
        .attr('stroke', '#ffffff')
        .attr('stroke-width', 2)
        .attr('fill-opacity', 0.85);

      node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '-0.2em')
        .style('fill', '#ffffff')
        .style('font-weight', 'bold')
        .style('pointer-events', 'none')
        .style('font-size', d => Math.max(12, d.radius / 3) + 'px')
        .text(d => d.ticker || d.label);

      node.append('text')
        .attr('text-anchor', 'middle')
        .attr('dy', '1.2em')
        .style('fill', '#ffffff')
        .style('pointer-events', 'none')
        .style('font-size', d => Math.max(10, d.radius / 4) + 'px')
        .text(d => `${d.change > 0 ? '+' : ''}${d.change.toFixed(1)} %`);

      node.append('title')
        .text(d => `${d.name} (${d.ticker})\nVariation jour : ${d.change.toFixed(2)} %`);

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

  // === EXPORT PDF (capture des bulles) ===
  const pdfBtn        = document.getElementById('btn-download-pdf');
  const pdfForm       = document.getElementById('pdfForm');
  const chartInput    = document.getElementById('chartImageInput');
  const bubblesCanvas = document.getElementById('brvm-bubbles');

  if (pdfBtn && pdfForm && chartInput && bubblesCanvas && window.html2canvas) {
    pdfBtn.addEventListener('click', async () => {
      try {
        pdfBtn.disabled = true;
        pdfBtn.textContent = '⏳ Génération du PDF...';

        const canvas = await html2canvas(bubblesCanvas, {
          backgroundColor: '#111'
        });
        const dataUrl = canvas.toDataURL('image/png');

        chartInput.value = dataUrl;
        pdfForm.submit();
      } catch (e) {
        console.error(e);
        alert("Impossible de préparer le PDF. Réessaie.");
        pdfBtn.disabled = false;
        pdfBtn.textContent = '📄 Télécharger le PDF';
      }
    });
  }
});
</script>
@endsection
