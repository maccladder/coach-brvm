@extends('layouts.admin')

@section('title', 'Dashboard Administrateur – Coach BRVM')

@section('content')
<div class="bg-light py-5">
    <div class="container" style="max-width: 1100px;">

        <h1 class="fw-bold mb-4">BOC journalières (à partir du 1er Janvier 2025)</h1>

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

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <h5 class="fw-semibold mb-0">Suivi des BOC</h5>
                        @if(method_exists($days, 'total'))
                            <div class="text-muted small">
                                Affichage
                                <strong>{{ $days->firstItem() ?? 0 }}</strong>–
                                <strong>{{ $days->lastItem() ?? 0 }}</strong>
                                sur <strong>{{ $days->total() }}</strong>
                            </div>
                        @endif
                    </div>

                    {{-- Choix du nombre de lignes par page --}}
                    <form method="GET" action="{{ url()->current() }}" class="d-flex align-items-center gap-2">
                        @foreach(request()->query() as $k => $v)
                            @if($k !== 'per_page' && $k !== 'page')
                                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                            @endif
                        @endforeach

                        <label class="small text-muted mb-0">Lignes / page</label>
                        <select name="per_page" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                            @foreach([25,50,100,150,200] as $n)
                                <option value="{{ $n }}" @selected(($perPage ?? 50) == $n)>{{ $n }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 200px;">Date</th>
                                <th style="width: 180px;">Statut</th>
                                <th>Fichier</th>
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

                {{-- Pagination (Bootstrap 5) --}}
                <div class="d-flex flex-column align-items-center gap-2 mt-3">
                    <div>
                        {{ $days->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                    @if(method_exists($days, 'total'))
                        <div class="text-muted small">
                            Page <strong>{{ $days->currentPage() }}</strong> / <strong>{{ $days->lastPage() }}</strong>
                        </div>
                    @endif
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
