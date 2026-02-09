@extends('layouts.admin')

@section('title', 'Dashboard Administrateur – Coach BRVM')

@section('content')
<div class="bg-light py-5">
    <div class="container" style="max-width: 1100px;">

        <h1 class="fw-bold mb-4">BOC journalières (à partir du 1er Janvier 2025)</h1>

        {{-- Messages --}}
        @if(session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger small">{{ session('error') }}</div>
        @endif

        {{-- Date par défaut --}}
        @php
            $default = now();
            if ($default->isMonday())      $default = $default->subDays(3);
            elseif ($default->isSunday())  $default = $default->subDays(2);
            elseif ($default->isSaturday())$default = $default->subDay();
            else                           $default = $default->subDay();
        @endphp

        {{-- Upload --}}
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-semibold mb-3">Uploader une BOC</h5>

                <form method="POST" action="{{ route('admin.bocs.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf

                    <div class="col-md-4">
                        <label class="form-label small">Date de la BOC</label>
                        <input type="date" name="date_boc"
                               class="form-control"
                               value="{{ old('date_boc', $default->toDateString()) }}">
                    </div>

                    <div class="col-md-5">
                        <label class="form-label small">Fichier PDF</label>
                        <input type="file" name="file" class="form-control" accept="application/pdf">
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100">
                            📄 Enregistrer la BOC
                        </button>
                    </div>
                </form>

                <div class="alert alert-info small mt-3 mb-0">
                    Les week-ends et jours fériés BRVM sont exclus automatiquement.
                </div>
            </div>
        </div>

        {{-- Tableau --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Fichier</th>
                                <th style="width:120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($days as $day)
                            @php $date = $day['date']; @endphp
                            <tr class="{{ $day['is_missing'] ? 'table-danger' : '' }}">
                                <td class="fw-semibold">
                                    {{ $date->translatedFormat('d/m/Y (D)') }}
                                    @if($day['is_today'])
                                        <span class="badge bg-secondary ms-1">Jour J</span>
                                    @endif
                                </td>

                                <td>
                                    @if($day['has_boc'])
                                        <span class="badge bg-success">BOC disponible</span>
                                    @elseif($day['is_today'])
                                        <span class="badge bg-warning text-dark">En attente</span>
                                    @else
                                        <span class="badge bg-danger">BOC manquante</span>
                                    @endif
                                </td>

                                <td>
                                    @if($day['has_boc'])
                                        <a href="{{ asset('storage/' . $day['record']->file_path) }}"
                                           target="_blank" class="small">
                                            📥 {{ $day['record']->original_name }}
                                        </a>
                                        @if($day['record']->replaced_at)
                                            <div class="text-warning small">
                                                ♻️ remplacée le {{ \Carbon\Carbon::parse($day['record']->replaced_at)->format('d/m/Y H:i') }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td>
                                    @if($day['has_boc'])
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                    data-bs-toggle="dropdown">
                                                ⚙️
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item"
                                                       target="_blank"
                                                       href="{{ asset('storage/' . $day['record']->file_path) }}">
                                                        📥 Télécharger
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button class="dropdown-item text-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#replaceModal{{ $day['record']->id }}">
                                                        ♻️ Remplacer PDF
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>

                                        {{-- Modal --}}
                                        <div class="modal fade" id="replaceModal{{ $day['record']->id }}" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST"
                                                          action="{{ route('admin.bocs.replace', $day['record']) }}"
                                                          enctype="multipart/form-data">
                                                        @csrf
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Remplacer la BOC</h5>
                                                            <button class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="small mb-2">
                                                                Date :
                                                                <strong>{{ \Carbon\Carbon::parse($day['record']->date_boc)->format('d/m/Y') }}</strong>
                                                            </div>

                                                            <input type="file"
                                                                   name="file"
                                                                   class="form-control"
                                                                   accept="application/pdf"
                                                                   required>

                                                            <div class="alert alert-warning small mt-3 mb-0">
                                                                Le fichier sera remplacé et les données recalculées.
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button class="btn btn-outline-secondary"
                                                                    data-bs-dismiss="modal">
                                                                Annuler
                                                            </button>
                                                            <button class="btn btn-warning">
                                                                Remplacer
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $days->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
