@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-3">Modifier le document</h3>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.documents.update', $document) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @include('admin.documents.partials.form', ['document' => $document])

                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Mettre à jour</button>
                    <a href="{{ route('admin.documents.index') }}" class="btn btn-light">Retour</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
