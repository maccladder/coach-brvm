@extends('layouts.admin')

@section('content')
<div class="container py-4" style="max-width:1100px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">🔔 Notifications Admin</h3>
        <form method="POST" action="{{ route('admin.notifications.readAll') }}">
            @csrf
            <button class="btn btn-sm btn-outline-light">Tout marquer comme lu</button>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="list-group list-group-flush">
            @forelse($notifications as $n)
                <div class="list-group-item {{ $n->read_at ? '' : 'bg-light' }}">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div>
                            <div class="fw-semibold">{{ $n->title }}</div>
                            @if($n->message)<div class="text-muted">{{ $n->message }}</div>@endif
                            <div class="text-muted small">{{ $n->created_at->diffForHumans() }}</div>
                        </div>
                        <div class="d-flex gap-2">
                            @if(!$n->read_at)
                                <form method="POST" action="{{ route('admin.notifications.read', $n->id) }}">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-dark">Lire</button>
                                </form>
                            @endif
                            @if($n->url)
                                <a class="btn btn-sm btn-dark" href="{{ $n->url }}">Ouvrir</a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="list-group-item">Aucune notification.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-3">{{ $notifications->links() }}</div>
</div>
@endsection
