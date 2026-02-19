@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:1100px;">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h3 class="mb-1">📄 Mes études / business plans</h3>
            <div class="text-muted">L’admin doit valider avant publication.</div>
        </div>
        <a class="btn btn-dark" href="{{ route('vendor.documents.create') }}">
            <i class="bi bi-plus-circle"></i> Nouveau document
        </a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($documents as $d)
                    @php
                        $badge = match($d->status) {
                            'pending' => 'warning',
                            'published' => 'success',
                            'rejected' => 'danger',
                            default => 'secondary'
                        };
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $d->title }}</td>
                        <td class="text-muted">{{ $d->type }}</td>
                        <td>{{ number_format((int)$d->price, 0, ',', ' ') }} FCFA</td>
                        <td>
                            <span class="badge text-bg-{{ $badge }}">{{ $d->status }}</span>
                            @if($d->status === 'rejected' && $d->rejected_note)
                                <div class="small text-danger mt-1">Motif : {{ $d->rejected_note }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-dark" href="{{ route('vendor.documents.edit', $d) }}">Gérer</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Aucun document pour l’instant.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-body border-top bg-white">
            {{ $documents->links() }}
        </div>
    </div>
</div>
@endsection
