@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:1100px;">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">Annonces BRVM</h2>
            <div class="text-muted">Calendrier AG, informations marché, communiqués, etc.</div>
        </div>
    </div>

    @forelse($announcements as $a)
        <a href="{{ route('announcements.show', $a->id) }}" class="text-decoration-none text-dark">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-semibold mb-1">{{ $a->title }}</h5>
                        <span class="badge bg-light text-dark border">{{ $a->public_date }}</span>
                    </div>
                    @if($a->excerpt)
                        <p class="text-muted mb-0">{{ $a->excerpt }}</p>
                    @else
                        <p class="text-muted mb-0">{{ \Illuminate\Support\Str::limit(strip_tags($a->content), 160) }}</p>
                    @endif
                </div>
            </div>
        </a>
    @empty
        <div class="text-muted">Aucune annonce pour le moment.</div>
    @endforelse

    <div class="mt-3">
        {{ $announcements->links() }}
    </div>
</div>
@endsection
