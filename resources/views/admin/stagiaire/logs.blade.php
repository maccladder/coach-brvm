@extends('layouts.admin')

@section('title', 'Logs Stagiaire')

@section('content')
<div class="container py-4" style="max-width:1100px;">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">📋 Activité Stagiaire</h2>
            <div class="text-muted small">
                <span class="me-3">Total enregistré : <strong>{{ $total }}</strong></span>
                <span>Aujourd'hui : <strong>{{ $todayCount }}</strong></span>
            </div>
        </div>
        @if(session('is_admin'))
        <form method="POST" action="{{ route('admin.stagiaire.logs.clear') }}"
              onsubmit="return confirm('Effacer tous les logs ?')">
            @csrf
            <button class="btn btn-sm btn-outline-danger">🗑️ Vider les logs</button>
        </form>
        @endif
    </div>

    {{-- Filtres --}}
    <form method="GET" class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-end">
                <div class="col-sm-4">
                    <label class="form-label small fw-semibold mb-1">Date</label>
                    <input type="date" name="date" class="form-control form-control-sm"
                           value="{{ request('date') }}">
                </div>
                <div class="col-sm-4">
                    <label class="form-label small fw-semibold mb-1">Type d'action</label>
                    <input type="text" name="action" class="form-control form-control-sm"
                           placeholder="ex: login, approve..." value="{{ request('action') }}">
                </div>
                <div class="col-sm-2">
                    <button class="btn btn-sm btn-primary w-100">Filtrer</button>
                </div>
                <div class="col-sm-2">
                    <a href="{{ route('admin.stagiaire.logs') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </div>
        </div>
    </form>

    {{-- Table des logs --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center text-muted py-5">Aucun log enregistré.</div>
            @else
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:160px;">Date / Heure</th>
                            <th style="width:120px;">Action</th>
                            <th>Détail</th>
                            <th style="width:110px;">Méthode</th>
                            <th style="width:120px;">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        <tr>
                            <td class="small text-muted">
                                <div class="fw-semibold text-dark">{{ $log->created_at->format('d/m/Y') }}</div>
                                {{ $log->created_at->format('H:i:s') }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $log->badge_class }} text-{{ in_array($log->badge_class, ['light','warning']) ? 'dark' : 'white' }}">
                                    {{ $log->icon }} {{ $log->action }}
                                </span>
                            </td>
                            <td>
                                @if($log->label)
                                    <div class="small fw-semibold">{{ $log->label }}</div>
                                @endif
                                @if($log->url)
                                    <div class="text-muted" style="font-size:11px;word-break:break-all;">
                                        {{ $log->url }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                @php
                                    $methodColor = match($log->method) {
                                        'POST'   => 'success',
                                        'PUT','PATCH' => 'warning',
                                        'DELETE' => 'danger',
                                        default  => 'secondary',
                                    };
                                @endphp
                                <span class="badge bg-{{ $methodColor }}">{{ $log->method }}</span>
                            </td>
                            <td class="small text-muted">{{ $log->ip }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-3">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
