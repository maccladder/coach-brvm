@extends('layouts.app')

@section('content')
<div class="container py-5" style="max-width: 1100px;">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fw-bold mb-1">💼 Portefeuille Virtuel (Admin)</h2>
            <div class="text-muted">Squelette de test — on branchera la DB après.</div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.market.index') }}" class="btn btn-outline-primary">
                📈 Cours Actions BRVM
            </a>
            <button class="btn btn-primary" disabled>
                ➕ Créer un portefeuille (bientôt)
            </button>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card p-4">
                <div class="fw-bold mb-2">⚙️ Actions rapides</div>
                <div class="text-muted small mb-3">
                    Pour l’instant, on met en place l’interface + la source des cours.
                </div>

                <ul class="list-unstyled mb-0">
                    <li class="mb-2">✅ Page portefeuille admin</li>
                    <li class="mb-2">✅ Page cours actions BRVM</li>
                    <li class="mb-2">⏳ Création portefeuille + PIN</li>
                    <li class="mb-2">⏳ Achat/Vente + positions</li>
                </ul>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card p-4">
                <div class="fw-bold mb-2">📊 Aperçu (mock)</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Solde virtuel</div>
                            <div class="fs-4 fw-bold">1 000 000</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Valeur portefeuille</div>
                            <div class="fs-4 fw-bold">1 000 000</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3">
                            <div class="text-muted small">Performance</div>
                            <div class="fs-4 fw-bold text-success">+0.00%</div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="fw-bold mb-2">🧾 Positions (mock)</div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Action</th>
                                <th>Quantité</th>
                                <th>PRU</th>
                                <th>Cours</th>
                                <th>P&L</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>SONATEL</td>
                                <td>0</td>
                                <td>—</td>
                                <td>—</td>
                                <td class="text-muted">—</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-muted small">
                    Prochaine étape : créer un portefeuille en admin + lier les cours BRVM à ce tableau.
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
