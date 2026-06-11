@extends('layouts.app')

@push('styles')
<style>
.lci-page { background: var(--cb-paper); min-height: 100vh; padding-bottom: 60px; }
.lci-header { background: var(--cb-card); border-bottom: 1px solid rgba(176,134,46,.1); padding: 40px 0 32px; }
.lci-header-tag { font-family:'Syne',sans-serif; font-size:11px; font-weight:600; letter-spacing:.2em; text-transform:uppercase; color:var(--cb-gold); margin-bottom:8px; }
.lci-header-title { font-family:'Playfair Display',serif; font-size:32px; font-weight:900; color:var(--cb-ink); margin-bottom:4px; }
.lci-header-sub { font-size:14px; color:var(--cb-muted); }
.btn-lci-primary {
    font-family:'Syne',sans-serif; font-size:12px; font-weight:700;
    letter-spacing:.1em; text-transform:uppercase;
    background:var(--cb-gold); color:#060910;
    border:none; border-radius:4px; padding:10px 22px;
    text-decoration:none; display:inline-block; transition:background .2s;
}
.btn-lci-primary:hover { background:#9A7020; color:#060910; }
.lci-letter-row {
    background: rgba(15,92,67,.03);
    border: 1px solid var(--cb-border);
    border-radius: 5px; padding: 16px 20px;
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 8px; transition: border-color .2s;
    text-decoration: none;
}
.lci-letter-row:hover { border-color: rgba(176,134,46,.2); text-decoration: none; }
.lci-letter-row .letter-icon { font-size: 24px; flex-shrink: 0; }
.lci-letter-row .letter-name { font-family:'Syne',sans-serif; font-size:13px; font-weight:600; color:var(--cb-ink); margin-bottom:3px; }
.lci-letter-row .letter-meta { font-size:12px; color:var(--cb-muted); }
.lci-letter-row .letter-actions { margin-left:auto; flex-shrink:0; display:flex; align-items:center; gap:8px; }
.status-badge { font-family:'Syne',sans-serif; font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; padding:3px 9px; border-radius:3px; }
.status-completed { background:rgba(15,92,67,.08); color:var(--cb-forest); }
.status-pending, .status-processing { background:rgba(176,134,46,.12); color:var(--cb-gold); }
.status-failed { background:rgba(192,57,43,.1); color:var(--cb-down); }
.lci-pdf-link { font-size:11px; color:var(--cb-gold); text-decoration:none; font-family:'Syne',sans-serif; font-weight:600; }
.lci-pdf-link:hover { color:var(--cb-forest); }
</style>
@endpush

@section('content')
<div class="lci-page">
    <div class="lci-header">
        <div class="container">
            <div class="lci-header-tag">LettreCI · Historique</div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h1 class="lci-header-title">Toutes mes lettres</h1>
                    <p class="lci-header-sub">{{ $letters->total() }} lettre(s) générée(s)</p>
                </div>
                <a href="{{ route('lettreci.new') }}" class="btn-lci-primary">+ Nouvelle lettre</a>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        @forelse($letters as $letter)
        @php $tpl = config("lettreci_templates.types.{$letter->template_slug}"); @endphp
        <div class="lci-letter-row">
            <div class="letter-icon">{{ $tpl['icon'] ?? '📄' }}</div>
            <div class="flex-grow-1">
                <div class="letter-name">{{ $tpl['name'] ?? $letter->template_slug }}</div>
                <div class="letter-meta">{{ $letter->created_at->format('d/m/Y à H:i') }}</div>
            </div>
            <div class="letter-actions">
                <span class="status-badge status-{{ $letter->status }}">{{ $letter->status }}</span>
                @if($letter->status === 'completed')
                <a href="{{ route('lettreci.preview', $letter) }}" class="lci-pdf-link">Voir →</a>
                <a href="{{ route('lettreci.pdf', $letter) }}" class="lci-pdf-link" target="_blank">PDF ↓</a>
                @elseif(in_array($letter->status, ['pending', 'processing']))
                <a href="{{ route('lettreci.generating', $letter) }}" class="lci-pdf-link">En attente →</a>
                @endif
            </div>
        </div>
        @empty
        <div style="text-align:center; padding:60px; color:var(--cb-muted); font-size:14px;">
            Aucune lettre générée pour l'instant.<br>
            <a href="{{ route('lettreci.new') }}" style="color:var(--cb-gold); margin-top:12px; display:inline-block;">
                Créer ma première lettre →
            </a>
        </div>
        @endforelse

        @if($letters->hasPages())
        <div class="mt-4">
            {{ $letters->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
