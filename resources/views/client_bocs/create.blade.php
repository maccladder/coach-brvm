@extends('layouts.app')

@section('content')
<div class="container py-4">

  <div class="row justify-content-center mb-4">
    <div class="col-lg-8 text-center">
      <h2 class="fw-bold">Analyse Automatique de Votre BOC 📊</h2>
      <p class="text-muted fs-5">
        Ne perdez plus des heures à lire ce document compliqué.<br>
        Pour seulement <span class="fw-bold text-success">1000 FCFA</span>, notre IA lit, interprète et résume votre BOC.
      </p>
    </div>
  </div>

  <!-- SECTION : Processus -->
  <div class="row justify-content-center mb-4">
    <div class="col-lg-8">
      <div class="card shadow-sm border-0">
        <div class="card-body d-flex flex-column flex-md-row text-center text-md-start">

          <div class="me-md-4 mb-3 mb-md-0">
            <span class="badge bg-primary rounded-circle p-3 fs-5">1</span>
          </div>
          <div>
            <h5 class="fw-bold">Upload du BOC</h5>
            <p class="mb-0 text-muted">Envoyez votre BOC en PDF, image ou document scanné.</p>
          </div>

        </div>

        <div class="card-body d-flex flex-column flex-md-row text-center text-md-start border-top">

          <div class="me-md-4 mb-3 mb-md-0">
            <span class="badge bg-primary rounded-circle p-3 fs-5">2</span>
          </div>
          <div>
            <h5 class="fw-bold">Paiement Mobile Money</h5>
            <p class="mb-0 text-muted">Payez seulement <span class="fw-bold text-success">1000 FCFA</span> via MTN, Orange ou Wave.</p>
          </div>

        </div>

        <div class="card-body d-flex flex-column flex-md-row text-center text-md-start border-top">

          <div class="me-md-4 mb-3 mb-md-0">
            <span class="badge bg-primary rounded-circle p-3 fs-5">3</span>
          </div>
          <div>
            <h5 class="fw-bold">Analyse Instantanée</h5>
            <p class="mb-0 text-muted">
              Recevez : interprétation complète, résumé clair, audio du résumé, et avatar qui lit votre analyse.
            </p>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- SECTION : Formulaire principal -->
  <div class="row justify-content-center">
    <div class="col-lg-8">

      <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
          <h5 class="mb-0 fw-bold">Uploader votre BOC</h5>
          <small class="text-muted">Service rapide – Analyse immédiate après paiement</small>
        </div>

        <div class="card-body">

          @if ($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <form action="{{ route('client-bocs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
              <label class="form-label fw-bold">Titre (optionnel)</label>
              <input type="text" name="title" class="form-control"
                     placeholder="BOC de novembre, portefeuille X…" value="{{ old('title') }}">
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Date du BOC</label>
              <input type="date" name="boc_date" class="form-control"
                     value="{{ old('boc_date', now()->toDateString()) }}" required>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold">Fichier BOC</label>
              <input type="file" name="file" class="form-control" required>
              <small class="text-muted">PDF, image ou document scanné accepté.</small>
            </div>

            <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">
              🚀 Envoyer & Démarrer l’analyse (1000 FCFA)
            </button>
          </form>

        </div>
      </div>

    </div>
  </div>
</div>
@endsection
