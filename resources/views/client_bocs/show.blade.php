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

  {{-- SI L’ANALYSE N’EST PAS ENCORE PRÊTE => écran d’attente --}}
  @if(!$boc->interpreted_markdown)
    <div class="d-flex flex-column align-items-center justify-content-center py-5">
      {{-- ton GIF loader --}}
      <img src="{{ asset('img/loader-dots.gif') }}" alt="Chargement..."
           style="width:120px;max-width:100%;" class="mb-3">

      <h5 class="mb-2 text-center">L’IA analyse ton BOC…</h5>
      <p class="text-muted text-center small mb-0">
        Cela prend généralement moins d’une minute.<br>
        Laisse simplement cette page ouverte, nous actualiserons le résultat automatiquement.
      </p>
    </div>

    {{-- petit refresh auto toutes les 8 secondes pour voir si l’analyse est prête --}}
    <script>
      setTimeout(() => window.location.reload(), 8000);
    </script>

  @else
    {{-- L’ANALYSE EST PRÊTE : même style que la page du résumé du jour --}}
    <div class="row g-4">

      {{-- Colonne gauche : avatar vidéo si disponible --}}
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

      {{-- Colonne droite : interprétation texte --}}
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
  @endif
</div>

{{-- JS pour le bouton audio --}}
@if(!empty($audioPath))
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const btn   = document.getElementById('playAudioBtn');
      const audio = document.getElementById('summaryAudio');

      if (!btn || !audio) return;

      let isPlaying = false;

      btn.addEventListener('click', () => {
        if (!isPlaying) {
          audio.play();
        } else {
          audio.pause();
        }
      });

      audio.addEventListener('play', () => {
        isPlaying = true;
        btn.innerHTML = '<span>⏸️</span><span>Mettre en pause</span>';
      });

      audio.addEventListener('pause', () => {
        isPlaying = false;
        btn.innerHTML = '<span>🔊</span><span>Me lire l’analyse</span>';
      });

      audio.addEventListener('ended', () => {
        isPlaying = false;
        btn.innerHTML = '<span>🔊</span><span>Me lire l’analyse</span>';
      });
    });
  </script>
@endif
@endsection
