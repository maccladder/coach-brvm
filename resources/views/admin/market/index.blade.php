@extends('layouts.admin')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="fw-bold mb-1">Marché BRVM (Toutes)</h3>
            <p class="text-muted mb-0">Actions détectées : {{ $count }}</p>
        </div>

        <a href="#" class="btn btn-outline-primary">
            🧾 Portefeuille
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger mb-3">{{ session('error') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:90px;">Symbole</th>
                            <th>Nom</th>
                            <th class="text-end" style="width:140px;">Cours veille</th>
                            <th class="text-end" style="width:160px;">Cours Ouverture</th>
                            <th class="text-end" style="width:140px;">Cours Clôture</th>
                            <th class="text-end" style="width:130px;">Variation</th>
                            <th class="text-end" style="width:260px;">Achat</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($stocks as $s)
                            @php
                                $fmt = fn($v) => $v !== null ? number_format($v, 0, ',', ' ') : 'NC';
                                $fmtPct = fn($v) => $v !== null ? number_format($v, 2, ',', ' ') . ' %' : 'NC';
                            @endphp

                            <tr>
                                <td class="fw-bold">{{ $s['ticker'] }}</td>
                                <td>{{ $s['name'] }}</td>

                                <td class="text-end">{{ $fmt($s['prev']) }}</td>
                                <td class="text-end">{{ $fmt($s['open']) }}</td>
                                <td class="text-end">{{ $fmt($s['close']) }}</td>

                                <td class="text-end">
                                    @if($s['change'] === null)
                                        <span class="text-muted">NC</span>
                                    @else
                                        <span class="badge {{ $s['change'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                            {{ $fmtPct($s['change']) }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    @if(empty($s['buy_price']))
                                        <span class="text-muted">Achat indisponible</span>
                                    @else
                                        <form method="POST" action="#" class="d-inline-flex gap-2 align-items-center">
                                            @csrf
                                            <input type="hidden" name="ticker" value="{{ $s['ticker'] }}">
                                            <input type="hidden" name="name" value="{{ $s['name'] }}">
                                            <input type="hidden" name="price" value="{{ $s['buy_price'] }}">

                                            <input type="number"
                                                name="qty"
                                                min="1"
                                                value="1"
                                                class="form-control form-control-sm"
                                                style="width:90px;"
                                                required>

                                            <button class="btn btn-sm btn-success">
                                                🛒 Acheter ({{ number_format($s['buy_price'], 0, ',', ' ') }})
                                            </button>
                                        </form>
                                    @endif
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
    </div>

</div>
@endsection
