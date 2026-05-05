@extends('layouts.admin')

@section('title', 'Attributions gratuites – Admin')

@section('content')
<div class="container py-4" style="max-width:1300px;">

    {{-- EN-TÊTE --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">🎁 Attributions gratuites</h3>
            <div class="text-muted small">Offrez l'accès à des contenus payants à des utilisateurs sélectionnés.</div>
        </div>
        <button class="btn btn-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNewGrant">
            + Nouvelle attribution
        </button>
    </div>

    {{-- ALERTES --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    {{-- FILTRES --}}
    <form method="GET" class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold mb-1">Rechercher (nom / email utilisateur)</label>
                    <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Ex: kouadio">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Type d'item</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Tous les types</option>
                        @foreach($typeLabels as $key => $label)
                            <option value="{{ $key }}" @selected(request('type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Statut</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="active"  @selected(request('status') === 'active')>Active</option>
                        <option value="revoked" @selected(request('status') === 'revoked')>Révoquée</option>
                        <option value="expired" @selected(request('status') === 'expired')>Expirée</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button class="btn btn-sm btn-dark w-100">Filtrer</button>
                    <a href="{{ route('admin.grants.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </div>
        </div>
    </form>

    {{-- TABLEAU --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Utilisateur</th>
                            <th>Item attribué</th>
                            <th>Raison</th>
                            <th>Attribué par</th>
                            <th>Date</th>
                            <th>Expiration</th>
                            <th>Statut</th>
                            <th style="width:110px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($grants as $grant)
                        <tr>
                            {{-- Utilisateur --}}
                            <td>
                                <div class="fw-semibold">{{ $grant->user?->name ?? '—' }}</div>
                                <div class="text-muted small">{{ $grant->user?->email }}</div>
                            </td>

                            {{-- Item --}}
                            <td>
                                <span class="badge bg-{{ $grant->type_badge_color }} me-1">{{ $grant->type_label }}</span>
                                <span class="small">{{ $itemNames[$grant->id] ?? '—' }}</span>
                            </td>

                            {{-- Raison --}}
                            <td class="small text-muted" style="max-width:180px">
                                {{ Str::limit($grant->reason, 60) ?: '—' }}
                            </td>

                            {{-- Attribué par --}}
                            <td class="small">{{ $grant->grantedBy?->name ?? '—' }}</td>

                            {{-- Date --}}
                            <td class="small text-nowrap">{{ $grant->granted_at->format('d/m/Y') }}</td>

                            {{-- Expiration --}}
                            <td class="small text-nowrap">
                                @if($grant->expires_at)
                                    <span class="{{ $grant->expires_at->isPast() ? 'text-danger' : 'text-muted' }}">
                                        {{ $grant->expires_at->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-muted">Permanent</span>
                                @endif
                            </td>

                            {{-- Statut --}}
                            <td>
                                <span class="badge bg-{{ $grant->status_color }}">{{ $grant->status_label }}</span>
                                @if($grant->status_label === 'Révoquée')
                                    <div class="text-muted" style="font-size:11px">{{ Str::limit($grant->revoke_reason, 30) }}</div>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td>
                                @if($grant->isActive())
                                    <button class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modalRevoke"
                                            data-grant-id="{{ $grant->id }}"
                                            data-item="{{ $itemNames[$grant->id] ?? '?' }}"
                                            data-user="{{ $grant->user?->name }}">
                                        Révoquer
                                    </button>
                                @else
                                    <form method="POST" action="{{ route('admin.grants.restore', $grant) }}" class="d-inline">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-secondary"
                                                onclick="return confirm('Restaurer cet accès ?')">
                                            Restaurer
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Aucune attribution trouvée.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($grants->hasPages())
            <div class="card-footer">{{ $grants->links() }}</div>
        @endif
    </div>
</div>

{{-- ================================================================
     MODAL : NOUVELLE ATTRIBUTION
================================================================ --}}
<div class="modal fade" id="modalNewGrant" tabindex="-1" aria-labelledby="modalNewGrantLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST" action="{{ route('admin.grants.store') }}" id="formNewGrant">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="modalNewGrantLabel">🎁 Nouvelle attribution gratuite</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

          {{-- 1. UTILISATEUR --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Utilisateur <span class="text-danger">*</span></label>
            <div class="position-relative">
              <input type="text" id="userSearch" class="form-control" placeholder="Rechercher par nom ou email…" autocomplete="off">
              <input type="hidden" name="user_id" id="userId">
              <div id="userResults" class="list-group position-absolute w-100 shadow z-3" style="display:none; top:100%; max-height:220px; overflow-y:auto"></div>
            </div>
            <div id="userSelected" class="mt-1 text-success small" style="display:none"></div>
          </div>

          {{-- 2. TYPE --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Type d'item <span class="text-danger">*</span></label>
            <select name="type" id="grantType" class="form-select" required>
              <option value="">— Choisir un type —</option>
              @foreach($typeLabels as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>

          {{-- 3. ITEM SPÉCIFIQUE --}}
          <div class="mb-3" id="itemSection" style="display:none">
            <label class="form-label fw-semibold">Item spécifique <span class="text-danger">*</span></label>
            <select name="item_id" id="itemSelect" class="form-select">
              <option value="">Chargement…</option>
            </select>
          </div>

          {{-- Message LettreCI --}}
          <div class="mb-3 alert alert-info" id="lettreCIMsg" style="display:none">
            <strong>LettreCI</strong> — L'accès complet au module générateur de lettres sera attribué à l'utilisateur.
          </div>

          {{-- 4. RAISON --}}
          <div class="mb-3">
            <label class="form-label fw-semibold">Raison (recommandé)</label>
            <textarea name="reason" class="form-control" rows="2" maxlength="500"
                      placeholder="Ex: Gagnant concours TikTok mars 2026, testeur bêta, journaliste…"></textarea>
          </div>

          {{-- 5. EXPIRATION --}}
          <div class="mb-1">
            <label class="form-label fw-semibold">Date d'expiration <span class="text-muted fw-normal">(optionnel — vide = permanent)</span></label>
            <input type="date" name="expires_at" class="form-control" min="{{ now()->addDay()->format('Y-m-d') }}">
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-primary fw-semibold" id="btnSubmitGrant" disabled>
            🎁 Attribuer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- ================================================================
     MODAL : RÉVOQUER
================================================================ --}}
<div class="modal fade" id="modalRevoke" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="formRevoke">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title fw-bold text-danger">Révoquer une attribution</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Vous êtes sur le point de révoquer l'accès à <strong id="revokeItemName"></strong> pour <strong id="revokeUserName"></strong>.</p>
          <p class="text-danger small">L'accès sera coupé immédiatement.</p>
          <div class="mb-3">
            <label class="form-label fw-semibold">Raison de la révocation <span class="text-danger">*</span></label>
            <textarea name="revoke_reason" class="form-control" rows="2" required maxlength="500"
                      placeholder="Ex: Fin de la période de test, partenariat terminé…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Annuler</button>
          <button type="submit" class="btn btn-danger fw-semibold">Confirmer la révocation</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    /* ── Recherche utilisateur ── */
    const userSearch  = document.getElementById('userSearch');
    const userResults = document.getElementById('userResults');
    const userId      = document.getElementById('userId');
    const userSelected = document.getElementById('userSelected');
    const btnSubmit   = document.getElementById('btnSubmitGrant');

    let searchTimer;

    userSearch.addEventListener('input', () => {
        clearTimeout(searchTimer);
        const q = userSearch.value.trim();
        if (q.length < 2) { userResults.style.display = 'none'; return; }

        searchTimer = setTimeout(async () => {
            const res  = await fetch(`{{ route('admin.grants.user-search') }}?q=${encodeURIComponent(q)}`);
            const data = await res.json();

            userResults.innerHTML = '';
            if (!data.length) {
                userResults.innerHTML = '<div class="list-group-item text-muted small">Aucun résultat</div>';
            } else {
                data.forEach(u => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'list-group-item list-group-item-action small';
                    btn.innerHTML = `<strong>${u.name}</strong> <span class="text-muted">${u.email}</span>`;
                    btn.addEventListener('click', () => {
                        userId.value      = u.id;
                        userSearch.value  = u.name + ' — ' + u.email;
                        userSelected.textContent = '✓ Utilisateur sélectionné';
                        userSelected.style.display = 'block';
                        userResults.style.display  = 'none';
                        checkReady();
                    });
                    userResults.appendChild(btn);
                });
            }
            userResults.style.display = 'block';
        }, 300);
    });

    document.addEventListener('click', e => {
        if (!userSearch.contains(e.target) && !userResults.contains(e.target)) {
            userResults.style.display = 'none';
        }
    });

    /* ── Type d'item → chargement items ── */
    const grantType   = document.getElementById('grantType');
    const itemSection = document.getElementById('itemSection');
    const itemSelect  = document.getElementById('itemSelect');
    const lettreCIMsg = document.getElementById('lettreCIMsg');

    grantType.addEventListener('change', async () => {
        const type = grantType.value;
        itemSection.style.display = 'none';
        lettreCIMsg.style.display = 'none';
        itemSelect.innerHTML = '<option value="">Chargement…</option>';
        itemSelect.removeAttribute('required');

        if (!type) { checkReady(); return; }

        if (type === 'lettreci') {
            lettreCIMsg.style.display = 'block';
            checkReady();
            return;
        }

        const res  = await fetch(`{{ route('admin.grants.items') }}?type=${type}`);
        const data = await res.json();

        itemSelect.innerHTML = '<option value="">— Choisir un item —</option>';
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.title + (item.type ? ` (${item.type})` : '');
            itemSelect.appendChild(opt);
        });

        itemSection.style.display = 'block';
        itemSelect.setAttribute('required', '');
        checkReady();
    });

    itemSelect.addEventListener('change', checkReady);

    function checkReady() {
        const hasUser = !!userId.value;
        const hasType = !!grantType.value;
        const hasItem = grantType.value === 'lettreci' || (itemSelect.value !== '');
        btnSubmit.disabled = !(hasUser && hasType && hasItem);
    }

    /* ── Modal révocation ── */
    const modalRevoke   = document.getElementById('modalRevoke');
    const formRevoke    = document.getElementById('formRevoke');
    const revokeItem    = document.getElementById('revokeItemName');
    const revokeUser    = document.getElementById('revokeUserName');
    const baseRevokeUrl = '{{ url("/admin/grants") }}/';

    modalRevoke.addEventListener('show.bs.modal', e => {
        const btn     = e.relatedTarget;
        const grantId = btn.dataset.grantId;
        revokeItem.textContent = btn.dataset.item;
        revokeUser.textContent = btn.dataset.user;
        formRevoke.action = baseRevokeUrl + grantId + '/revoke';
    });

});
</script>
@endpush
