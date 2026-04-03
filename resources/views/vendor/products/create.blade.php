{{-- ════════════════════════════════════════════════
     resources/views/vendor/products/create.blade.php
════════════════════════════════════════════════ --}}
@extends('layouts.app')

@push('styles')
<style>
    .vd-page { background:#060910;min-height:100vh; }
    .vd-hero { background:#0C1120;border-bottom:1px solid rgba(201,168,76,.08);padding:28px 0; }
    .vd-hero-title { font-family:'Playfair Display',serif;font-size:clamp(22px,3.5vw,32px);font-weight:900;color:#E8EAF0; }
    .vd-form-card { background:#0C1120;border:1px solid rgba(201,168,76,.1);border-radius:4px;overflow:hidden;margin-top:24px;margin-bottom:48px; }
    .vd-form-card::before { content:'';display:block;height:2px;background:linear-gradient(90deg,#C9A84C,transparent); }
    .vd-form-body { padding:28px 32px; }
    @media(max-width:600px){ .vd-form-body{padding:20px;} }
    .vd-label { font-family:'Syne',sans-serif;font-size:10px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:#6B7590;display:block;margin-bottom:6px; }
    .vd-input,.vd-select,.vd-textarea { background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;color:#E8EAF0 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:13px !important;padding:10px 14px !important;width:100%;outline:none;transition:border-color .25s; }
    .vd-input:focus,.vd-select:focus,.vd-textarea:focus { border-color:rgba(201,168,76,.4) !important;box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .vd-input::placeholder,.vd-textarea::placeholder { color:#6B7590 !important; }
    .vd-select option { background:#0C1120; }
    .vd-file-input { background:rgba(6,9,16,.9) !important;border:1px solid rgba(255,255,255,.1) !important;color:#6B7590 !important;border-radius:3px !important;font-family:'DM Sans',sans-serif !important;font-size:13px !important;padding:8px 12px !important;width:100%; }
    .vd-file-input:focus { border-color:rgba(201,168,76,.4) !important;box-shadow:0 0 0 3px rgba(201,168,76,.07) !important; }
    .vd-error { font-size:12px;color:#FF6B6B;margin-top:4px; }
    .vd-help { font-size:12px;color:#6B7590;margin-top:5px;line-height:1.6; }
    .vd-help strong { color:#9AA3B8; }
    .vd-divider { height:1px;background:rgba(255,255,255,.05);margin:20px 0; }
    .vd-alert-ok  { background:rgba(15,207,164,.07);border:1px solid rgba(15,207,164,.2);border-radius:3px;padding:12px 16px;font-size:13px;color:#0FCFA4;margin-bottom:16px; }
    .vd-alert-warn { background:rgba(255,200,30,.06);border:1px solid rgba(255,200,30,.15);border-radius:3px;padding:12px 16px;font-size:13px;color:#FFC850;margin-bottom:16px; }
    .cb-btn-gold { display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#C9A84C,#9B6B15);color:#050810 !important;font-family:'Syne',sans-serif;font-weight:800;font-size:12px;letter-spacing:.07em;text-transform:uppercase;padding:12px 26px;border:none;border-radius:3px;cursor:pointer;text-decoration:none;transition:all .3s; }
    .cb-btn-gold:hover { box-shadow:0 6px 20px rgba(201,168,76,.3);transform:translateY(-1px); }
    .cb-btn-outline { display:inline-flex;align-items:center;gap:7px;background:transparent;color:#6B7590 !important;font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.06em;text-transform:uppercase;padding:9px 16px;border:1px solid rgba(255,255,255,.1);border-radius:3px;text-decoration:none;transition:all .3s; }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important; }
    .cbr { opacity:0;transform:translateY(18px);transition:all .7s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="vd-page">
    <div class="vd-hero">
        <div class="container" style="max-width:900px;">
            <a href="{{ route('vendor.products.index') }}" style="font-family:'Syne',sans-serif;font-size:10px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:#6B7590;text-decoration:none;display:inline-flex;align-items:center;gap:6px;margin-bottom:12px;">← Mes produits</a>
            <h1 class="vd-hero-title">➕ Nouveau produit</h1>
        </div>
    </div>

    <div class="container" style="max-width:900px;">
        @if(session('success')) <div class="vd-alert-ok cbr">✅ {{ session('success') }}</div> @endif
        @if(session('warning')) <div class="vd-alert-warn cbr">⚠️ {{ session('warning') }}</div> @endif

        <div class="vd-form-card cbr">
            <div class="vd-form-body">
                <form method="POST" action="{{ route('vendor.products.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="vd-label">Catégorie</label>
                            <select name="category_id" class="vd-select form-select" required>
                                <option value="">Choisir…</option>
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" @selected(old('category_id')==$c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="vd-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="vd-label">Type</label>
                            <select name="type" class="vd-select form-select" required id="typeSelect">
                                @foreach($types as $k=>$label)
                                    <option value="{{ $k }}" @selected(old('type')===$k)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type')<div class="vd-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="vd-label">Titre</label>
                            <input name="title" class="vd-input form-control" value="{{ old('title') }}" required maxlength="160" placeholder="Ex: Guide complet d'analyse technique BRVM">
                            @error('title')<div class="vd-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="vd-label">Description</label>
                            <textarea name="description" class="vd-textarea form-control" rows="5" placeholder="Décris ton produit en détail…">{{ old('description') }}</textarea>
                            @error('description')<div class="vd-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="vd-label">WhatsApp support <span style="color:#6B7590;">(optionnel)</span></label>
                            <input name="support_whatsapp" class="vd-input form-control" placeholder="+2250700000000" value="{{ old('support_whatsapp') }}">
                            @error('support_whatsapp')<div class="vd-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="vd-label">Prix (FCFA)</label>
                            <input name="price" type="number" min="0" class="vd-input form-control" value="{{ old('price', 0) }}" required>
                            @error('price')<div class="vd-error">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <div class="vd-divider"></div>
                        </div>

                        <div class="col-12">
                            <label class="vd-label">Image de couverture <span style="color:#6B7590;">(optionnelle au draft)</span></label>
                            <input name="cover_image" type="file" class="vd-file-input form-control" accept="image/*">
                            @error('cover_image')<div class="vd-error">{{ $message }}</div>@enderror
                            <div class="vd-help">Obligatoire uniquement au moment de "Soumettre".</div>
                        </div>

                        {{-- Champ fichier standard (book / software / video) --}}
                        <div class="col-12" id="fieldFile">
                            <label class="vd-label">Fichier du produit <span style="color:#6B7590;">(optionnel au draft)</span></label>
                            <input name="file" type="file" class="vd-file-input form-control" id="fileInput">
                            @error('file')<div class="vd-error">{{ $message }}</div>@enderror
                            <div class="vd-help" id="fileHelp">
                                PDF → .pdf | ZIP → .zip/.rar | Vidéo → .mp4/.mov/.m4v/.avi<br>
                                Obligatoire au moment de "Soumettre".
                            </div>
                        </div>

                        {{-- Champ HTML du jeu (affiché uniquement si type = game) --}}
                        <div class="col-12" id="fieldGameHtml" style="display:none;">
                            <label class="vd-label">Fichier HTML du jeu <span style="color:#6B7590;">(optionnel au draft)</span></label>
                            <input name="game_html_file" type="file" accept=".html,text/html" class="vd-file-input form-control" id="gameHtmlInput">
                            @error('game_html_file')<div class="vd-error">{{ $message }}</div>@enderror
                            <div class="vd-help">
                                Upload un fichier <strong>.html</strong> tout-en-un (HTML + CSS + JS inline).<br>
                                Le jeu sera joué directement dans le navigateur. Max 10 Mo.<br>
                                Obligatoire au moment de "Soumettre".
                            </div>
                        </div>

                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('vendor.products.index') }}" class="cb-btn-outline">Annuler</a>
                        <button type="submit" class="cb-btn-gold">Créer le brouillon</button>
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
    const sel           = document.getElementById('typeSelect');
    const fileHelp      = document.getElementById('fileHelp');
    const fileInput     = document.getElementById('fileInput');
    const fieldFile     = document.getElementById('fieldFile');
    const fieldGameHtml = document.getElementById('fieldGameHtml');

    function refresh() {
        const t = sel.value;
        const isGame = t === 'game';
        fieldFile.style.display     = isGame ? 'none' : '';
        fieldGameHtml.style.display = isGame ? ''     : 'none';

        if (t === 'book')     { fileInput.accept = 'application/pdf'; fileHelp.innerHTML = 'Type PDF : fichier <strong>.pdf</strong> · Obligatoire à la soumission.'; }
        else if (t === 'software') { fileInput.accept = '.zip,.rar,application/zip,application/x-rar-compressed'; fileHelp.innerHTML = 'Type ZIP : <strong>.zip</strong> ou <strong>.rar</strong> · Obligatoire à la soumission.'; }
        else if (t === 'video')   { fileInput.accept = 'video/mp4,video/quicktime,video/x-m4v,video/x-msvideo'; fileHelp.innerHTML = 'Type Vidéo : <strong>.mp4/.mov/.m4v/.avi</strong> · Tu pourras ensuite le mettre sur Cloudflare.'; }
        else { fileInput.accept = ''; }
    }
    sel.addEventListener('change', refresh);
    refresh();
});
document.querySelectorAll('.cbr').forEach(el=>{new IntersectionObserver(([e])=>{if(e.isIntersecting)el.classList.add('on');},{threshold:.06}).observe(el);});
</script>
@endpush
