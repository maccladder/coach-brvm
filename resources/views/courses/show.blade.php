@extends('layouts.app')

@section('content')
<div class="container py-4">

    <a href="{{ route('courses.my') }}" class="btn btn-link mb-3">
        ← Retour à mes cours
    </a>

    <h2 class="mb-3">{{ $course->title }}</h2>

    @if ($course->description)
        <p class="text-muted mb-4">
            {{ $course->description }}
        </p>
    @endif

    <div class="ratio ratio-16x9 shadow-sm">
        <iframe
            src="{{ $iframe }}"
            style="border: none;"
            loading="lazy"
            allow="accelerometer; gyroscope; autoplay; encrypted-media; picture-in-picture;"
            allowfullscreen>
        </iframe>
    </div>

    <div class="alert alert-light mt-4">
        🔒 Cette vidéo est protégée et accessible uniquement depuis votre compte.
    </div>

</div>
@endsection
