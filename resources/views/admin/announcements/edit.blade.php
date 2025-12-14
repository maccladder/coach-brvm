@extends('layouts.admin')

@section('title', 'Modifier annonce')

@section('content')
<div class="container py-5" style="max-width: 900px;">
    <h2 class="fw-bold mb-4">Modifier annonce</h2>

    <form action="{{ route('admin.announcements.update', $announcement->id) }}" method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm">
        @csrf
        @method('PUT')
        <div class="card-body p-4">
            @include('admin.announcements.form', ['announcement' => $announcement])
        </div>
    </form>
</div>
@endsection
