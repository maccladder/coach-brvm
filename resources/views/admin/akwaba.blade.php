@extends('layouts.admin')
@section('title', '🌙 Akwaba — Admin')
@section('content')
@php
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ') . ' F';
    $chapter = fn ($s) => $s === null ? '—' : ($s === 'P' ? 'Prologue' : 'Ch. ' . $s);
    $finLabels = ['oubli' => "Fin de l'oubli", 'gardien' => 'Fin du gardien'];
    $reasonBadges = [
        'purchase' => ['bg-success', '💳 Achat'],
        'use'      => ['bg-secondary', '💡 Utilisé'],
        'admin'    => ['bg-warning text-dark', '🛠️ Admin'],
    ];
    $funnelTop = max(1, collect($stats['funnel'])->max('count'));
    $guestTop  = max(1, $stats['guests']['play_start'], $stats['guests']['lock_reached']);
    $pct = fn ($v) => $v === null ? '—' : $v . ' %';
    // Gestes manuels réservés au code admin ; le code stagiaire consulte seulement
    $canAct = (bool) session('is_admin');
@endphp
<div class="container py-4" style="max-width:1300px;">

    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h2 class="mb-1">🌙 Akwaba : le mystère de la lagune Ébrié</h2>
            <div class="text-muted small">
                Produit #{{ $product->id }} ·
                @if($product->status === 'published')
                    <span class="badge bg-success">Publié</span>
                @else
                    <span class="badge bg-secondary">{{ ucfirst($product->status) }}</span>
                @endif
                · <a href="{{ route('akwaba.show') }}" target="_blank" rel="noopener">Ouvrir le jeu ↗</a>
            </div>
        </div>
    </div>

    {{-- Messages --}}
    @foreach(['success' => 'success', 'info' => 'info', 'error' => 'danger'] as $key => $class)
        @if(session($key))
            <div class="alert alert-{{ $class }}">{{ session($key) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger mb-4">
            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
        </div>
    @endif

    {{-- Paiements en double à rembourser --}}
    @if($duplicates->isNotEmpty())
        <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #dc3545 !important;">
            <div class="card-header fw-bold text-danger">
                💸 Paiements en double à rembourser ({{ $duplicates->count() }} · {{ $fcfa($duplicates->sum('amount_paid')) }})
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Date</th><th>Joueur</th><th>Référence Paystack</th><th class="text-end">Montant</th><th>Canal</th></tr>
                        </thead>
                        <tbody>
                            @foreach($duplicates as $d)
                                <tr>
                                    <td class="small text-muted text-nowrap">{{ $d->credited_at?->format('d/m/Y H:i') ?? $d->updated_at?->format('d/m/Y H:i') }}</td>
                                    <td class="small">
                                        @if($d->user)
                                            <a href="{{ route('admin.akwaba.index', ['user' => $d->user_id]) }}">{{ $d->user->name }}</a>
                                            <div class="text-muted">{{ $d->user->email }}</div>
                                        @else
                                            #{{ $d->user_id }}
                                        @endif
                                    </td>
                                    <td class="font-monospace small">{{ $d->transaction_id }}</td>
                                    <td class="text-end fw-semibold">{{ $fcfa($d->amount_paid) }}</td>
                                    <td class="small text-muted">{{ data_get($d->meta, 'channel', '—') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white small text-muted">
                Le joueur a payé deux fois l'histoire complète (deux tentatives validées). Le second paiement n'a créé aucun achat :
                rembourse-le depuis le tableau de bord Paystack (référence ci-dessus). Il n'est compté dans aucun chiffre d'affaires.
            </div>
        </div>
    @endif

    {{-- KPIs --}}
    <div class="row g-3 mb-4">
        @foreach([
            ['Joueurs connectés', $stats['players'], '#0d6efd'],
            ['Arrivés au cadenas', $stats['lock'], '#6f42c1'],
            ['Déblocages payés', $stats['unlocks_paid'] . ($stats['unlocks_grant'] ? ' + ' . $stats['unlocks_grant'] . ' offerts' : ''), '#198754'],
            ['Conversion globale au cadenas', $pct($stats['conversion_global']), '#fd7e14'],
            ['Packs d\'indices vendus', $stats['packs_sold'], '#20c997'],
            ['Chiffre d\'affaires total', $fcfa($stats['revenue']['total']), '#dc3545'],
        ] as [$label, $value, $color])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card text-center border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div style="font-size:1.6rem;font-weight:900;color:{{ $color }};">{{ $value }}</div>
                        <div class="text-muted small">{{ $label }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        {{-- Entonnoir --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header fw-bold">👻 Entonnoir des invités (sessions anonymes)</div>
                <div class="card-body">
                    @foreach([['Ont lancé le jeu', $stats['guests']['play_start'], '#0dcaf0'], ['🔒 Arrivés au cadenas', $stats['guests']['lock_reached'], '#6f42c1']] as [$label, $count, $color])
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1"><span>{{ $label }}</span><strong>{{ $count }}</strong></div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar" style="width:{{ round($count * 100 / $guestTop) }}%;background:{{ $color }};"></div>
                            </div>
                        </div>
                    @endforeach
                    <div class="small text-muted mt-2">
                        Une session = un navigateur non connecté (au plus un événement par type). Aucune IP ni donnée personnelle stockée.<br>
                        Conversion connectés : <strong>{{ $pct($stats['conversion']) }}</strong> ·
                        globale (payés ÷ cadenas invités + connectés) : <strong>{{ $pct($stats['conversion_global']) }}</strong>
                    </div>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-header fw-bold">📊 Entonnoir des joueurs connectés : chapitre le plus loin atteint</div>
                <div class="card-body">
                    @foreach($stats['funnel'] as $step)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="{{ $step['lock'] ? 'fw-bold' : '' }}">{{ $step['label'] }}</span>
                                <strong>{{ $step['count'] }}</strong>
                            </div>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar {{ $step['lock'] ? 'bg-purple' : 'bg-info' }}"
                                     style="width:{{ round($step['count'] * 100 / $funnelTop) }}%;{{ $step['lock'] ? 'background:#6f42c1;' : '' }}"></div>
                            </div>
                        </div>
                    @endforeach
                    <hr>
                    <div class="small text-muted">
                        Fins découvertes : {{ $finLabels['oubli'] }} <strong>{{ $stats['fins']['oubli'] }}</strong>
                        · {{ $finLabels['gardien'] }} <strong>{{ $stats['fins']['gardien'] }}</strong>
                    </div>
                    <div class="small text-muted mt-2">
                        Seuls les joueurs connectés sont comptés (les invités jouent sans sauvegarde serveur).
                        Cadenas = a cliqué « Continuer l'aventure » en étant connecté, a l'accès, ou a dépassé le chapitre 2.
                    </div>
                </div>
            </div>
        </div>

        {{-- Ventes --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-bold">💰 Ventes</div>
                <div class="card-body">
                    <table class="table table-sm mb-3">
                        <thead class="table-light">
                            <tr><th></th><th class="text-end">7 jours</th><th class="text-end">30 jours</th><th class="text-end">Total</th></tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Déblocages</td>
                                <td class="text-end">{{ $stats['last7']['unlocks'] }}</td>
                                <td class="text-end">{{ $stats['last30']['unlocks'] }}</td>
                                <td class="text-end">{{ $stats['unlocks_paid'] }}</td>
                            </tr>
                            <tr>
                                <td>Packs d'indices</td>
                                <td class="text-end">{{ $stats['last7']['packs'] }}</td>
                                <td class="text-end">{{ $stats['last30']['packs'] }}</td>
                                <td class="text-end">{{ $stats['packs_sold'] }}</td>
                            </tr>
                            <tr>
                                <td>CA Paystack</td>
                                <td class="text-end">{{ $fcfa($stats['last7']['revenue']['paystack']) }}</td>
                                <td class="text-end">{{ $fcfa($stats['last30']['revenue']['paystack']) }}</td>
                                <td class="text-end">{{ $fcfa($stats['revenue']['paystack']) }}</td>
                            </tr>
                            <tr>
                                <td>CA manuel</td>
                                <td class="text-end">{{ $fcfa($stats['last7']['revenue']['manual']) }}</td>
                                <td class="text-end">{{ $fcfa($stats['last30']['revenue']['manual']) }}</td>
                                <td class="text-end">{{ $fcfa($stats['revenue']['manual']) }}</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>CA total</td>
                                <td class="text-end">{{ $fcfa($stats['last7']['revenue']['total']) }}</td>
                                <td class="text-end">{{ $fcfa($stats['last30']['revenue']['total']) }}</td>
                                <td class="text-end">{{ $fcfa($stats['revenue']['total']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="small text-muted">
                        Paystack : déblocages {{ $fcfa($stats['unlock_revenue']) }} · packs {{ $fcfa($stats['packs_revenue']) }}.<br>
                        Manuel : montants saisis sur les déblocages offerts (encore actifs) et les ajouts d'indices
                        (paiements WhatsApp…).<br>
                        Indices ajoutés à la main : <strong>{{ $stats['hints_admin'] }}</strong> ·
                        indices utilisés : <strong>{{ $stats['hints_used'] }}</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Rattrapage --}}
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header fw-bold">🛟 Rattraper un pack d'indices</div>
                <div class="card-body">
                    <p class="small text-muted">
                        Le joueur a payé 500 F mais n'a pas reçu ses indices ? Saisis la référence Paystack
                        (<code>AKW-H-…</code>) : elle est revérifiée chez Paystack, puis +5 indices sont crédités
                        au joueur qui a payé, une seule fois.
                    </p>
                    @if($canAct)
                    <form method="POST" action="{{ route('admin.akwaba.recover') }}" class="js-confirm"
                          data-confirm="Vérifier cette référence chez Paystack et créditer +5 indices si elle est valide ?">
                        @csrf
                        <input type="text" name="reference" class="form-control form-control-sm font-monospace mb-2"
                               placeholder="AKW-H-…" value="{{ old('reference') }}" required maxlength="120">
                        <button type="submit" class="btn btn-sm btn-dark w-100">Vérifier et créditer</button>
                    </form>
                    @else
                        <div class="small text-muted fst-italic">Réservé au code admin.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Joueur sélectionné : actions manuelles --}}
    @if($selected)
        <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #6f42c1 !important;">
            <div class="card-header fw-bold d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span>👤 {{ $selected->name }} <span class="text-muted fw-normal">· {{ $selected->email }}</span></span>
                <span class="d-flex gap-2">
                    <a href="{{ route('admin.users.show', $selected) }}" class="btn btn-sm btn-outline-secondary">Fiche utilisateur</a>
                    <a href="{{ route('admin.akwaba.index') }}" class="btn btn-sm btn-outline-secondary">✕ Tous les joueurs</a>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3 small">
                    <div class="col-6 col-md-3">
                        <div class="text-muted">Histoire complète</div>
                        @if($selectedSummary['via'] === 'purchase')
                            <span class="badge bg-success">✅ Achetée (Paystack)</span>
                        @elseif($selectedSummary['via'] === 'grant')
                            <span class="badge bg-info">🎁 Offerte</span>
                        @else
                            <span class="badge bg-secondary">🔒 Non</span>
                        @endif
                    </div>
                    <div class="col-6 col-md-3"><div class="text-muted">Indices</div><strong>{{ $selectedSummary['hints'] }}</strong></div>
                    <div class="col-6 col-md-3"><div class="text-muted">Chapitre en cours / max</div><strong>{{ $chapter($selectedSummary['save']) }}</strong> / {{ $chapter($selectedSummary['max']) }}</div>
                    <div class="col-6 col-md-3"><div class="text-muted">Fins</div>{{ collect($selectedSummary['fins'])->map(fn ($f) => $finLabels[$f] ?? $f)->join(', ') ?: '—' }}</div>
                </div>

                @if(!$canAct)
                    <div class="alert alert-secondary small mb-0">
                        Consultation seule : débloquer, retirer, ajouter des indices et rattraper un pack sont réservés au code admin.
                    </div>
                @else
                {{-- Confirmation : texte dans data-confirm (échappé par Blade), jamais injecté dans du JavaScript --}}
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-bold mb-2">Déblocage de l'histoire</div>
                            @if(!$selectedSummary['unlocked'])
                                <form method="POST" action="{{ route('admin.akwaba.unlock', $selected) }}" class="js-confirm"
                                      data-confirm="Débloquer l'histoire complète pour {{ $selected->name }} ?">
                                    @csrf
                                    <input type="text" name="reason" class="form-control form-control-sm mb-2" required maxlength="300"
                                           placeholder="Motif (ex. paiement WhatsApp)">
                                    <div class="input-group input-group-sm mb-2">
                                        <span class="input-group-text">Montant encaissé</span>
                                        <input type="number" name="amount" class="form-control" value="0" min="0" max="1000000" step="1">
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                    <button class="btn btn-sm btn-success w-100">🎁 Débloquer</button>
                                </form>
                            @elseif($selectedSummary['via'] === 'grant')
                                <form method="POST" action="{{ route('admin.akwaba.lock', $selected) }}" class="js-confirm"
                                      data-confirm="Retirer le déblocage offert à {{ $selected->name }} ?">
                                    @csrf
                                    <input type="text" name="reason" class="form-control form-control-sm mb-2" required maxlength="300"
                                           placeholder="Motif du retrait">
                                    <button class="btn btn-sm btn-outline-danger w-100">Retirer le déblocage</button>
                                </form>
                            @else
                                <p class="small text-muted mb-0">Acheté via Paystack : l'accès est à vie et ne se retire pas ici.</p>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="border rounded p-3 h-100">
                            <div class="fw-bold mb-2">Ajouter des indices</div>
                            <form method="POST" action="{{ route('admin.akwaba.hints', $selected) }}" class="row g-2 js-confirm"
                                  data-confirm="Ajouter ces indices à {{ $selected->name }} ?">
                                @csrf
                                <div class="col-sm-3">
                                    <label class="form-label small mb-0">Indices</label>
                                    <input type="number" name="count" class="form-control form-control-sm" value="{{ old('count', 5) }}" min="1" max="100" required>
                                </div>
                                <div class="col-sm-5">
                                    <label class="form-label small mb-0">Motif</label>
                                    <input type="text" name="reason" class="form-control form-control-sm" required maxlength="200"
                                           placeholder="ex. paiement WhatsApp" value="{{ old('reason') }}">
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label small mb-0">Montant encaissé (FCFA)</label>
                                    <input type="number" name="amount" class="form-control form-control-sm" value="{{ old('amount', 0) }}" min="0" max="1000000" step="1">
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-sm btn-warning w-100">+ Ajouter</button>
                                </div>
                            </form>
                            <div class="small text-muted mt-2">Ton nom (compte connecté) ou ton rôle est enregistré dans l'historique. Laisse 0 si c'est un cadeau.</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Joueurs --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-bold d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>🎮 Joueurs ({{ $players->total() }})</span>
            <form method="GET" action="{{ route('admin.akwaba.index') }}" class="d-flex gap-2">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Nom ou e-mail" style="min-width:220px;">
                <button class="btn btn-sm btn-outline-dark">Rechercher</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Joueur</th>
                            <th>Débloqué</th>
                            <th class="text-end">Indices</th>
                            <th>Indice gratuit utilisé</th>
                            <th>Chapitre max</th>
                            <th>Fins</th>
                            <th>Dernière activité</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($players as $p)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $p->name }}</div>
                                    <div class="text-muted small">{{ $p->email }}</div>
                                </td>
                                <td>
                                    @if($p->unlocked_via === 'purchase')
                                        <span class="badge bg-success">Oui · payé</span>
                                    @elseif($p->unlocked_via === 'grant')
                                        <span class="badge bg-info">Oui · offert</span>
                                    @else
                                        <span class="badge bg-secondary">Non</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ $p->hints }}</td>
                                <td class="small">
                                    @if($p->free_hint_at)
                                        <span class="badge bg-secondary" title="{{ $p->free_hint_at->format('d/m/Y H:i') }}">Oui</span>
                                    @else
                                        <span class="badge bg-light text-dark border">Non</span>
                                    @endif
                                </td>
                                <td>{{ $chapter($p->max) }}</td>
                                <td class="small">{{ collect($p->fins)->map(fn ($f) => $finLabels[$f] ?? $f)->join(', ') ?: '—' }}</td>
                                <td class="small text-muted">{{ $p->last_activity?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.akwaba.index', ['user' => $p->id]) }}" class="btn btn-sm btn-outline-primary">Gérer</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted p-4">Aucun joueur.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($players->hasPages())
            <div class="card-footer bg-white">{{ $players->links() }}</div>
        @endif
    </div>

    {{-- Historique des indices --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-bold d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span>📜 Historique des indices{{ $selected ? ' · ' . $selected->name : '' }} ({{ $ledger->total() }})</span>
            <form method="GET" action="{{ route('admin.akwaba.index') }}" class="d-flex flex-wrap gap-2">
                @if($selected)<input type="hidden" name="user" value="{{ $selected->id }}">@endif
                <input type="text" name="ledger_q" value="{{ request('ledger_q') }}" class="form-control form-control-sm" placeholder="Joueur ou référence" style="min-width:200px;">
                <select name="reason" class="form-select form-select-sm" style="width:auto;">
                    <option value="">Tous</option>
                    @foreach($reasonBadges as $key => [$class, $label])
                        <option value="{{ $key }}" @selected(request('reason') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-outline-dark">Filtrer</button>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Joueur</th>
                            <th>Type</th>
                            <th class="text-end">Mouvement</th>
                            <th class="text-end">Montant</th>
                            <th>Référence / motif</th>
                            <th>Par</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ledger as $line)
                            @php [$badgeClass, $badgeLabel] = $reasonBadges[$line->reason] ?? ['bg-light text-dark', $line->reason]; @endphp
                            <tr>
                                <td class="small text-muted text-nowrap">{{ $line->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="small">
                                    @if($line->user)
                                        <a href="{{ route('admin.akwaba.index', ['user' => $line->user_id]) }}">{{ $line->user->name }}</a>
                                    @else
                                        #{{ $line->user_id }}
                                    @endif
                                </td>
                                <td><span class="badge {{ $badgeClass }}">{{ $badgeLabel }}</span></td>
                                <td class="text-end fw-semibold {{ $line->delta < 0 ? 'text-danger' : 'text-success' }}">{{ $line->delta > 0 ? '+' : '' }}{{ $line->delta }}</td>
                                <td class="text-end small">{{ $line->amount ? $fcfa($line->amount) : '—' }}</td>
                                <td class="small">
                                    @if($line->payment_reference)<span class="font-monospace">{{ $line->payment_reference }}</span>@endif
                                    @if($line->note)<div class="text-muted">{{ $line->note }}</div>@endif
                                </td>
                                <td class="small text-muted">{{ $line->admin_label ?? ($line->reason === 'purchase' ? 'Paystack' : '—') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted p-4">Aucun mouvement.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($ledger->hasPages())
            <div class="card-footer bg-white">{{ $ledger->links() }}</div>
        @endif
    </div>

</div>

<script>
    document.querySelectorAll('form.js-confirm').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });
</script>
@endsection
