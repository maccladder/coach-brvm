@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 1100px;">

  {{-- Bandeau haut : avatar + titre + bouton audio --}}
  <div class="card shadow-sm mb-4 border-0">
    <div class="card-body d-flex align-items-center flex-wrap gap-3">

      {{-- Coach --}}
      <div class="d-flex align-items-center gap-3 flex-grow-1">
        <img src="{{ asset('avatars/coach.png') }}"
             alt="Coach BRVM"
             class="rounded-circle border shadow-sm"
             style="width:70px;height:70px;object-fit:cover;">
        <div>
          <h4 class="mb-1">Résumé du {{ $today->format('d/m/Y') }}</h4>
          <div class="text-muted small">
            Coach BRVM · interprétation personnalisée
          </div>
        </div>
      </div>

      {{-- Bouton audio --}}
      @if($summary && !empty($audioPath))
        <button id="playAudioBtn" class="btn btn-primary ms-auto d-flex align-items-center gap-2">
          <span>🔊</span>
          <span>Me lire l’analyse</span>
        </button>
      @endif
    </div>
  </div>

  {{-- Audio généré via AiVoiceService (invisible) --}}
  @if($summary && !empty($audioPath))
    <audio
      id="summaryAudio"
      src="{{ asset('storage/' . $audioPath) }}"
      preload="auto">
    </audio>
  @endif

  @if(!$summary)
    <div class="alert alert-warning shadow-sm border-0">
      Aucun résumé n’a encore été généré pour cette date.
    </div>
  @else
    <div class="row g-4">

      {{-- Col gauche : Avatar vidéo si dispo --}}
      @if($summary->avatar_video_url)
        <div class="col-lg-5">
          <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-white border-0 pb-0">
              <h6 class="mb-1">
                🎥 Avatar vidéo
              </h6>
              <small class="text-muted">
                Le coach te résume la séance
              </small>
            </div>
            <div class="card-body text-center">
              <video
                src="{{ $summary->avatar_video_url }}"
                controls
                playsinline
                style="max-width: 100%; border-radius: 12px;">
              </video>
            </div>
          </div>
        </div>
      @endif

      {{-- Col droite : Résumé texte --}}
      <div class="{{ $summary->avatar_video_url ? 'col-lg-7' : 'col-12' }}">
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white border-0">
            <h6 class="mb-1">📝 Résumé détaillé</h6>
            <small class="text-muted">
              Données brutes + interprétation IA du marché
            </small>
          </div>

          <div class="card-body">
            <pre class="p-3 bg-light rounded border small mb-0"
                 style="white-space:pre-wrap; font-family: 'JetBrains Mono', 'Fira Code', monospace;">
{{ $summary->summary_markdown }}
            </pre>
          </div>
        </div>

        @if($summary->signals)
          <div class="card shadow-sm border-0 mt-3">
            <div class="card-header bg-white border-0">
              <h6 class="mb-1">📌 Signaux détectés</h6>
              <small class="text-muted">
                Idées d’actions et points de vigilance
              </small>
            </div>
            <div class="card-body">
              <pre class="p-3 bg-light rounded border small mb-0"
                   style="white-space:pre-wrap; font-family: 'JetBrains Mono', 'Fira Code', monospace;">
{{ json_encode($summary->signals, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}
              </pre>
            </div>
          </div>
        @endif
      </div>
    </div>
  @endif
</div>

{{-- JS pour le bouton audio (lecture du MP3 généré) --}}
@if($summary && !empty($audioPath))
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
        btn.innerHTML = '⏸️ Mettre en pause';
      });

      audio.addEventListener('pause', () => {
        isPlaying = false;
        btn.innerHTML = '🔊 Me lire l’analyse';
      });

      audio.addEventListener('ended', () => {
        isPlaying = false;
        btn.innerHTML = '🔊 Me lire l’analyse';
      });
    });
  </script>
@endif
@endsection
