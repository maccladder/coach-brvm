@extends('layouts.admin')

@section('title', 'Nouvelle actualité')

@section('content')
<div class="container py-5" style="max-width: 900px;">
    <h2 class="fw-bold mb-4">Nouvelle actualité</h2>

    <form action="{{ route('admin.news.store') }}" method="POST" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body p-4">
            @include('admin.news.form', ['news' => null])
        </div>
    </form>
</div>
@endsection
