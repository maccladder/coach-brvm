@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width: 500px;">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">

            <h3 class="fw-bold text-center mb-4">Espace Admin</h3>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.login') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label fw-semibold">Code secret</label>
                    <input type="password"
                           name="code"
                           class="form-control @error('code') is-invalid @enderror"
                           placeholder="Entrez le code admin"
                           required>
                    @error('code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button class="btn btn-primary w-100">
                    Se connecter
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
