@extends('layouts.app')

@section('content')
<div class="bg-light py-5">
  <div class="container" style="max-width:1100px;">

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
      <div>
        <h1 class="fw-bold mb-1">⚡ Chocs de marché</h1>
        <p class="text-muted mb-0">
          Comprendre pourquoi une action BRVM peut <strong>monter</strong> ou <strong>chuter</strong> subitement,
          selon le <strong>secteur</strong>, avec des exemples concrets.
        </p>
      </div>
      <span class="badge bg-white text-dark border">Gratuit</span>
    </div>

    <div class="row g-3 mt-1">
      @foreach($sectors as $key => $s)
        <div class="col-md-6 col-lg-4">
          <a href="{{ route('chocs.show', $key) }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100">
              <div class="card-body">
                <div class="fw-semibold mb-1">📌 {{ $s['title'] }}</div>
                <div class="small text-muted">
                  Voir les causes de hausse/baisse + exemples
                </div>
                <div class="mt-3">
                  <span class="badge bg-light text-dark border">Hausse</span>
                  <span class="badge bg-light text-dark border">Baisse</span>
                  <span class="badge bg-light text-dark border">Exemples</span>
                </div>
              </div>
            </div>
          </a>
        </div>
      @endforeach
    </div>

    <div class="alert alert-white bg-white border shadow-sm mt-4 mb-0">
      <div class="fw-semibold">🎯 Comment utiliser ce module</div>
      <div class="text-muted small">
        Quand tu vois une action bouger fort, tu identifies le <strong>secteur</strong>, puis tu compares l’événement
        avec les causes typiques. Ensuite, tu décides calmement : acheter, garder, vendre ou attendre.
      </div>
    </div>

  </div>
</div>
@endsection
