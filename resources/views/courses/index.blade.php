@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width:1100px;">

    {{-- Titre --}}
    <div class="mb-4">
        <h2 class="fw-bold mb-1">🎓 Formations Coach-BRVM</h2>
        <p class="text-muted">
            Formations pratiques pour investir intelligemment à la BRVM.
        </p>
    </div>

    {{-- Messages --}}
    @if(session('success'))
        <div class="alert alert-success shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="row g-4">
        @forelse ($courses as $course)
            @php
                $isBought = auth()->check()
                    && auth()->user()->coursePurchases()
                        ->where('course_id', $course->id)
                        ->whereNotNull('paid_at')
                        ->exists();

                // Covers
                $covers = [
                    'brvm-debutant' => asset('courses/brvm-debutant.jpg'),
                    'brvm-intermediaire' => asset('courses/brvm-intermediaire.jpg'),
                    'brvm-pratique-outils-analyse-portefeuille-virtuel' => asset('courses/brvm-pratique.jpg'),
                ];

                $cover = $covers[$course->slug] ?? asset('courses/brvm-debutant.jpg');
            @endphp

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">

                    {{-- Cover --}}
                    <div class="position-relative">
                        <img src="{{ $cover }}"
                             alt="Aperçu du cours"
                             class="w-100"
                             style="height:160px; object-fit:cover;">

                        {{-- Play --}}
                        <div class="position-absolute top-50 start-50 translate-middle">
                            <div class="bg-dark bg-opacity-50 rounded-circle d-flex align-items-center justify-content-center"
                                 style="width:56px; height:56px;">
                                <span class="text-white fs-4">▶</span>
                            </div>
                        </div>

                        {{-- Badge acheté --}}
                        @if($isBought)
                            <span class="badge bg-success position-absolute top-0 start-0 m-2">
                                ✔ Déjà acheté
                            </span>
                        @endif
                    </div>

                    <div class="card-body d-flex flex-column p-4">

                        {{-- Titre --}}
                        <h5 class="fw-semibold mb-2">
                            {{ $course->title }}
                        </h5>

                        {{-- Description --}}
                        <p class="text-muted small flex-grow-1 mb-3">
                            {{ \Illuminate\Support\Str::limit($course->description, 130) }}
                        </p>

                        {{-- Prix + actions --}}
                        <div class="d-flex justify-content-between align-items-center">

                            <div class="fw-bold text-success fs-6">
                                {{ number_format($course->price_fcfa, 0, ',', ' ') }} FCFA
                            </div>

                            @auth
                                @if($isBought)
                                    <a href="{{ route('courses.show', $course->slug) }}"
                                       class="btn btn-success btn-sm rounded-pill px-3">
                                        ▶ Continuer
                                    </a>
                                @else
                                    <div class="d-flex gap-2">
                                        {{-- CinetPay --}}
                                        {{-- <form method="POST" action="{{ route('courses.buy', $course) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-primary btn-sm rounded-pill px-3">
                                                CinetPay
                                            </button>
                                        </form> --}}

                                        {{-- Paystack --}}
                                        <form method="POST" action="{{ route('courses.buy.paystack', $course) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="btn btn-dark btn-sm rounded-pill px-3">
                                                Payer
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            @else
                                <a href="{{ route('login') }}"
                                   class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    Se connecter
                                </a>
                            @endauth
                        </div>

                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">
                    Aucune formation disponible pour le moment.
                </div>
            </div>
        @endforelse
    </div>

</div>
@endsection
