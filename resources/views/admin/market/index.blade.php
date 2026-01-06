@extends('layouts.admin')

@section('content')
<div class="container py-4">
  <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
      <h3 class="fw-bold mb-1">Marché BRVM (Toutes)</h3>
      <p class="text-muted mb-0">Actions détectées : {{ $count }}</p>
    </div>

    <div>
      <a href="#" class="btn btn-outline-primary">
        🧾 Portefeuille
      </a>
    </div>
  </div>

  <div class="card mt-3">
    <div class="table-responsive">
      <table class="table table-sm table-striped align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:110px;">Symbole</th>
            <th>Nom</th>
            <th class="text-end" style="width:140px;">Cours veille</th>
            <th class="text-end" style="width:160px;">Cours Ouverture</th>
            <th class="text-end" style="width:140px;">Cours Clôture</th>
            <th class="text-end" style="width:120px;">Variation</th>
            <th class="text-end" style="width:120px;">Prix achat</th>
          </tr>
        </thead>

        <tbody>
          @forelse($stocks as $s)
            <tr>
              <td class="fw-bold">{{ $s['ticker'] }}</td>
              <td>{{ $s['name'] }}</td>

              <td class="text-end">
                {{ $s['prev'] !== null ? number_format($s['prev'], 0, ',', ' ') : 'NC' }}
              </td>

              <td class="text-end">
                {{ $s['open'] !== null ? number_format($s['open'], 0, ',', ' ') : 'NC' }}
              </td>

              <td class="text-end">
                {{ $s['close'] !== null ? number_format($s['close'], 0, ',', ' ') : 'NC' }}
              </td>

              <td class="text-end">
                @if($s['change'] === null)
                  NC
                @else
                  {{ number_format($s['change'], 2, ',', ' ') }} %
                @endif
              </td>

              <td class="text-end">
                {{ $s['buy_price'] !== null ? number_format($s['buy_price'], 0, ',', ' ') : 'NC' }}
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                Aucune donnée marché disponible.
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <p class="text-muted small mt-2 mb-0">
    On utilise <strong>Prix achat</strong> = Ouverture (si &gt; 0) sinon Clôture sinon Veille.
  </p>
</div>
@endsection
