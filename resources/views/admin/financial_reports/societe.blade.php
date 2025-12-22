@extends('layouts.admin')

@section('content')
@php
    $labels = ['Q1'=>'T1', 'S1'=>'S1', 'Q3'=>'T3', 'FY'=>'Annuel'];

    $badge = function ($status) {
        return match($status) {
            'published' => 'success',
            'not_published' => 'danger',
            'pending' => 'secondary',
            default => 'secondary'
        };
    };

    $statusLabel = function ($status) {
        return match($status) {
            'published' => 'Publié',
            'not_published' => 'Non publié',
            'pending' => 'En attente',
            default => 'En attente'
        };
    };
@endphp

<div class="container py-4" style="max-width: 900px;">
    <a href="{{ route('admin.financial_reports.index', ['year'=>$year]) }}" class="text-decoration-none small">
        ← Retour à la liste
    </a>

    <div class="d-flex align-items-start justify-content-between mt-2 mb-3">
        <div>
            <h3 class="fw-bold mb-0">{{ $societe->name }}</h3>
            <div class="text-muted small">{{ $societe->code }} • {{ $societe->country }} @if($societe->sector) • {{ $societe->sector }} @endif</div>
        </div>
        <span class="badge text-bg-dark align-self-center">{{ $year }}</span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-bold">Erreur :</div>
            <ul class="mb-0">
                @foreach($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        @foreach($periods as $p)
            @php
                $r = $reports[$p] ?? null;
                $status = $r->status ?? 'pending';
                $fileUrl = ($r && $r->file_path) ? asset('storage/' . $r->file_path) : null;
            @endphp

            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-bold">{{ $labels[$p] ?? $p }}</div>
                            <span class="badge text-bg-{{ $badge($status) }}">{{ $statusLabel($status) }}</span>
                        </div>

                        @if($fileUrl)
                            <div class="mb-2">
                                <a href="{{ $fileUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                    Voir le PDF
                                </a>
                            </div>
                        @endif

                        <form method="POST"
                              action="{{ route('admin.financial_reports.upload', ['year'=>$year,'societe'=>$societe->id,'period'=>$p]) }}"
                              enctype="multipart/form-data"
                              class="d-flex gap-2 mb-2">
                            @csrf
                            <input type="file" name="pdf" accept="application/pdf" class="form-control form-control-sm" required>
                            <button class="btn btn-success btn-sm" type="submit">Upload</button>
                        </form>

                        <form method="POST"
                              action="{{ route('admin.financial_reports.not_published', ['year'=>$year,'societe'=>$societe->id,'period'=>$p]) }}">
                            @csrf
                            <button class="btn btn-outline-danger btn-sm" type="submit">
                                Marquer “Non publié”
                            </button>
                        </form>

                        <div class="text-muted small mt-2">
                            @if($r && $r->published_at)
                                Publié le : {{ $r->published_at->format('d/m/Y') }}
                            @elseif($fileUrl)
                                PDF présent
                            @else
                                —
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
