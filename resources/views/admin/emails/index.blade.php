@extends('layouts.admin')

@section('title', 'Emails utilisateurs – Admin')

@section('content')
<div class="container py-4" style="max-width:1200px;">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h3 class="fw-bold mb-1">📧 Envoyer un email</h3>
            <div class="text-muted">Coche des utilisateurs ou envoie à tous.</div>
        </div>

        <form method="GET" class="d-flex gap-2">
            <input name="q" value="{{ $q }}" class="form-control" placeholder="Rechercher nom/email...">
            <button class="btn btn-outline-secondary">Rechercher</button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.emails.send') }}">
        @csrf

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">👥 Sélection des destinataires</div>
                    <div class="card-body">

                        <div class="d-flex flex-wrap gap-3 align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="modeSelected" value="selected" checked>
                                <label class="form-check-label" for="modeSelected">Envoyer aux utilisateurs cochés</label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="modeAll" value="all">
                                <label class="form-check-label" for="modeAll">Envoyer à tous</label>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-primary ms-auto" id="btnSelectAll">
                                Tout cocher
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnUnselectAll">
                                Tout décocher
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th style="width:40px;"></th>
                                        <th>Nom</th>
                                        <th>Email</th>
                                        <th>Date d’inscription</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($users as $u)
                                    <tr>
                                        <td>
                                            <input class="form-check-input user-check"
                                                type="checkbox"
                                                name="user_ids[]"
                                                value="{{ $u->id }}">
                                        </td>
                                        <td class="fw-semibold">{{ $u->name }}</td>
                                        <td class="text-muted">{{ $u->email }}</td>
                                        <td class="text-muted">{{ optional($u->created_at)->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-2">
                            {{ $users->links() }}
                        </div>

                        <small class="text-muted d-block mt-2">
                            Note: si “Envoyer à tous” est sélectionné, les cases sont ignorées.
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header fw-semibold">✍️ Contenu du message</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Sujet</label>
                            <input name="subject" class="form-control" required maxlength="200"
                                   placeholder="Ex: Nouveautés Coach-BRVM">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="9" required maxlength="10000"
                                      placeholder="Ton message ici..."></textarea>
                        </div>

                        <button class="btn btn-success w-100 fw-semibold">
                            🚀 Envoyer l’email
                        </button>

                        <div class="text-muted small mt-2">
                            Astuce: pour des gros volumes, on passera l’envoi en queue (job) pour éviter timeout.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const checks = document.querySelectorAll('.user-check');
    document.getElementById('btnSelectAll').addEventListener('click', () => checks.forEach(c => c.checked = true));
    document.getElementById('btnUnselectAll').addEventListener('click', () => checks.forEach(c => c.checked = false));
});
</script>
@endsection
