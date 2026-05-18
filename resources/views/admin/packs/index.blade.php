@extends('layouts.admin')

@section('title', 'Pack BRVM — Admin')

@section('content')
<div class="container py-4" style="max-width:1200px">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-0">📦 Pack BRVM Complet</h4>
            <div class="text-muted small">{{ $payments->total() }} acheteur(s)</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Utilisateur</th>
                        <th>Date achat</th>
                        <th class="text-center">Token WhatsApp</th>
                        <th>Date utilisation</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        @php $token = $latestTokens[$payment->user_id] ?? null; @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ optional($payment->user)->name ?? '—' }}</div>
                                <div class="text-muted small">{{ optional($payment->user)->email ?? '—' }}</div>
                            </td>
                            <td class="text-muted small text-nowrap">
                                {{ $payment->credited_at?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="text-center">
                                @if(!$token)
                                    <span class="badge bg-secondary">Aucun token</span>
                                @elseif($token->used)
                                    <span class="badge bg-success">Utilisé</span>
                                @else
                                    <span class="badge bg-warning text-dark">Non utilisé</span>
                                @endif
                            </td>
                            <td class="text-muted small text-nowrap">
                                @if($token?->used_at)
                                    {{ $token->used_at->format('d/m/Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="d-flex gap-2 justify-content-end">
                                    {{-- Renvoyer l'email --}}
                                    @if($payment->user)
                                        <form method="POST" action="{{ route('admin.packs.resend', $payment->user) }}"
                                              onsubmit="return confirm('Renvoyer un nouveau lien à {{ addslashes(optional($payment->user)->name) }} ?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-envelope me-1"></i>Renvoyer
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Réinitialiser token (visible seulement si token utilisé) --}}
                                    @if($token && $token->used)
                                        <form method="POST" action="{{ route('admin.packs.tokens.reset', $token) }}"
                                              onsubmit="return confirm('Réinitialiser ce token ?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Réinitialiser
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                Aucun achat du Pack BRVM pour l'instant.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $payments->links() }}
    </div>

</div>
@endsection
