@extends('layouts.admin')

@section('title', 'Nouvelle annonce')

@section('content')
<div class="container py-5" style="max-width: 900px;">
    <h2 class="fw-bold mb-4">Nouvelle annonce</h2>

    <form action="{{ route('admin.announcements.store') }}" method="POST" enctype="multipart/form-data" class="card border-0 shadow-sm">
        @csrf
        <div class="card-body p-4">
            @include('admin.announcements.form', ['announcement' => null])
        </div>
    </form>
</div>
@endsection
