{{-- resources/views/dashboard.blade.php (ou ta vue "Mon espace") --}}
@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold mb-0">Mon espace</h3>
            <div class="text-muted">
                Bienvenue, {{ auth()->user()->name }} 👋
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('landing') }}" class="btn btn-outline-secondary btn-sm">
                ← Retour accueil
            </a>

            {{-- <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn btn-outline-danger btn-sm">Déconnexion</button>
            </form> --}}
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-1">🎓 Mes cours</div>
                    <div class="text-muted small mb-3">
                        Accès à tes formations payées.
                    </div>
                    <a href="#" class="btn btn-primary btn-sm disabled">Ouvrir (bientôt)</a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-1">💼 Mon portefeuille</div>
                    <div class="text-muted small mb-3">
                        Solde virtuel + historique.
                    </div>

                    {{-- ✅ Lien portefeuille --}}
                    <a href="{{ route('wallet.index') }}" class="btn btn-primary btn-sm">
                        Ouvrir
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-1">📄 Mes analyses</div>
                    <div class="text-muted small mb-3">
                        BOC & états financiers commandés.
                    </div>
                    <a href="#" class="btn btn-primary btn-sm disabled">Ouvrir (bientôt)</a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
