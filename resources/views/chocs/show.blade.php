@extends('layouts.app')

@section('content')
<div class="bg-light py-5">
  <div class="container" style="max-width:1100px;">

    <div class="mb-3">
      <a href="{{ route('chocs.index') }}" class="text-decoration-none small">
        ← Retour aux secteurs
      </a>
    </div>

    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
      <div>
        <h1 class="fw-bold mb-1">⚡ {{ $data['title'] }}</h1>
        <p class="text-muted mb-0">Causes typiques de hausse / baisse + exemples.</p>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="fw-semibold mb-2">📈 Ce qui fait monter</div>
            <ul class="text-muted small mb-0">
              @foreach($data['up'] as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body">
            <div class="fw-semibold mb-2">📉 Ce qui fait chuter</div>
            <ul class="text-muted small mb-0">
              @foreach($data['down'] as $item)
                <li>{{ $item }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      </div>

      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="fw-semibold mb-2">🧾 Exemples (réels / typiques)</div>

            <div class="row g-3">
              @foreach($data['examples'] as $ex)
                <div class="col-md-6">
                  <div class="border rounded-3 p-3 bg-white">
                    <div class="fw-semibold">{{ $ex['label'] }}</div>
                    <div class="text-muted small">{{ $ex['note'] }}</div>
                  </div>
                </div>
              @endforeach
            </div>

            <div class="alert alert-light border mt-3 mb-0 small">
              ⚠️ Pédagogique : ce module explique les réactions du marché.
              Ce n’est pas un conseil d’investissement personnalisé.
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
