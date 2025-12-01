@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">BOC des clients</h4>
    <a href="{{ route('client-bocs.create') }}" class="btn btn-sm btn-primary">
      + Nouveau BOC
    </a>
  </div>

  @if($bocs->isEmpty())
    <div class="alert alert-info">
      Aucun BOC client pour l’instant.
    </div>
  @else
    <div class="list-group">
      @foreach($bocs as $boc)
        <a href="{{ route('client-bocs.show', $boc) }}" class="list-group-item list-group-item-action">
          <div class="d-flex justify-content-between">
            <div>
              <strong>{{ $boc->title }}</strong><br>
              <small class="text-muted">
                BOC du {{ optional($boc->boc_date)->format('d/m/Y') }} – fichier : {{ $boc->original_filename }}
              </small>
            </div>
            <div>
              <small class="text-muted">{{ $boc->created_at->format('d/m/Y H:i') }}</small>
            </div>
          </div>
        </a>
      @endforeach
    </div>
  @endif
</div>
@endsection
