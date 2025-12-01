@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- En-tête --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="me-3">
                    <img src="{{ asset('img/coach-avatar.png') }}"
                         alt="Coach BRVM"
                         style="width:56px;height:56px;border-radius:50%;">
                </div>
                <div>
                    <h4 class="mb-0">{{ $financial->title ?? 'États financiers' }}</h4>
                    <div class="text-muted small">
                        @if($financial->company)
                            {{ $financial->company }} –
                        @endif
                        {{ $financial->period }}
                        @if($financial->financial_date)
                            • États au {{ $financial->financial_date->format('d/m/Y') }}
                        @endif
                    </div>
                </div>
            </div>

            @php
                $audioUrl = $audioPath ? asset('storage/'.$audioPath) : null;
            @endphp

            @if($audioUrl)
                <div>
                    <audio id="audioAnalysis" src="{{ $audioUrl }}"></audio>
                    <button class="btn btn-primary" id="playAudioBtn">
                        🔊 Me lire l’analyse
                    </button>
                </div>
            @endif
        </div>
    </div>

    <div class="row g-4">
        {{-- Avatar vidéo --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0">
                    <strong>Avatar vidéo</strong>
                    <div class="small text-muted">Le coach commente tes états financiers</div>
                </div>
                <div class="card-body text-center">
                    @if($financial->avatar_video_url)
                        <video src="{{ $financial->avatar_video_url }}"
                               controls
                               style="width:100%;border-radius:12px;"
                               poster="{{ asset('img/coach-avatar.png') }}">
                        </video>
                    @else
                        <p class="text-muted">
                            La vidéo n’est pas encore disponible ou a rencontré un problème.
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Analyse détaillée --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-0">
                    <strong>Analyse détaillée</strong>
                    <div class="small text-muted">
                        Synthèse IA des états financiers :
                        chiffre d’affaires, bénéfice net, capacité d’autofinancement, dettes, trésorerie…
                    </div>
                </div>
                <div class="card-body" style="max-height:70vh;overflow:auto;">
                    @if($financial->interpreted_markdown)
                        <div class="markdown-body" style="white-space:pre-wrap;">
                            {!! nl2br(e($financial->interpreted_markdown)) !!}
                        </div>
                    @else
                        <p class="text-muted">
                            L’analyse n’a pas encore été générée.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($audioUrl)
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btn   = document.getElementById('playAudioBtn');
            const audio = document.getElementById('audioAnalysis');

            if (btn && audio) {
                btn.addEventListener('click', () => {
                    if (audio.paused) {
                        audio.play();
                        btn.textContent = '⏸️ Mettre en pause';
                    } else {
                        audio.pause();
                        btn.textContent = '🔊 Me lire l’analyse';
                    }
                });

                audio.addEventListener('ended', () => {
                    btn.textContent = '🔊 Me lire l’analyse';
                });
            }
        });
    </script>
@endif
@endsection
