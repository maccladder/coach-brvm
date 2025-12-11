@extends('layouts.admin')

@section('title', 'Dashboard Administrateur – Coach BRVM')

@section('content')
<div class="bg-light py-5">
    <div class="container" style="max-width: 1100px;">

        <h1 class="fw-bold mb-4">BOC journalières (à partir du 1er décembre 2025)</h1>

        {{-- Messages flash --}}
        @if(session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger small">{{ session('error') }}</div>
        @endif

        {{-- Détermination automatique J-1 hors week-end --}}
        @php
            $default = now();
            if ($default->isMonday()) {
                $default = $default->subDays(3); // vendredi
            } elseif ($default->isSunday()) {
                $default = $default->subDays(2); // vendredi
            } elseif ($default->isSaturday()) {
                $default = $default->subDay();   // vendredi
            } else {
                $default = $default->subDay();   // J-1 normal
            }
        @endphp

        {{-- Formulaire d’upload --}}
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body">
                <h5 class="fw-semibold mb-3">Uploader une BOC</h5>

                <form method="POST" action="{{ route('admin.bocs.store') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf

                    <div class="col-md-4">
                        <label for="date_boc" class="form-label small">Date de la BOC</label>
                        <input type="date" name="date_boc" id="date_boc"
                            class="form-control @error('date_boc') is-invalid @enderror"
                            value="{{ old('date_boc', $default->toDateString()) }}">
                        @error('date_boc')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-5">
                        <label for="file" class="form-label small">Fichier PDF</label>
                        <input type="file" name="file" id="file"
                            class="form-control @error('file') is-invalid @enderror"
                            accept="application/pdf">
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            📄 Enregistrer la BOC
                        </button>
                    </div>
                </form>

                {{-- Encart explicatif --}}
                <div class="alert alert-info mt-3 mb-0 small">
                    <strong>Infos :</strong>
                    les <strong>samedis</strong>, <strong>dimanches</strong> et
                    <strong>jours fériés officiels BRVM</strong> sont automatiquement exclus du suivi.
                    Le <strong>jour J</strong> est affiché en « en attente » mais n’est jamais compté comme
                    BOC manquante.
                </div>
            </div>
        </div>

        {{-- Tableau des dates --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h5 class="fw-semibold mb-3">Suivi des BOC</h5>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Fichier</th>
                            </tr>
                        </thead>

                        <tbody>
                        @foreach($days as $day)
                            @php $date = $day['date']; @endphp

                            <tr class="{{ $day['is_missing'] ? 'table-danger' : '' }}">
                                <td>
                                    {{ $date->translatedFormat('d/m/Y (D)') }}
                                    @if($day['is_today'])
                                        <span class="badge bg-secondary ms-1">Jour J</span>
                                    @endif
                                </td>

                                <td>
                                    @if($day['has_boc'])
                                        <span class="badge bg-success">BOC disponible</span>
                                    @elseif($day['is_today'])
                                        <span class="badge bg-warning text-dark">En attente (jour en cours)</span>
                                    @else
                                        <span class="badge bg-danger">BOC manquante</span>
                                    @endif
                                </td>

                                <td>
                                    @if($day['has_boc'])
                                        <a href="{{ asset('storage/' . $day['record']->file_path) }}"
                                           class="small text-decoration-none" target="_blank">
                                            📥 Télécharger ({{ $day['record']->original_name ?? 'PDF' }})
                                        </a>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Résumé des stats --}}
                <div class="mt-3 p-3 bg-light rounded-3 small d-flex flex-wrap gap-3">
                    <div>
                        <strong>Jours ouvrés attendus :</strong>
                        {{ $stats['total_days'] ?? 0 }}
                    </div>
                    <div class="text-success">
                        <strong>BOC reçues :</strong>
                        {{ $stats['received'] ?? 0 }}
                    </div>
                    <div class="text-danger">
                        <strong>BOC manquantes :</strong>
                        {{ $stats['missing'] ?? 0 }}
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection
