{{-- ════════════════════════════════════════════════
     resources/views/vendor/products/edit.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .vd-page { background:#060910;min-height:100vh; }
    .vd-hero { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:28px 0; }
    .vd-hero-title { font-family:'Playfair Display',serif;font-size:clamp(22px,3.5vw,32px);font-weight:900;color:#E8EAF0;margin-bottom:6px; }
    .vd-badge { font-family:'Syne',sans-serif;font-size:9px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:4px 10px;border-radius:100px; }
    .vd-draft     { background:rgba(255,255,255,.06);color:#9AA3B8;border:1px solid rgba(255,255,255,.1); }
    .vd-pending   { background:rgba(255,200,30,.08);color:#FFC850;border:1px solid rgba(255,200,30,.2); }
    .vd-published { background:rgba(15,207,164,.08);color:#0FCFA4;border:1px solid rgba(15,207,164,.2); }
    .vd-rejected  { background:rgba(255,107,107,.08);color:#FF6B6B;border:1px solid rgba(255,107,107,.2); }
    .vd-form-card { background:#0C1120;border:1px solid rgba(201,168,76,.1);border-radius:4px;overflow:hidden;margin-top:24px;margin-bottom:48px; }
    .vd-form-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,transparent); }
    .vd-form-body { padding:28px 32px; }
    @media(max-width:600px){ .vd-form-body{padding:20px;} }
    .vd-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#6B7590;display:block;margin-bottom:6px; }
    .vd-input,.vd-select,.vd-textarea { background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;color:#E8EAF0 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:13px !important;padding:10px 14px !important;width:100%;outline:none;transition:border-color .25s; }
    .vd-input:focus,.vd-select:focus,.vd-textarea:focus { border-color:rgba(201,168,76,.4) !important;box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .vd-input:disabled,.vd-select:disabled,.vd-textarea:disabled { opacity:.4;cursor:default; }
    .vd-select option { background:#0C1120; }
    .vd-file-input { background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;color:#6B7590 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:13px !important;padding:8px 12px !important;width:100%; }
    .vd-error { font-size:12px;color:#FF6B6B;margin-top:4px; }
    .vd-help { font-size:12px;color:#6B7590;margin-top:5px;line-height:1.6; }
    .vd-help strong { color:#9AA3B8; }
    .vd-divider { height:1px;background:rgba(255,255,255,.05);margin:20px 0; }
    .vd-alert-ok  { background:rgba(15,207,164,.07);border:1px solid rgba(15,207,164,.2);border-radius:3px;padding:12px 16px;font-size:13px;color:#0FCFA4;margin-bottom:16px; }
    .vd-alert-warn { background:rgba(255,200,30,.06);border:1px solid rgba(255,200,30,.15);border-radius:3px;padding:12px 16px;font-size:13px;color:#FFC850;margin-bottom:16px; }
    .vd-pending-notice { background:rgba(255,200,30,.05);border:1px solid rgba(255,200,30,.15);border-radius:3px;padding:14px 18px;font-size:13px;color:#FFC850;margin-bottom:20px; }
    .vd-file-current { background:#121A2C;border:1px solid rgba(255,255,255,.06);border-radius:3px;padding:14px 16px;margin-top:8px; }
    .vd-file-current-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#C9A84C;margin-bottom:6px; }
    .vd-reject-note { font-size:13px;color:#FF6B6B;margin-left:10px; }
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:12px 26px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-gold:disabled { opacity:.35;cursor:default;transform:none;box-shadow:none; }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:7px;background:transparent;color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:9px 16px;border:1px solid rgba(255,255,255,.12);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important;background:rgba(201,168,76,.05); }
    .cb-btn-submit { display:inline-flex;align-items:center;gap:7px;background:rgba(255,255,255,.06);color:#E8EAF0 !important;font-family:'Syne',sans-serif;font-weight:700;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:11px 22px;border:1px solid rgba(255,255,255,.15);border-radius:3px;cursor:pointer;transition:all .3s; }
    .cb-btn-submit:hover { background:rgba(15,207,164,.1);border-color:rgba(15,207,164,.2);color:#0FCFA4 !important; }
    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
@php
    $fileAsset = $product->assets->firstWhere('kind', 'file');
    $statusCls = match($product->status) {
        'draft'=>'vd-draft','pending'=>'vd-pending',
        'published'=>'vd-published','rejected'=>'vd-rejected',default=>'vd-draft'
    };
@endphp
<div class="vd-page">
    <div class="vd-hero">
        <div class="container" style="max-width:900px;">
            <a href="{{ route('vendor.products.index') }}" style="font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6B7590;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;">← Mes produits</a>
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <h1 class="vd-hero-title">✏️ Gérer produit</h1>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="vd-badge {{ $statusCls }}">{{ $product->status }}</span>
                        @if($product->status === 'rejected' && $product->admin_note)
                            <span class="vd-reject-note">Motif : {{ $product->admin_note }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a class="cb-btn-outline" style="font-size:11px;padding:8px 14px;" target="_blank" href="{{ route('marketplace.show',$product->slug) }}">
                        👁 Voir (si publié)
                    </a>
                    @if(in_array($product->status,['draft','rejected'],true))
                        <form method="POST" action="{{ route('vendor.products.submit',$product) }}">
                            @csrf
                            <button type="submit" class="cb-btn-submit">
                                ✅ Soumettre à validation
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="container" style="max-width:900px;">
        @if(session('success')) <div class="vd-alert-ok cbr">✅ {{ session('success') }}</div> @endif
        @if(session('warning')) <div class="vd-alert-warn cbr">⚠️ {{ session('warning') }}</div> @endif

        <div class="vd-form-card cbr">
            <div class="vd-form-body">

                @if($product->status === 'pending')
                    <div class="vd-pending-notice">⏳ Produit en validation admin — modification désactivée temporairement.</div>
                @endif

                <form method="POST" action="{{ route('vendor.products.update',$product) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <fieldset @disabled($product->status === 'pending')>
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="vd-label">Catégorie</label>
                                <select name="category_id" class="vd-select form-select" required>
                                    @foreach($categories as $c)
                                        <option value="{{ $c->id }}" @selected(old('category_id',$product->category_id)==$c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_id')<div class="vd-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="vd-label">Type</label>
                                <select name="type" class="vd-select form-select" required id="typeSelect">
                                    @foreach($types as $k=>$label)
                                        <option value="{{ $k }}" @selected(old('type',$product->type)===$k)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type')<div class="vd-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="vd-label">Titre</label>
                                <input name="title" class="vd-input form-control" value="{{ old('title',$product->title) }}" required maxlength="160">
                                @error('title')<div class="vd-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label class="vd-label">Description</label>
                                <textarea name="description" class="vd-textarea form-control" rows="6">{{ old('description',$product->description) }}</textarea>
                                @error('description')<div class="vd-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="vd-label">WhatsApp support</label>
                                <input name="support_whatsapp" class="vd-input form-control" value="{{ old('support_whatsapp',$product->support_whatsapp) }}" placeholder="+2250700000000">
                                @error('support_whatsapp')<div class="vd-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label class="vd-label">Prix (FCFA)</label>
                                <input name="price" type="number" min="0" class="vd-input form-control" value="{{ old('price',$product->price) }}" required>
                                @error('price')<div class="vd-error">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12"><div class="vd-divider"></div></div>

                            <div class="col-12">
                                <label class="vd-label">Image de couverture</label>
                                <input name="cover_image" type="file" class="vd-file-input form-control" accept="image/*">
                                @error('cover_image')<div class="vd-error">{{ $message }}</div>@enderror
                                @if($product->cover_image_path)
                                    <div class="vd-file-current">
                                        <div class="vd-file-current-label">Image actuelle</div>
                                        <img src="{{ asset('storage/'.$product->cover_image_path) }}" alt="cover" style="max-height:100px;border-radius:3px;">
                                    </div>
                                @endif
                                <div class="vd-help">Obligatoire pour soumettre.</div>
                            </div>

                            <div class="col-12">
                                <label class="vd-label">Fichier du produit</label>
                                <input name="file" type="file" class="vd-file-input form-control" id="fileInput">
                                @error('file')<div class="vd-error">{{ $message }}</div>@enderror
                                @if($fileAsset)
                                    <div class="vd-file-current">
                                        <div class="vd-file-current-label">Fichier actuel</div>
                                        <div style="font-size:13px;color:#9AA3B8;">{{ $fileAsset->label }}</div>
                                        @if($fileAsset->path)
                                            <div style="font-size:12px;color:#6B7590;margin-top:3px;">{{ $fileAsset->path }}</div>
                                        @endif
                                        <div style="font-size:12px;color:#6B7590;margin-top:6px;">Upload un nouveau fichier pour remplacer.</div>
                                    </div>
                                @else
                                    <div class="vd-help">Aucun fichier encore — ajoute un PDF/ZIP/Vidéo avant de soumettre.</div>
                                @endif
                                <div class="vd-help" id="fileHelp">PDF → .pdf | ZIP → .zip/.rar | Vidéo → .mp4/.mov/.m4v/.avi</div>
                            </div>

                        </div>
                    </fieldset>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="cb-btn-gold" @disabled($product->status === 'pending')>
                            Enregistrer les modifications
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('typeSelect');
    const fileHelp = document.getElementById('fileHelp');
    const fileInput = document.getElementById('fileInput');
    function refresh() {
        const t = sel.value;
        if(t==='pdf'){fileInput.accept='application/pdf';fileHelp.innerHTML='Type PDF : fichier <strong style="color:#9AA3B8;">.pdf</strong>';}
        else if(t==='zip'){fileInput.accept='.zip,.rar,application/zip,application/x-rar-compressed';fileHelp.innerHTML='Type ZIP : fichier <strong style="color:#9AA3B8;">.zip</strong> / <strong style="color:#9AA3B8;">.rar</strong>';}
        else if(t==='video'){fileInput.accept='video/mp4,video/quicktime,video/x-m4v,video/x-msvideo';fileHelp.innerHTML='Type Vidéo : <strong style="color:#9AA3B8;">.mp4 / .mov / .m4v / .avi</strong>';}
        else{fileInput.accept='';fileHelp.textContent='Ajoute un fichier correspondant au type choisi.';}
    }
    if(sel){ sel.addEventListener('change', refresh); refresh(); }
});
document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});
</script>
@endpush
