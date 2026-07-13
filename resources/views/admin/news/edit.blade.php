@extends('layouts.admin')

@section('title', 'Modifier actualité')

@section('content')
<div class="container py-5" style="max-width: 900px;">
    <h2 class="fw-bold mb-4">Modifier actualité</h2>

    <form action="{{ route('admin.news.update', $news->slug) }}" method="POST" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body p-4">
            @include('admin.news.form', ['news' => $news])
        </div>
    </form>
</div>
@endsection
