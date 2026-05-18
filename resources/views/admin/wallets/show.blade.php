@extends('layouts.admin')

@section('title', 'Portefeuille – ' . $user->name)

@section('content')
<div class="container py-4" style="max-width:1100px">

    {{-- Fil d'Ariane --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.wallets.index') }}">Portefeuilles</a></li>
            <li class="breadcrumb-item active">{{ $user->name }}</li>
        </ol>
    </nav>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    {{-- En-tête utilisateur --}}
    <div class="card mb-4 shadow-sm">
        <div class="card-body d-flex flex-wrap align-items-center gap-4">
            <div>
                <div class="fw-bold fs-5">{{ $user->name }}</div>
                <div class="text-muted">{{ $user->email }}</div>
                <div class="text-muted small">Membre depuis {{ $user->created_at->format('d/m/Y') }}</div>
            </div>
            <div class="ms-auto d-flex gap-3 flex-wrap">
                <div class="text-center">
                    <div class="text-muted small">Solde cash</div>
                    <div class="fs-4 fw-bold text-success">
                        {{ $wallet ? number_format($wallet->balance, 0, ',', ' ') : '0' }}
                        <span class="fs-6 text-muted fw-normal">FCFA</span>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-muted small">Positions</div>
                    <div class="fs-4 fw-bold">{{ $positions->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Colonne gauche : actions admin --}}
        <div class="col-lg-4">

            {{-- Recharge --}}
            <div class="card shadow-sm mb-3">
                <div class="card-header fw-semibold">
                    <i class="bi bi-plus-circle me-1 text-success"></i> Recharger le portefeuille
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.wallets.topup', $user) }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Montant (FCFA)</label>
                            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                   min="1" placeholder="ex : 10000" value="{{ old('amount') }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motif <span class="text-muted">(optionnel)</span></label>
                            <input type="text" name="motif" class="form-control" maxlength="255"
                                   placeholder="ex : Correction email, Bonus…" value="{{ old('motif') }}">
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-plus-circle me-1"></i> Recharger
                        </button>
                    </form>
                </div>
            </div>

            {{-- Transfert --}}
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">
                    <i class="bi bi-arrow-left-right me-1 text-primary"></i> Transférer le solde
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Débite le portefeuille de <strong>{{ $user->name }}</strong> et crédite un autre compte.
                    </p>
                    <form method="POST" action="{{ route('admin.wallets.transfer', $user) }}" id="transferForm">
                        @csrf

                        {{-- Autocomplete destination --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Compte destination</label>
                            <div class="position-relative">
                                <input type="text" id="destSearch"
                                       class="form-control @error('dest_email') is-invalid @enderror"
                                       placeholder="Tapez un nom ou email…"
                                       autocomplete="off">
                                <input type="hidden" name="dest_email" id="destEmail" value="{{ old('dest_email') }}">

                                {{-- Dropdown résultats --}}
                                <div id="destDropdown"
                                     class="dropdown-menu w-100 shadow-sm p-0"
                                     style="display:none; position:absolute; z-index:1000; top:100%; left:0; max-height:220px; overflow-y:auto">
                                </div>
                            </div>

                            {{-- Utilisateur sélectionné --}}
                            <div id="destSelected" class="mt-2" style="display:none">
                                <div class="d-flex align-items-center justify-content-between bg-light border rounded px-3 py-2">
                                    <div>
                                        <span class="fw-semibold" id="destSelectedName"></span>
                                        <span class="text-muted small ms-2" id="destSelectedEmail"></span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="destClear">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>

                            @error('dest_email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Montant (FCFA)</label>
                            <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror"
                                   min="1" placeholder="ex : 5000" value="{{ old('amount') }}" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motif <span class="text-muted">(optionnel)</span></label>
                            <input type="text" name="motif" class="form-control" maxlength="255"
                                   placeholder="ex : Erreur d'email…" value="{{ old('motif') }}">
                        </div>
                        <button type="submit" id="transferSubmit" class="btn btn-primary w-100" disabled>
                            <i class="bi bi-arrow-left-right me-1"></i> Transférer
                        </button>
                    </form>
                </div>
            </div>

        </div>

        {{-- Colonne droite : positions + historique --}}
        <div class="col-lg-8">

            {{-- Positions --}}
            @if($positions->count())
                <div class="card shadow-sm mb-4">
                    <div class="card-header fw-semibold">
                        <i class="bi bi-bar-chart me-1"></i> Positions ({{ $positions->count() }})
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Ticker</th>
                                    <th>Société</th>
                                    <th class="text-end">Quantité</th>
                                    <th class="text-end">PRU</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($positions as $pos)
                                    <tr>
                                        <td><span class="badge bg-dark">{{ $pos->ticker }}</span></td>
                                        <td class="text-muted small">{{ $pos->name }}</td>
                                        <td class="text-end fw-semibold">{{ number_format($pos->qty, 0, ',', ' ') }}</td>
                                        <td class="text-end text-muted small">{{ number_format($pos->avg_price, 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- Historique transactions --}}
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">
                    <i class="bi bi-clock-history me-1"></i> Historique des transactions
                    <span class="text-muted fw-normal small">({{ $transactions->count() }} dernières)</span>
                </div>
                @if($transactions->isEmpty())
                    <div class="card-body text-muted text-center py-4">Aucune transaction.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-end">Montant</th>
                                    <th>Motif / Détail</th>
                                    <th>Par</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                $typeLabels = [
                                    'topup'        => ['label' => 'Recharge', 'color' => 'success'],
                                    'topup_admin'  => ['label' => 'Recharge admin', 'color' => 'success'],
                                    'buy'          => ['label' => 'Achat', 'color' => 'secondary'],
                                    'sell'         => ['label' => 'Vente', 'color' => 'primary'],
                                    'transfer_in'  => ['label' => 'Transfert reçu', 'color' => 'info'],
                                    'transfer_out' => ['label' => 'Transfert envoyé', 'color' => 'warning'],
                                ];
                                @endphp
                                @foreach($transactions as $tx)
                                    @php
                                        $meta  = $typeLabels[$tx->type] ?? ['label' => $tx->type, 'color' => 'secondary'];
                                        $isPos = $tx->amount >= 0;
                                    @endphp
                                    <tr>
                                        <td class="text-muted small text-nowrap">
                                            {{ $tx->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $meta['color'] }}">{{ $meta['label'] }}</span>
                                            @if($tx->ticker)
                                                <span class="badge bg-dark ms-1">{{ $tx->ticker }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-semibold text-nowrap {{ $isPos ? 'text-success' : 'text-danger' }}">
                                            {{ $isPos ? '+' : '' }}{{ number_format($tx->amount, 0, ',', ' ') }} FCFA
                                        </td>
                                        <td class="text-muted small">
                                            @if($tx->motif)
                                                {{ $tx->motif }}
                                            @elseif($tx->ticker && $tx->qty)
                                                {{ $tx->qty }} action(s) à {{ number_format($tx->price, 0, ',', ' ') }} FCFA
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-muted small">
                                            @if($tx->creator)
                                                {{ $tx->creator->name }}
                                            @elseif(in_array($tx->type, ['topup_admin', 'transfer_in', 'transfer_out']))
                                                Admin
                                            @else
                                                Utilisateur
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const searchInput  = document.getElementById('destSearch');
    const hiddenEmail  = document.getElementById('destEmail');
    const dropdown     = document.getElementById('destDropdown');
    const selectedBox  = document.getElementById('destSelected');
    const selectedName = document.getElementById('destSelectedName');
    const selectedMail = document.getElementById('destSelectedEmail');
    const clearBtn     = document.getElementById('destClear');
    const submitBtn    = document.getElementById('transferSubmit');
    const searchUrl    = '{{ route('admin.wallets.user-search') }}';

    let debounceTimer;

    function selectUser(name, email) {
        hiddenEmail.value   = email;
        selectedName.textContent = name;
        selectedMail.textContent = email;
        selectedBox.style.display = 'block';
        searchInput.style.display = 'none';
        dropdown.style.display    = 'none';
        submitBtn.disabled = false;
    }

    function resetSelection() {
        hiddenEmail.value = '';
        searchInput.value = '';
        searchInput.style.display = 'block';
        selectedBox.style.display = 'none';
        dropdown.style.display    = 'none';
        submitBtn.disabled = true;
        searchInput.focus();
    }

    clearBtn.addEventListener('click', resetSelection);

    searchInput.addEventListener('input', function () {
        const q = this.value.trim();
        clearTimeout(debounceTimer);

        if (q.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        debounceTimer = setTimeout(function () {
            fetch(searchUrl + '?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(users => {
                    dropdown.innerHTML = '';

                    if (users.length === 0) {
                        dropdown.innerHTML = '<div class="px-3 py-2 text-muted small">Aucun utilisateur trouvé.</div>';
                        dropdown.style.display = 'block';
                        return;
                    }

                    users.forEach(u => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = 'dropdown-item px-3 py-2';
                        item.innerHTML =
                            '<span class="fw-semibold">' + escHtml(u.name) + '</span>' +
                            '<span class="text-muted small ms-2">' + escHtml(u.email) + '</span>';
                        item.addEventListener('click', function () {
                            selectUser(u.name, u.email);
                        });
                        dropdown.appendChild(item);
                    });

                    dropdown.style.display = 'block';
                });
        }, 250);
    });

    // Ferme le dropdown si on clique ailleurs
    document.addEventListener('click', function (e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Bloque la soumission si aucun user sélectionné
    document.getElementById('transferForm').addEventListener('submit', function (e) {
        if (!hiddenEmail.value) {
            e.preventDefault();
            searchInput.classList.add('is-invalid');
            searchInput.focus();
        }
    });

    function escHtml(str) {
        return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // Si old('dest_email') est renseigné (validation server échouée), réaffiche le champ texte
    if (hiddenEmail.value) {
        searchInput.value = hiddenEmail.value;
        submitBtn.disabled = false;
    }
})();
</script>
@endpush
