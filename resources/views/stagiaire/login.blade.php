@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width: 500px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <h3 class="fw-bold text-center mb-1">Espace Stagiaire</h3>
            <p class="text-center text-muted small mb-4">Coach BRVM</p>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('stagiaire.login') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Code d'accès</label>
                    <input type="password"
                           name="code"
                           class="form-control @error('code') is-invalid @enderror"
                           placeholder="Entrez votre code stagiaire"
                           required autofocus>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button class="btn btn-primary w-100">
                    Accéder →
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('admin.login.form') }}" class="text-muted small">
                    Accès administrateur
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
