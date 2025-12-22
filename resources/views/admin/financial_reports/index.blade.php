@extends('layouts.admin')

@section('content')
<div class="container py-4" style="max-width: 1100px;">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="fw-bold mb-0">📄 États financiers (Admin) — {{ $year }}</h3>
            <div class="text-muted small">Clique une société pour gérer ses 4 périodes (T1, S1, T3, Annuel).</div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.financial_reports.index', ['year' => 2025]) }}" class="btn btn-outline-secondary btn-sm">2025</a>
            <a href="{{ route('admin.financial_reports.index', ['year' => 2024]) }}" class="btn btn-outline-secondary btn-sm">2024</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Société</th>
                        <th class="text-center" style="width: 120px;">Publiés</th>
                        <th class="text-center" style="width: 140px;">Non publiés</th>
                        <th class="text-end" style="width: 140px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($societes as $s)
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $s->name }}</div>
                            <div class="text-muted small">
                                {{ $s->code }} • {{ $s->country }} @if($s->sector) • {{ $s->sector }} @endif
                            </div>
                        </td>

                        <td class="text-center">
                            <span class="badge text-bg-success">{{ $s->published_count ?? 0 }}/4</span>
                        </td>

                        <td class="text-center">
                            <span class="badge text-bg-danger">{{ $s->not_published_count ?? 0 }}</span>
                        </td>

                        <td class="text-end">
                            <a href="{{ route('admin.financial_reports.societe', ['year'=>$year, 'societe'=>$s->id]) }}"
                               class="btn btn-primary btn-sm">
                                Gérer →
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
