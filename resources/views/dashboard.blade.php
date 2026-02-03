{{-- resources/views/dashboard.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">

    {{-- En-tête --}}
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

            {{--
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn btn-outline-danger btn-sm">Déconnexion</button>
            </form>
            --}}
        </div>
    </div>

    {{-- Cartes --}}
    <div class="row g-3">

        {{-- 🎓 Mes cours --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-1">🎓 Mes cours</div>
                    <div class="text-muted small mb-3">
                        Accès à tes formations payées.
                    </div>
                    <a href="{{ route('courses.my') }}" class="btn btn-primary btn-sm">
                        Ouvrir
                    </a>
                </div>
            </div>
        </div>

        {{-- 🧾 Mes produits Marketplace --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-1">🧾 Mes produits</div>
                    <div class="text-muted small mb-3">
                        Livres PDF, logiciels et contenus achetés.
                    </div>
                    <a href="{{ route('my.products') }}" class="btn btn-primary btn-sm">
                        Ouvrir
                    </a>
                </div>
            </div>
        </div>

        {{-- 💼 Mon portefeuille --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-1">💼 Mon portefeuille</div>
                    <div class="text-muted small mb-3">
                        Solde virtuel + historique.
                    </div>
                    <a href="{{ route('wallet.index') }}" class="btn btn-primary btn-sm">
                        Ouvrir
                    </a>
                </div>
            </div>
        </div>

        {{-- 📚 Mes documents (études / business plans) --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-1">📚 Mes documents</div>
                    <div class="text-muted small mb-3">
                        Études de marché & business plans achetés.
                    </div>
                    <a href="{{ route('documents.mine') }}" class="btn btn-primary btn-sm">
                        Ouvrir
                    </a>
                </div>
            </div>
        </div>

        {{-- 📄 Mes analyses --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="fw-semibold mb-1">📄 Mes analyses</div>
                    <div class="text-muted small mb-3">
                        BOC & états financiers commandés.
                    </div>
                    <a href="#" class="btn btn-primary btn-sm disabled">
                        Ouvrir (bientôt)
                    </a>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
