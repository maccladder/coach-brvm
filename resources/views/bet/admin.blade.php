@extends('layouts.admin')

@section('title', 'Coupons de paris')

@section('content')
<div class="container py-5" style="max-width:1200px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Coupons de paris Betclic</h2>
            <div class="text-muted">Brouillons générés chaque matin par n8n. Compose les sélections dans l'app Betclic, colle le lien « Partager ces sélections », puis publie.</div>
        </div>
        <a href="{{ route('bet.index') }}" target="_blank" class="btn btn-outline-secondary">Voir la page publique</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <h5 class="fw-bold mb-3">Brouillons en attente</h5>

    @forelse($brouillons as $coupon)
        @php $badge = $coupon->badge(); @endphp
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <span class="badge" style="background: {{ $badge['color'] }};">{{ $badge['emoji'] }} {{ $badge['label'] }}</span>
                        <span class="text-muted ms-2">{{ $coupon->date_coupon->format('d/m/Y') }}</span>
                    </div>
                    <div class="fw-bold">Cote totale : {{ number_format($coupon->cote_totale, 2) }}</div>
                </div>

                <ul class="list-group list-group-flush mb-3">
                    @foreach($coupon->selections as $sel)
                        <li class="list-group-item px-0">
                            <strong>{{ $sel['match'] }}</strong> → {{ $sel['pari'] }} @ {{ number_format($sel['cote'], 2) }}
                            <div class="text-muted small">{{ $sel['ligue'] }} — {{ \Carbon\Carbon::parse($sel['heure'])->setTimezone('Africa/Abidjan')->format('d/m H:i') }}</div>
                        </li>
                    @endforeach
                </ul>

                @if($coupon->analyse)
                    <p class="text-muted fst-italic small mb-3">{{ $coupon->analyse }}</p>
                @endif

                <form action="{{ route('bet.publier', $coupon) }}" method="POST" class="d-flex gap-2">
                    @csrf
                    <input type="url" name="lien_betclic" class="form-control" placeholder="Lien Betclic (Partager ces sélections)" required>
                    <button type="submit" class="btn btn-success text-nowrap">Publier</button>
                </form>
            </div>
        </div>
    @empty
        <div class="text-muted p-4">Aucun brouillon en attente.</div>
    @endforelse

    <h5 class="fw-bold mb-3 mt-5">Publiés récemment</h5>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Niveau</th>
                        <th>Cote</th>
                        <th>Résultat</th>
                        <th class="text-end">Lien</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($publies as $coupon)
                        @php $badge = $coupon->badge(); @endphp
                        <tr>
                            <td>{{ $coupon->date_coupon->format('d/m/Y') }}</td>
                            <td><span class="badge" style="background: {{ $badge['color'] }};">{{ $badge['emoji'] }} {{ $badge['label'] }}</span></td>
                            <td>{{ number_format($coupon->cote_totale, 2) }}</td>
                            <td>
                                @if($coupon->resultat === 'en_attente')
                                    <span class="text-muted">⏳ En attente</span>
                                @elseif($coupon->resultat === 'gagne')
                                    <span class="text-success">✅ Gagné</span>
                                @else
                                    <span class="text-danger">❌ Perdu</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('bet.index') }}" target="_blank" class="btn btn-sm btn-outline-primary">Page publique</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted p-4">Aucun coupon publié.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
