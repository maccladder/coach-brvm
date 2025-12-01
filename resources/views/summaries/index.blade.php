@extends('layouts.app')

@section('content')
<h2 class="mb-4">📈 Résumés quotidiens générés</h2>

@if($summaries->isEmpty())
    <div class="alert alert-info">Aucun résumé disponible pour le moment.</div>
@else
    <div class="list-group">
        @foreach($summaries as $summary)
            <div class="list-group-item mb-3 shadow-sm">
                <h5 class="mb-1">📅 {{ \Carbon\Carbon::parse($summary->for_date)->format('d/m/Y') }}</h5>
                <div class="markdown-body" style="white-space: pre-line;">
                    {!! nl2br(e($summary->summary_markdown)) !!}
                </div>
                <small class="text-muted">Créé le {{ $summary->created_at->format('d/m/Y à H:i') }}</small>
            </div>
        @endforeach
    </div>
@endif
@endsection
