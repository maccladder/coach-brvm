@extends('layouts.admin')

@section('title', 'Dashboard Administrateur – Coach BRVM')

@push('styles')
<style>
    .dash-card {
        border: 1px solid rgba(0,0,0,.08);
        border-radius: .6rem;
        box-shadow: 0 1px 4px rgba(0,0,0,.06);
        transition: transform .15s, box-shadow .15s;
        text-decoration: none;
        display: block;
        height: 100%;
    }
    .dash-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 14px rgba(0,0,0,.12);
    }
    .dash-card .card-body { padding: 1.25rem 1.35rem; }

    /* cards colorées : on garde les classes bg-* existantes */
    .dash-card.bg-light  { border-color: #dee2e6; }
    .dash-card.bg-dark   { border-color: transparent; }
    .dash-card.bg-primary{ border-color: transparent; }
    .dash-card.bg-success{ border-color: transparent; }
    .dash-card.bg-info   { border-color: transparent; }
    .dash-card.bg-warning{ border-color: transparent; }
    .dash-card.bg-secondary{ border-color: transparent; }

    .dash-card .card-icon {
        width: 42px; height: 42px;
        border-radius: .45rem;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .dash-card .card-arrow {
        font-size: .78rem;
        opacity: .55;
    }

    /* table états financiers */
    #table-financials td, #table-financials th {
        padding: .7rem 1rem;
        vertical-align: middle;
    }
    #table-financials thead th {
        font-size: .78rem;
        text-transform: uppercase;
        letter-spacing: .04em;
        color: #6c757d;
        border-bottom: 2px solid #dee2e6;
    }

    /* section title */
    .section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #343a40;
        letter-spacing: .01em;
    }

    /* quick-links barre */
    .quick-links a {
        font-size: .82rem;
        color: #495057;
        text-decoration: none;
        padding: .3rem .6rem;
        border-radius: .3rem;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        transition: background .12s;
    }
    .quick-links a:hover { background: #e9ecef; color: #212529; }
</style>
@endpush

@section('content')
<div class="container py-4" style="max-width:1200px;">

    {{-- ── HEADER ─────────────────────────────────────────── --}}
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Dashboard Administrateur</h2>
            <p class="text-muted mb-0" style="font-size:.9rem;">
                Centre de pilotage : contenus, marketplace, stats et opérations.
            </p>
        </div>

        {{-- Actions principales --}}
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <a href="{{ route('admin.documents.create') }}" class="btn btn-dark btn-sm fw-semibold">
                <i class="bi bi-plus-lg me-1"></i>Ajouter un document
            </a>
            <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-dark btn-sm fw-semibold">
                <i class="bi bi-book me-1"></i>Documents
            </a>
            <a href="{{ route('admin.analytics.index') }}" class="btn btn-outline-primary btn-sm fw-semibold">
                <i class="bi bi-bar-chart-line me-1"></i>Analytics
            </a>
        </div>
    </div>

    {{-- ── QUICK LINKS ─────────────────────────────────────── --}}
    <div class="quick-links d-flex flex-wrap gap-1 mb-4 p-2 bg-white rounded border">
        <a href="{{ route('admin.payouts.index') }}">
            <i class="bi bi-cash-stack"></i> Reversements
        </a>
        <a href="{{ route('admin.marketplace.index') }}">
            <i class="bi bi-shop"></i> Marketplace
        </a>
        <a href="{{ route('admin.users.index') }}">
            <i class="bi bi-people"></i> Utilisateurs
        </a>
        <a href="{{ route('admin.emails.index') }}">
            <i class="bi bi-envelope"></i> Emails
        </a>
        <a href="{{ route('admin.financial_reports.index', ['year' => 2025]) }}">
            <i class="bi bi-file-earmark-text"></i> États financiers 2025
        </a>
        <a href="{{ route('admin.topups.index') }}">
            <i class="bi bi-credit-card"></i> Topups Wallet
        </a>
        <a href="{{ route('admin.announcements.index') }}">
            <i class="bi bi-megaphone"></i> Annonces
        </a>
        <a href="{{ route('admin.performances.index') }}">
            <i class="bi bi-graph-up-arrow"></i> Performances
        </a>
        <a href="{{ route('admin.analytics.index') }}">
            <i class="bi bi-bar-chart-line"></i> Analytics visiteurs
        </a>
    </div>

    {{-- ── CARDS RAPIDES ───────────────────────────────────── --}}
    <div class="row g-3 mb-4">

        {{-- Documents --}}
        <div class="col-6 col-md-4 col-lg-3">
            <div class="dash-card bg-light">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-white border">📚</div>
                        <span class="badge bg-dark ms-auto">PDF</span>
                    </div>
                    <a href="{{ route('admin.documents.index') }}" class="fw-bold mb-1 d-block text-dark text-decoration-none">Documents</a>
                    <p class="small text-muted mb-3">Études de marché, business plans, dossiers de financement.</p>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <span class="badge bg-white text-dark border" style="font-size:.7rem;">Étude</span>
                        <span class="badge bg-white text-dark border" style="font-size:.7rem;">Business plan</span>
                        <span class="badge bg-white text-dark border" style="font-size:.7rem;">Financement</span>
                    </div>
                    <a href="{{ route('admin.documents.create') }}" class="btn btn-sm btn-dark fw-semibold">
                        <i class="bi bi-plus-lg me-1"></i>Ajouter
                    </a>
                </div>
            </div>
        </div>

        {{-- Reversements vendeurs --}}
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.payouts.index') }}" class="dash-card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-white bg-opacity-10 border border-light border-opacity-25">💸</div>
                        <span class="badge bg-warning text-dark ms-auto">Payouts</span>
                    </div>
                    <div class="fw-bold mb-1">Reversements vendeurs</div>
                    <p class="small mb-3 opacity-75">Approuver, rejeter, marquer payé et suivre l'historique.</p>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-light text-dark" style="font-size:.7rem;">Demandes</span>
                        <span class="badge bg-light text-dark" style="font-size:.7rem;">Validation</span>
                        <span class="badge bg-light text-dark" style="font-size:.7rem;">Paiement</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- Marketplace --}}
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.marketplace.index') }}" class="dash-card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-dark bg-opacity-10">🛍️</div>
                        <span class="badge bg-dark text-white ms-auto">Produits</span>
                    </div>
                    <div class="fw-bold mb-1">Marketplace</div>
                    <p class="small mb-3">PDF, vidéos, logiciels — covers, prix et assets.</p>
                    <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-light text-dark" style="font-size:.7rem;">Créer</span>
                        <span class="badge bg-light text-dark" style="font-size:.7rem;">Catégories</span>
                        <span class="badge bg-light text-dark" style="font-size:.7rem;">Assets</span>
                    </div>
                </div>
            </a>
        </div>

        {{-- BOC journalières --}}
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.bocs.index') }}" class="dash-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-white bg-opacity-10 border border-light border-opacity-25">📊</div>
                        <span class="badge bg-light text-dark ms-auto">BOC</span>
                    </div>
                    <div class="fw-bold mb-1">BOC journalières</div>
                    <p class="small mb-0 opacity-75">Uploads, retards et fichiers manquants.</p>
                </div>
            </a>
        </div>

        {{-- États financiers --}}
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.financial_reports.index', ['year' => 2025]) }}" class="dash-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-white bg-opacity-10 border border-light border-opacity-25">📄</div>
                        <span class="badge bg-light text-dark ms-auto">2025</span>
                    </div>
                    <div class="fw-bold mb-1">États financiers</div>
                    <p class="small mb-0 opacity-75">Upload & suivi : T1, S1, T3 et Annuel.</p>
                </div>
            </a>
        </div>

        {{-- Performances --}}
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.performances.index') }}" class="dash-card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-white bg-opacity-10 border border-light border-opacity-25">📈</div>
                        <span class="badge bg-warning text-dark ms-auto">7 jours</span>
                    </div>
                    <div class="fw-bold mb-1">Performances</div>
                    <p class="small mb-0 opacity-75">Variations (%) par société sur les 7 derniers jours de BOC.</p>
                </div>
            </a>
        </div>

        {{-- Annonces --}}
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.announcements.index') }}" class="dash-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-white bg-opacity-10 border border-light border-opacity-25">📣</div>
                        <span class="badge bg-light text-dark ms-auto">Annonces</span>
                    </div>
                    <div class="fw-bold mb-1">Gestion des annonces</div>
                    <p class="small mb-0 opacity-75">Calendrier AG, communiqués, infos BRVM.</p>
                </div>
            </a>
        </div>

        {{-- Utilisateurs --}}
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.users.index') }}" class="dash-card bg-secondary text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-white bg-opacity-10 border border-light border-opacity-25">👥</div>
                        <span class="badge bg-light text-dark ms-auto">Comptes</span>
                    </div>
                    <div class="fw-bold mb-1">Utilisateurs</div>
                    <p class="small mb-0 opacity-75">Nom, email, date d'inscription.</p>
                </div>
            </a>
        </div>

        {{-- Topups Wallet --}}
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.topups.index') }}" class="dash-card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-white bg-opacity-10 border border-light border-opacity-25">💳</div>
                        <span class="badge bg-light text-dark ms-auto">Wallet</span>
                    </div>
                    <div class="fw-bold mb-1">Topups Wallet</div>
                    <p class="small mb-0 opacity-75">Rechargements : validés, en attente, échoués.</p>
                </div>
            </a>
        </div>

        {{-- Emails --}}
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.emails.index') }}" class="dash-card">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon bg-success bg-opacity-10 border border-success border-opacity-25">📧</div>
                        <span class="badge bg-success ms-auto">Emails</span>
                    </div>
                    <div class="fw-bold mb-1 text-success">Emails utilisateurs</div>
                    <p class="small mb-0 text-muted">Envoyer à tous ou aux utilisateurs sélectionnés.</p>
                </div>
            </a>
        </div>

        {{-- Logs stagiaire (admin only) --}}
        @if(session('is_admin'))
        <div class="col-6 col-md-4 col-lg-3">
            <a href="{{ route('admin.stagiaire.logs') }}" class="dash-card" style="border-color: rgba(255,140,0,.25);">
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="card-icon" style="background:rgba(255,140,0,.1);border:1px solid rgba(255,140,0,.25);">📋</div>
                        <span class="badge ms-auto" style="background:rgba(255,140,0,.15);color:#ff8c00;border:1px solid rgba(255,140,0,.3);">Stagiaire</span>
                    </div>
                    <div class="fw-bold mb-1" style="color:#e07800;">Logs stagiaire</div>
                    <p class="small mb-0 text-muted">Activité journalière : connexions, actions effectuées.</p>
                </div>
            </a>
        </div>
        @endif

    </div>

    {{-- ── SECTION ÉTATS FINANCIERS ANALYSÉS ──────────────── --}}
    <div class="card border-0 shadow-sm" style="border-radius:.6rem;">
        <div class="card-body p-0">

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 px-4 py-3 border-bottom">
                <div>
                    <div class="section-title">
                        <i class="bi bi-file-earmark-bar-graph me-2 text-info"></i>Derniers états financiers analysés
                    </div>
                    <div class="text-muted" style="font-size:.78rem;">Analyses clients payées ou en cours</div>
                </div>
                <a href="{{ route('admin.financial_reports.index', ['year' => 2025]) }}"
                   class="btn btn-sm btn-outline-info fw-semibold">
                    <i class="bi bi-archive me-1"></i>Archive 2025
                </a>
            </div>

            @if($financials->isEmpty())
                <div class="px-4 py-4 text-muted" style="font-size:.9rem;">
                    <i class="bi bi-inbox me-2"></i>Aucun état financier analysé pour l'instant.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0" id="table-financials">
                        <thead>
                            <tr>
                                <th>Intitulé</th>
                                <th>Entreprise</th>
                                <th>Période</th>
                                <th>Statut</th>
                                <th class="text-end">Montant</th>
                                <th>Date</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($financials as $f)
                                <tr>
                                    <td class="fw-semibold">{{ $f->title }}</td>
                                    <td>{{ $f->company }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $f->period }}</span>
                                    </td>
                                    <td>
                                        @switch($f->status)
                                            @case('paid')
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>Payé
                                                </span>
                                                @break
                                            @case('pending')
                                                <span class="badge bg-warning text-dark">
                                                    <i class="bi bi-clock me-1"></i>En attente
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-x-circle me-1"></i>Échec
                                                </span>
                                                @break
                                            @case('abandoned')
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-dash-circle me-1"></i>Abandonné
                                                </span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark border">Inconnu</span>
                                        @endswitch
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($f->amount, 0, ',', ' ') }} <span class="text-muted fw-normal" style="font-size:.78rem;">FCFA</span>
                                    </td>
                                    <td class="text-muted" style="font-size:.83rem;">
                                        {{ $f->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td>
                                        <a href="{{ route('client-financials.show', $f->id) }}"
                                           class="btn btn-sm btn-primary fw-semibold">
                                            Voir <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
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
@endsection
