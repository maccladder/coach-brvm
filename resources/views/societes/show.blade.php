@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:1100px;">

    <a href="{{ route('societes.index') }}" class="small text-decoration-none">
        ← Retour aux sociétés
    </a>

    {{-- Header société --}}
    <div class="d-flex align-items-center gap-3 mt-3 mb-4">
        @if(!empty($societe['logo']) && file_exists(public_path($societe['logo'])))
            <img src="{{ asset($societe['logo']) }}"
                 alt="{{ $societe['name'] }}"
                 class="border rounded p-2 bg-white shadow-sm"
                 style="width:72px;height:72px;object-fit:contain;">
        @else
            <div class="rounded-circle bg-light border d-flex align-items-center justify-content-center shadow-sm"
                 style="width:72px;height:72px;font-size:1.5rem;font-weight:700;">
                {{ strtoupper(substr($societe['name'], 0, 1)) }}
            </div>
        @endif

        <div>
            <h1 class="fw-bold mb-1">{{ $societe['name'] }}</h1>
            <div class="text-muted">
                Ticker : <strong>{{ $societe['ticker'] }}</strong>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Présentation --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h5 class="fw-semibold mb-2">📌 Présentation</h5>
                    <p class="text-muted mb-0">
                        {{ $societe['description'] }}
                    </p>
                </div>
            </div>

            {{-- ✅ Bloc Dividendes --}}
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <h5 class="fw-semibold mb-1">💰 Dividendes & Valorisation</h5>
                            <div class="text-muted small">
                                Données issues des BOC (pages 3–4)
                            </div>
                        </div>

                        {{-- UX bonus badge (uniquement si classé en 2025) --}}
                        <div class="text-end">
                            @php
                                $badge = null;
                                if (!empty($rank)) {
                                    if ($rank === 1) $badge = ['🥇 Top 1', 'bg-warning-subtle text-warning-emphasis border border-warning-subtle'];
                                    elseif ($rank === 2) $badge = ['🥈 Top 2', 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle'];
                                    elseif ($rank === 3) $badge = ['🥉 Top 3', 'bg-danger-subtle text-danger-emphasis border border-danger-subtle'];
                                    elseif ($rank <= 10) $badge = ['🔥 Top 10', 'bg-success-subtle text-success-emphasis border border-success-subtle'];
                                    elseif ($rank <= 20) $badge = ['✅ Top 20', 'bg-primary-subtle text-primary-emphasis border border-primary-subtle'];
                                }
                            @endphp

                            @if($isRanked2025 && $badge)
                                <span class="badge rounded-pill {{ $badge[1] }}">
                                    {{ $badge[0] }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <hr class="my-3">

                    @if($hasDividend)
                        <div class="row small">
                            <div class="col-6 mb-3">
                                <div class="text-muted">Montant du dernier dividende (net)</div>
                                <div class="fw-bold">
                                    {{ number_format((float)$dividende->dividende_net, 2, ',', ' ') }} FCFA
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="text-muted">Date du dernier paiement</div>
                                <div class="fw-bold">
                                    {{ $dividende->date_paiement
                                        ? \Carbon\Carbon::parse($dividende->date_paiement)->format('d/m/Y')
                                        : '—' }}
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="text-muted">Rendement net</div>
                                <div class="fw-bold text-success">
                                    {{ $dividende->rendement_net !== null
                                        ? number_format((float)$dividende->rendement_net, 2, ',', ' ') . ' %'
                                        : '—' }}
                                </div>
                            </div>

                            <div class="col-6 mb-3">
                                <div class="text-muted">PER</div>
                                <div class="fw-bold">
                                    {{ $dividende->per !== null
                                        ? number_format((float)$dividende->per, 2, ',', ' ')
                                        : '—' }}
                                </div>
                            </div>
                        </div>

                        {{-- ✅ Rang + lien classement (filtré 2025) --}}
                        <div class="mt-2">
                            <div class="text-muted small mb-1">Rang dividende {{ $rankingYear }}</div>

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="fw-bold">
                                    @if($isRanked2025 && $rank && $totalPayeurs)
                                        {{ $rank }} / {{ $totalPayeurs }} <span class="ms-1">✅</span>
                                    @else
                                        <span class="text-muted">
                                            Non classé en {{ $rankingYear }}
                                            @if(!empty($dividende->date_paiement))
                                                (dernier paiement : {{ \Carbon\Carbon::parse($dividende->date_paiement)->format('Y') }})
                                            @endif
                                        </span>
                                    @endif
                                </div>

                                <a href="{{ route('dividendes.index', ['year' => $rankingYear]) }}" class="btn btn-sm btn-outline-primary">
                                    Voir le classement complet →
                                </a>
                            </div>
                        </div>

                        <div class="text-muted small mt-3">
                            Source : {{ $dividende->boc_date_reference
                                ? 'BOC du ' . \Carbon\Carbon::parse($dividende->boc_date_reference)->format('d/m/Y')
                                : 'BOC' }}
                        </div>

                    @else
                        <div class="alert alert-light border small mb-0">
                            ℹ️ Infos dividendes non disponibles pour le moment pour ce titre.
                            <div class="mt-2">
                                <a href="{{ route('dividendes.index', ['year' => $rankingYear]) }}" class="btn btn-sm btn-outline-primary">
                                    Voir le classement complet →
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Informations --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-semibold mb-3">ℹ️ Informations</h6>

                    <div class="small">
                        <div class="text-muted">Téléphone</div>
                        <div class="fw-semibold mb-3">
                            {{ $societe['telephone'] ?? '—' }}
                        </div>

                        <div class="text-muted">Adresse</div>
                        <div class="fw-semibold mb-3">
                            {{ $societe['adresse'] ?? '—' }}
                        </div>

                        <div class="text-muted mb-1">Dirigeants</div>
                        @if(!empty($societe['dirigeants']))
                            <ul class="mb-0 ps-3">
                                @foreach($societe['dirigeants'] as $role => $name)
                                    <li><strong>{{ $role }}</strong> : {{ $name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="fw-semibold">—</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
