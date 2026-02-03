@extends('layouts.admin')

@section('content')
<div class="container py-4">
    <h3 class="mb-3">Ajouter un document</h3>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data">
                @csrf

                @include('admin.documents.partials.form', ['document' => null])

                <div class="d-flex gap-2">
                    <button class="btn btn-primary">Enregistrer</button>
                    <a href="{{ route('admin.documents.index') }}" class="btn btn-light">Retour</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
