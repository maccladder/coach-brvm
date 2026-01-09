@extends('layouts.admin')

@section('title', 'Gestion des cours')

@section('content')
<div class="container py-4">

    <h2 class="mb-4">🎓 Cours disponibles</h2>

    <div class="row g-3">
        @foreach($courses as $course)
            <div class="col-md-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h5>{{ $course->title }}</h5>

                        <p class="text-muted flex-grow-1">
                            {{ Str::limit($course->description, 100) }}
                        </p>

                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-success">
                                {{ number_format($course->price_fcfa) }} FCFA
                            </span>
                            <span class="badge bg-primary">
                                {{ $course->buyers_count }} acheteurs
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
