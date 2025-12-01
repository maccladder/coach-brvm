@extends('layouts.app')
@section('content')
<div class="container py-4">
  <h3>🧠 Générer un résumé pour une date précise</h3>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <form method="POST" action="{{ route('summaries.generate') }}">
    @csrf
    <div class="mb-3">
      <label for="date" class="form-label">Date à résumer :</label>
      <input type="date" name="date" id="date" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Générer le résumé</button>
  </form>
</div>
@endsection
