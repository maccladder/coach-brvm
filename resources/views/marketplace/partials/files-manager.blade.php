{{--
    Gestionnaire « Fichiers téléchargeables » (admin + vendeur).
    Variables : $product (files chargés), $routePrefix ('admin.marketplace.files' | 'vendor.products.files'),
                $canNotify (bool, admin uniquement), $locked (bool, produit en validation côté vendeur)
--}}
@php
    $pfFiles      = $product->files;
    $pfLimits     = app(\App\Services\Marketplace\ProductFileService::class)->uploadLimits();
    $pfExtensions = config('marketplace.files_extensions');
    $pfMaxMb      = (int) floor($pfLimits['file'] / 1048576);
    $pfErrors     = $errors->getBag('productFiles');
    $locked       = $locked ?? false;
    $canNotify    = $canNotify ?? false;
    $canReview    = $canReview ?? false;
    $pfPending    = $pfFiles->filter->isPending()->count();
@endphp

@once
<style>
    .pf-card { background:var(--cb-card,#fff); border:1px solid var(--cb-border,#e3e6ea); border-radius:6px; margin:24px 0 48px; overflow:hidden; }
    .pf-card::before { content:''; display:block; height:2px; background:linear-gradient(90deg,var(--cb-gold,#b0862e),transparent); }
    .pf-body { padding:24px 28px; }
    @media(max-width:600px){ .pf-body{ padding:18px 16px; } }
    .pf-title { font-size:18px; font-weight:800; color:var(--cb-ink,#1d2433); margin:0; }
    .pf-intro { font-size:13px; color:var(--cb-muted,#6b7590); margin:6px 0 18px; line-height:1.6; }
    .pf-list { border:1px solid var(--cb-border,#e3e6ea); border-radius:4px; margin-bottom:22px; }
    .pf-item { display:flex; flex-wrap:wrap; gap:10px 14px; align-items:center; padding:12px 14px; border-bottom:1px solid var(--cb-border,#e3e6ea); }
    .pf-item:last-child { border-bottom:none; }
    .pf-icon { font-size:22px; color:var(--cb-gold,#b0862e); width:28px; text-align:center; flex:0 0 auto; }
    .pf-main { flex:1 1 220px; min-width:0; }
    .pf-label { font-weight:700; font-size:14px; color:var(--cb-ink,#1d2433); overflow-wrap:anywhere; }
    .pf-meta { font-size:12px; color:var(--cb-muted,#6b7590); margin-top:2px; overflow-wrap:anywhere; }
    .pf-tag { display:inline-block; font-size:10px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; padding:2px 7px; border-radius:100px; border:1px solid currentColor; margin-left:4px; }
    .pf-tag-warn { color:#b26a00; }
    .pf-tag-bad { color:#c0392b; }
    .pf-status { font-size:12px; margin-top:4px; line-height:1.5; }
    .pf-status-warn { color:#8a6414; }
    .pf-status-bad { color:#c0392b; }
    .pf-review { flex:1 1 100%; display:flex; flex-wrap:wrap; gap:8px 16px; padding:10px; margin-top:4px; border-radius:3px; background:rgba(176,134,46,.06); border:1px solid rgba(176,134,46,.2); }
    .pf-review-form { display:flex; flex-wrap:wrap; gap:8px; align-items:center; margin:0; flex:1 1 260px; }
    .pf-btn-ok { color:var(--cb-forest,#0f5c43) !important; border-color:rgba(15,92,67,.35); }
    .pf-btn-ok:hover:not(:disabled) { background:rgba(15,92,67,.06); color:var(--cb-forest,#0f5c43) !important; border-color:var(--cb-forest,#0f5c43); }
    .pf-actions { display:flex; flex-wrap:wrap; gap:6px; align-items:center; }
    .pf-actions form { margin:0; }
    .pf-btn { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; padding:6px 10px; border-radius:3px; border:1px solid var(--cb-border,#d5d9de); background:transparent; color:var(--cb-ink,#1d2433) !important; text-decoration:none; cursor:pointer; line-height:1.2; }
    .pf-btn:hover:not(:disabled) { border-color:var(--cb-gold,#b0862e); color:var(--cb-gold,#b0862e) !important; }
    .pf-btn:disabled { opacity:.35; cursor:default; }
    .pf-btn-danger { color:#c0392b !important; border-color:rgba(192,57,43,.35); }
    .pf-btn-danger:hover:not(:disabled) { background:rgba(192,57,43,.06); color:#c0392b !important; border-color:#c0392b; }
    .pf-btn-primary { background:linear-gradient(135deg,var(--cb-gold,#b0862e),#9B6B15); color:#050810 !important; border:none; font-weight:800; padding:10px 18px; text-transform:uppercase; letter-spacing:.05em; }
    .pf-btn-primary:hover:not(:disabled) { color:#050810 !important; box-shadow:0 4px 14px rgba(176,134,46,.3); }
    .pf-edit { flex:1 1 100%; display:none; gap:8px; flex-wrap:wrap; padding-top:6px; }
    .pf-edit.open { display:flex; }
    .pf-input, .pf-select { background:var(--cb-paper,#fff); border:1px solid var(--cb-border,#d5d9de); color:var(--cb-ink,#1d2433); border-radius:3px; font-size:13px; padding:8px 10px; min-width:0; }
    .pf-input { flex:1 1 200px; }
    .pf-select { flex:0 1 190px; }
    .pf-empty { font-size:13px; color:var(--cb-muted,#6b7590); padding:16px; text-align:center; }
    .pf-add { border:1px dashed var(--cb-border,#c9ced4); border-radius:4px; padding:18px; }
    .pf-add-title { font-weight:700; font-size:14px; color:var(--cb-ink,#1d2433); margin-bottom:10px; }
    .pf-file-input { width:100%; font-size:13px; padding:8px; border:1px solid var(--cb-border,#d5d9de); border-radius:3px; background:var(--cb-paper,#fff); color:var(--cb-muted,#6b7590); }
    .pf-help { font-size:12px; color:var(--cb-muted,#6b7590); margin-top:6px; line-height:1.6; }
    .pf-rows { display:flex; flex-direction:column; gap:10px; margin-top:12px; }
    .pf-row { display:flex; flex-wrap:wrap; gap:8px; align-items:center; padding:10px; border:1px solid var(--cb-border,#e3e6ea); border-radius:3px; }
    .pf-row-name { flex:1 1 100%; font-size:12px; color:var(--cb-muted,#6b7590); overflow-wrap:anywhere; }
    .pf-row-name.bad { color:#c0392b; font-weight:600; }
    .pf-alert { border-radius:3px; padding:10px 14px; font-size:13px; margin-bottom:14px; }
    .pf-alert-err { background:rgba(192,57,43,.06); border:1px solid rgba(192,57,43,.25); color:#c0392b; }
    .pf-alert-err ul { margin:0; padding-left:18px; }
    .pf-notify { display:flex; gap:8px; align-items:flex-start; font-size:13px; color:var(--cb-ink,#1d2433); margin-top:14px; }
    .pf-notify input { margin-top:3px; }
    .pf-submit-row { display:flex; justify-content:flex-end; margin-top:14px; }
</style>
@endonce

<div class="pf-card" id="fichiers">
    <div class="pf-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="pf-title">📦 Fichiers téléchargeables</h3>
            <span class="pf-meta">
                {{ $pfFiles->count() }} fichier(s)
                @if($pfPending) · <strong style="color:#8a6414;">{{ $pfPending }} en attente de validation</strong> @endif
            </span>
        </div>
        <p class="pf-intro">
            Tous les acheteurs de ce produit ont accès à <strong>tous</strong> ces fichiers approuvés,
            y compris ceux ajoutés après leur achat. Exemple : « Windows — Installeur », « Windows — Portable », « macOS ».
            @if(!$canReview && $product->status === 'published')
                <br>🔎 Produit publié : les fichiers exécutables ({{ implode(', ', config('marketplace.review_extensions')) }})
                que tu ajoutes ou remplaces sont vérifiés par l'admin avant d'être visibles. Les PDF/epub sont publiés tout de suite.
            @endif
        </p>

        @if(session('pf_success'))
            <div class="pf-alert" style="background:rgba(15,92,67,.06);border:1px solid rgba(15,92,67,.2);color:var(--cb-forest,#0f5c43);">{{ session('pf_success') }}</div>
        @endif
        @if(session('pf_warning'))
            <div class="pf-alert" style="background:rgba(176,134,46,.06);border:1px solid rgba(176,134,46,.2);color:#8a6414;">⚠️ {{ session('pf_warning') }}</div>
        @endif
        @if($locked)
            <div class="pf-alert" style="background:rgba(176,134,46,.06);border:1px solid rgba(176,134,46,.2);color:#8a6414;">⏳ Produit en validation admin : les fichiers ne peuvent pas être modifiés pour l'instant.</div>
        @endif

        @if(request('upload_trop_gros'))
            <div class="pf-alert pf-alert-err">
                ⚠️ Envoi trop volumineux : il dépasse la limite du serveur ({{ ini_get('post_max_size') }} par envoi).
                Envoie les gros fichiers un par un.
            </div>
        @endif

        @if($pfErrors->any())
            <div class="pf-alert pf-alert-err">
                <ul>
                    @foreach($pfErrors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Liste des fichiers existants --}}
        <div class="pf-list">
            @forelse($pfFiles as $f)
                <div class="pf-item">
                    <i class="bi {{ $f->icon }} pf-icon"></i>
                    <div class="pf-main">
                        <div class="pf-label">
                            {{ $f->label }}
                            @if($f->disk === 'public')
                                <span class="pf-tag pf-tag-warn" title="Fichier encore sur le disque public : lancer marketplace:move-files-private">public</span>
                            @endif
                            @if($f->isPending())
                                <span class="pf-tag pf-tag-warn">En attente de validation</span>
                            @elseif($f->isRejected())
                                <span class="pf-tag pf-tag-bad">Refusé</span>
                            @endif
                        </div>
                        @if($f->isPending())
                            <div class="pf-status pf-status-warn">
                                ⏳ Invisible pour les acheteurs jusqu'à validation admin.
                                @if($f->replaces)
                                    Remplacera « {{ $f->replaces->label }} » ({{ $f->replaces->original_name }}), qui reste servi d'ici là.
                                @endif
                            </div>
                        @elseif($f->isRejected())
                            <div class="pf-status pf-status-bad">
                                🚫 Refusé{{ $f->review_note ? ' : ' . $f->review_note : '.' }} Invisible pour les acheteurs.
                            </div>
                        @endif
                        <div class="pf-meta">
                            {{ $f->platform_label ?? 'Plateforme non précisée' }}
                            · {{ $f->original_name }}
                            @if($f->human_size) · {{ $f->human_size }} @endif
                            · ⬇ {{ $f->download_count }} téléchargement(s)
                        </div>
                    </div>

                    <div class="pf-actions">
                        <form method="POST" action="{{ route($routePrefix.'.move', [$product, $f]) }}">
                            @csrf
                            <input type="hidden" name="direction" value="up">
                            <button type="submit" class="pf-btn" title="Monter" aria-label="Monter" @disabled($locked || $loop->first)>↑</button>
                        </form>
                        <form method="POST" action="{{ route($routePrefix.'.move', [$product, $f]) }}">
                            @csrf
                            <input type="hidden" name="direction" value="down">
                            <button type="submit" class="pf-btn" title="Descendre" aria-label="Descendre" @disabled($locked || $loop->last)>↓</button>
                        </form>
                        <a class="pf-btn" href="{{ route($routePrefix.'.download', [$product, $f]) }}">
                            <i class="bi bi-download"></i> <span class="d-none d-sm-inline">Télécharger</span>
                        </a>
                        <button type="button" class="pf-btn js-pf-edit-toggle" data-target="pf-edit-{{ $f->id }}" @disabled($locked)>
                            <i class="bi bi-pencil"></i> <span class="d-none d-sm-inline">Modifier</span>
                        </button>
                        <form method="POST" action="{{ route($routePrefix.'.destroy', [$product, $f]) }}"
                              onsubmit="return confirm('Supprimer définitivement « {{ addslashes($f->label) }} » ?\nLe fichier sera effacé du serveur et les acheteurs ne pourront plus le télécharger.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="pf-btn pf-btn-danger" @disabled($locked)>
                                <i class="bi bi-trash"></i> <span class="d-none d-sm-inline">Supprimer</span>
                            </button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route($routePrefix.'.update', [$product, $f]) }}" class="pf-edit" id="pf-edit-{{ $f->id }}">
                        @csrf
                        @method('PATCH')
                        <input type="text" name="label" class="pf-input" value="{{ $f->label }}" maxlength="120" required aria-label="Libellé">
                        <select name="platform" class="pf-select" aria-label="Plateforme">
                            <option value="">— Plateforme —</option>
                            @foreach(\App\Models\ProductFile::PLATFORMS as $key => $name)
                                <option value="{{ $key }}" @selected($f->platform === $key)>{{ $name }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="pf-btn">Enregistrer</button>
                    </form>

                    @if($canReview && $f->isPending())
                        <div class="pf-review">
                            <form method="POST" action="{{ route($routePrefix.'.approve', [$product, $f]) }}" class="pf-review-form">
                                @csrf
                                <button type="submit" class="pf-btn pf-btn-ok"><i class="bi bi-check2-circle"></i> Approuver</button>
                                <label class="pf-meta" style="display:inline-flex;gap:6px;align-items:center;">
                                    <input type="checkbox" name="notify" value="1"> Prévenir les acheteurs par email
                                </label>
                            </form>
                            <form method="POST" action="{{ route($routePrefix.'.reject', [$product, $f]) }}" class="pf-review-form"
                                  onsubmit="return confirm('Refuser ce fichier ? Il restera invisible pour les acheteurs.')">
                                @csrf
                                <input type="text" name="review_note" class="pf-input" maxlength="500" placeholder="Motif du refus (visible par le vendeur)" aria-label="Motif du refus">
                                <button type="submit" class="pf-btn pf-btn-danger"><i class="bi bi-x-circle"></i> Refuser</button>
                            </form>
                        </div>
                    @endif
                </div>
            @empty
                <div class="pf-empty">Aucun fichier téléchargeable pour l'instant.</div>
            @endforelse
        </div>

        {{-- Ajout de fichiers --}}
        <form method="POST" action="{{ route($routePrefix.'.store', $product) }}" enctype="multipart/form-data"
              class="pf-add js-pf-add"
              data-max-file="{{ $pfLimits['file'] }}"
              data-max-total="{{ $pfLimits['total'] ?? '' }}"
              data-extensions="{{ implode(',', $pfExtensions) }}">
            @csrf
            <fieldset @disabled($locked) style="border:0;padding:0;margin:0;min-width:0;">
                <div class="pf-add-title">➕ Ajouter des fichiers</div>

                <input type="file" name="files[]" multiple class="pf-file-input js-pf-input"
                       accept="{{ collect($pfExtensions)->map(fn ($e) => '.'.$e)->implode(',') }}">
                <div class="pf-help">
                    Formats : {{ implode(', ', $pfExtensions) }} · {{ $pfMaxMb }} Mo max par fichier.<br>
                    💡 Pour les gros installeurs (plusieurs centaines de Mo), envoie <strong>un fichier par envoi</strong> :
                    c'est plus fiable et ça évite de dépasser la limite du serveur.
                </div>

                <div class="pf-rows js-pf-rows"></div>
                <div class="pf-alert pf-alert-err js-pf-error" style="display:none;margin-top:12px;"></div>

                @if($canNotify)
                    <label class="pf-notify">
                        <input type="checkbox" name="notify" value="1">
                        <span>
                            <strong>Prévenir les acheteurs par email</strong><br>
                            <span class="pf-meta">« Nouvelle version disponible : {{ $product->title }} », envoyé à chaque acheteur et bénéficiaire d'une attribution.</span>
                        </span>
                    </label>
                @endif

                <div class="pf-submit-row">
                    <button type="submit" class="pf-btn pf-btn-primary js-pf-submit">Ajouter les fichiers</button>
                </div>
            </fieldset>
        </form>
    </div>
</div>

@once
<script>
(function () {
    const PLATFORMS = @json(\App\Models\ProductFile::PLATFORMS);

    function guessPlatform(name) {
        const ext = (name.split('.').pop() || '').toLowerCase();
        if (ext === 'exe' || ext === 'msi') return 'windows';
        if (ext === 'dmg' || ext === 'pkg') return 'macos';
        if (ext === 'apk') return 'android';
        return '';
    }

    function humanSize(bytes) {
        const units = ['octets', 'Ko', 'Mo', 'Go'];
        let i = 0, size = bytes;
        while (size >= 1024 && i < units.length - 1) { size /= 1024; i++; }
        return size.toFixed(i >= 2 ? 1 : 0).replace('.', ',') + ' ' + units[i];
    }

    document.querySelectorAll('.js-pf-edit-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById(btn.dataset.target)?.classList.toggle('open');
        });
    });

    document.querySelectorAll('.js-pf-add').forEach(form => {
        const input    = form.querySelector('.js-pf-input');
        const rows     = form.querySelector('.js-pf-rows');
        const errorBox = form.querySelector('.js-pf-error');
        const submit   = form.querySelector('.js-pf-submit');
        const maxFile  = parseInt(form.dataset.maxFile, 10) || 0;
        const maxTotal = parseInt(form.dataset.maxTotal, 10) || 0;
        const allowed  = form.dataset.extensions.split(',');

        function check() {
            const files = Array.from(input.files || []);
            const problems = [];
            let total = 0;

            files.forEach(f => {
                total += f.size;
                const ext = (f.name.split('.').pop() || '').toLowerCase();
                if (!allowed.includes(ext)) problems.push(`« ${f.name} » : format .${ext} non autorisé.`);
                if (maxFile && f.size > maxFile) problems.push(`« ${f.name} » (${humanSize(f.size)}) dépasse la limite de ${humanSize(maxFile)} par fichier.`);
            });
            if (maxTotal && total > maxTotal) {
                problems.push(`Total sélectionné : ${humanSize(total)}, au-delà de la limite du serveur (${humanSize(maxTotal)} par envoi). Envoie les fichiers un par un.`);
            }

            errorBox.style.display = problems.length ? '' : 'none';
            errorBox.innerHTML = problems.map(p => '⚠️ ' + p.replace(/</g, '&lt;')).join('<br>');
            submit.disabled = problems.length > 0 || files.length === 0;
            return problems.length === 0;
        }

        input.addEventListener('change', () => {
            rows.innerHTML = '';
            Array.from(input.files || []).forEach((f, i) => {
                const guess = guessPlatform(f.name);
                const row = document.createElement('div');
                row.className = 'pf-row';

                const name = document.createElement('div');
                name.className = 'pf-row-name';
                name.textContent = `${f.name} · ${humanSize(f.size)}`;
                if (maxFile && f.size > maxFile) name.classList.add('bad');

                const label = document.createElement('input');
                label.type = 'text';
                label.name = `labels[${i}]`;
                label.maxLength = 120;
                label.className = 'pf-input';
                label.placeholder = guess ? `Libellé (ex. ${PLATFORMS[guess]} — Installeur)` : 'Libellé (ex. Windows — Portable)';
                label.setAttribute('aria-label', `Libellé de ${f.name}`);

                const select = document.createElement('select');
                select.name = `platforms[${i}]`;
                select.className = 'pf-select';
                select.setAttribute('aria-label', `Plateforme de ${f.name}`);
                select.add(new Option('— Plateforme —', ''));
                Object.entries(PLATFORMS).forEach(([key, text]) => select.add(new Option(text, key, false, key === guess)));

                row.append(name, label, select);
                rows.appendChild(row);
            });
            check();
        });

        form.addEventListener('submit', e => {
            if (!check()) { e.preventDefault(); return; }
            submit.disabled = true;
            submit.textContent = 'Envoi en cours… ne ferme pas la page';
        });

        submit.disabled = true;
    });
})();
</script>
@endonce
