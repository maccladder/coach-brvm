@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h2 class="mb-4">🎓 Formations Coach-BRVM</h2>

    {{-- messages --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        @forelse ($courses as $course)
            @php
                // ✅ acheté ? (même logique que ton show: paid_at not null)
                $isBought = auth()->check()
                    && auth()->user()->coursePurchases()
                        ->where('course_id', $course->id)
                        ->whereNotNull('paid_at')
                        ->exists();
            @endphp

            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">

                        <h5 class="card-title">{{ $course->title }}</h5>

                        <p class="card-text text-muted flex-grow-1">
                            {{ \Illuminate\Support\Str::limit($course->description, 120) }}
                        </p>

                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="fw-bold text-success">
                                {{ number_format($course->price_fcfa) }} FCFA
                            </span>

                            @auth
                                @if($isBought)
                                    {{-- ✅ Déjà acheté --}}
                                    <a href="{{ route('courses.show', $course->slug) }}"
                                       class="btn btn-success btn-sm">
                                        ▶️ Continuer
                                    </a>
                                @else
                                    {{-- 🔥 Acheter --}}
                                    <form method="POST" action="{{ route('courses.buy', $course) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            Acheter
                                        </button>
                                    </form>
                                @endif
                            @else
                                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-sm">
                                    Se connecter
                                </a>
                            @endauth
                        </div>

                    </div>
                </div>
            </div>
        @empty
            <p>Aucune formation disponible pour le moment.</p>
        @endforelse
    </div>

</div>
@endsection
