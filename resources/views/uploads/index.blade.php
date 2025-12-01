@extends('layouts.app')

@section('content')
<div class="container py-4">
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  @if(session('ok'))
    <div class="alert alert-success">{{ session('ok') }}</div>
  @endif

  <div class="row g-4">
    {{-- ====== Analyses ====== --}}
    <div class="col-lg-6">
      <h4>Uploader une analyse</h4>
      <form method="POST" action="{{ route('uploads.analysis.store') }}" enctype="multipart/form-data" class="border p-3 rounded">
        @csrf
        <div class="mb-2">
          <label class="form-label">Date</label>
          <input name="as_of_date" type="date" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Titre</label>
          <input name="title" class="form-control" placeholder="Analyse marché BRVM" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Fichier (pdf/csv/xlsx)</label>
          <input name="file" type="file" class="form-control">
        </div>
        <div class="mb-2">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="4"></textarea>
        </div>
        <button class="btn btn-primary">Enregistrer</button>
      </form>

      <h6 class="mt-4">Dernières analyses</h6>
      <ul class="small">
        @foreach($analyses as $a)
          <li>{{ $a->as_of_date->format('Y-m-d') }} — {{ $a->title }}</li>
        @endforeach
      </ul>
    </div>

    {{-- ====== États financiers ====== --}}
    <div class="col-lg-6">
      <h4>Uploader un état financier</h4>
      <form method="POST" action="{{ route('uploads.statement.store') }}" enctype="multipart/form-data" class="border p-3 rounded">
        @csrf
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label">Émetteur</label>
            <input name="issuer" class="form-control" placeholder="UNIWAX CI" required>
          </div>
          <div class="col-6">
            <label class="form-label">Période</label>
            <input name="period" class="form-control" placeholder="FY2024" required>
          </div>
        </div>

        <div class="row g-2 mt-1">
          <div class="col-6">
            <label class="form-label">Type</label>
            <select name="statement_type" class="form-select" required>
              <option value="income">Compte de résultat</option>
              <option value="balance">Bilan</option>
              <option value="cashflow">Flux de trésorerie</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label">Date de publication</label>
            {{-- ✅ rendre la date requise --}}
            <input name="published_at" type="date" class="form-control" required>
          </div>
        </div>

        <div class="mt-2">
          <label class="form-label">Fichier (pdf/xlsx/csv)</label>
          <input name="file" type="file" class="form-control" required>
        </div>

        <button class="btn btn-primary mt-2">Importer</button>
      </form>

      <h6 class="mt-4">Derniers états financiers</h6>
      <ul class="small">
        @foreach($statements as $s)
          <li>
            {{ optional($s->published_at)->format('Y-m-d') }}
            — {{ $s->issuer }} • {{ $s->period }} • {{ strtoupper($s->statement_type) }}
          </li>
        @endforeach
      </ul>
    </div>
  </div>
</div>
@endsection
