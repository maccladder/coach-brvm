@extends('layouts.app')

@section('content')
<div class="container py-4">

    <h2 class="mb-4">📚 Mes formations</h2>

    @if ($courses->isEmpty())
        <div class="alert alert-info">
            Vous n’avez encore acheté aucune formation.
        </div>
    @else
        <div class="row">
            @foreach ($courses as $course)
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">{{ $course->title }}</h5>

                            <p class="card-text text-muted flex-grow-1">
                                {{ Str::limit($course->description, 120) }}
                            </p>

                            <a href="{{ route('courses.show', $course->slug) }}"
                               class="btn btn-success btn-sm mt-auto">
                                ▶️ Continuer le cours
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
