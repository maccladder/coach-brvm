@extends('layouts.admin')

@section('title', 'Inspection produit')

@section('content')
<div class="container py-5" style="max-width:1200px;">

    <a href="{{ route('admin.marketplace.index') }}" class="text-decoration-none small">← Retour</a>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mt-2 mb-4">
        <div>
            <h2 class="fw-bold mb-1">🔎 Inspection produit</h2>
            <div class="text-muted">
                Statut :
                @php
                    $badge = match($product->status) {
                        'published' => 'success',
                        'draft'     => 'secondary',
                        'pending'   => 'warning',
                        'rejected'  => 'danger',
                        default     => 'secondary',
                    };
                @endphp
                <span class="badge bg-{{ $badge }}">{{ $product->status }}</span>

                @if($product->reviewed_at)
                    <span class="ms-2 small text-muted">reviewed_at: {{ $product->reviewed_at }}</span>
                @endif
            </div>

            @if($product->admin_note)
                <div class="small text-danger mt-1">Motif : {{ $product->admin_note }}</div>
            @endif
        </div>

        @if($product->status === 'pending')
            <div class="d-flex flex-wrap gap-2">
                <form method="POST" action="{{ route('admin.marketplace.approve', $product) }}">
                    @csrf
                    <button class="btn btn-success">✅ Approuver</button>
                </form>

                <form method="POST" action="{{ route('admin.marketplace.reject', $product) }}" class="d-flex gap-2">
                    @csrf
                    <input type="text" name="admin_note" class="form-control"
                           placeholder="Motif (obligatoire)" required style="max-width:300px;">
                    <button class="btn btn-outline-danger">⛔ Rejeter</button>
                </form>
            </div>
        @endif
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="ratio ratio-16x9 bg-light rounded overflow-hidden">
                        @if($product->cover_image_path)
                            <img src="{{ asset('storage/'.$product->cover_image_path) }}" class="w-100 h-100" style="object-fit:cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center text-muted">Pas de cover</div>
                        @endif
                    </div>

                    <div class="mt-3">
                        <div class="fw-bold">{{ $product->title }}</div>
                        <div class="text-muted small">
                            Type: {{ $product->type }} • Prix: {{ number_format((int)$product->price, 0, ',', ' ') }} FCFA
                        </div>
                        <div class="text-muted small">
                            Catégorie: {{ $product->category?->name ?? '—' }}
                        </div>

                        @if($product->vendor)
                            <div class="mt-2 small">
                                <span class="text-muted">Vendeur :</span>
                                <span class="fw-semibold">{{ $product->vendor->name }}</span>
                                <span class="text-muted">({{ $product->vendor->email }})</span>
                            </div>
                        @endif

                        @if($product->support_whatsapp)
                            <div class="mt-2 small">
                                <span class="text-muted">WhatsApp support :</span>
                                <span class="fw-semibold">{{ $product->support_whatsapp }}</span>
                            </div>
                        @endif

                        @if($product->description)
                            <div class="mt-3">
                                <div class="text-muted small mb-1">Description</div>
                                <div class="small">{{ $product->description }}</div>
                            </div>
                        @endif

                        <div class="mt-3 d-flex flex-wrap gap-2">
                            <a class="btn btn-outline-primary" href="{{ route('admin.marketplace.edit', $product) }}">
                                ✏️ Modifier
                            </a>
                            <a class="btn btn-outline-dark" target="_blank" href="{{ route('marketplace.show', $product->slug) }}">
                                🌍 Voir côté public
                            </a>
                            @if($product->type === 'game' && !empty($product->game_html))
                                <a class="btn btn-warning" target="_blank" href="{{ route('admin.marketplace.play', $product) }}">
                                    🎮 Tester le jeu
                                </a>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">📎 Assets (fichiers / stream)</div>
                <div class="card-body">

                    @if($product->assets->isEmpty())
                        <div class="alert alert-warning mb-0">Aucun asset attaché.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kind</th>
                                        <th>Label</th>
                                        <th>Path / URL</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($product->assets as $a)
                                        <tr>
                                            <td class="text-muted">{{ $a->kind }}</td>
                                            <td class="fw-semibold">{{ $a->label ?? '—' }}</td>
                                            <td class="small text-muted">
                                                @if($a->kind === 'stream')
                                                    {{ $a->url }}
                                                @else
                                                    {{ $a->path }}
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($a->kind === 'file' && $a->path)
                                                    <a class="btn btn-sm btn-outline-dark"
                                                       href="{{ route('admin.marketplace.assets.download', $a) }}">
                                                        <i class="bi bi-download"></i> Télécharger
                                                    </a>
                                                @else
                                                    <span class="text-muted small">—</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                </div>
            </div>

            <div class="alert alert-info border-0 shadow-sm mt-3">
                💡 Astuce : tu peux “inspecter” un PDF/ZIP en le téléchargeant ici, avant d’approuver.
            </div>
        </div>
    </div>

</div>
@endsection
