@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width:900px;">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">🔔 Notifications</h3>

        <form method="POST" action="{{ route('notifications.readAll') }}">
            @csrf
            <button class="btn btn-outline-secondary btn-sm">
                Tout marquer comme lu
            </button>
        </form>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            @forelse($notifications as $n)
                @php
                    $isUnread = is_null($n->read_at);
                    $title = $n->data['title'] ?? 'Notification';
                    $message = $n->data['message'] ?? '';
                @endphp

                <div class="d-flex justify-content-between align-items-start py-3 border-bottom">
                    <div>
                        <div class="fw-semibold">
                            @if($isUnread)
                                <span class="badge bg-danger me-2">new</span>
                            @endif
                            {{ $title }}
                        </div>
                        @if($message)
                            <div class="text-muted small mt-1">{{ $message }}</div>
                        @endif
                        <div class="text-muted small mt-1">
                            {{ $n->created_at->diffForHumans() }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                        @csrf
                        <button class="btn btn-sm btn-dark">
                            Ouvrir
                        </button>
                    </form>
                </div>
            @empty
                <div class="text-center text-muted py-4">Aucune notification.</div>
            @endforelse

        </div>
    </div>

    <div class="mt-3">
        {{ $notifications->links() }}
    </div>

</div>
@endsection
