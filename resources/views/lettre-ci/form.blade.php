{{-- resources/views/lettre-ci/form.blade.php --}}
@extends('layouts.app')

@push('styles')
<style>
    .lci-page { background: #060910; min-height: 100vh; padding-bottom: 80px; }

    .lci-header {
        background: radial-gradient(ellipse 80% 50% at 50% 0%, rgba(201,168,76,.1) 0%, transparent 55%), #060910;
        border-bottom: 1px solid rgba(201,168,76,.08);
        padding: 36px 0 28px; position: relative; overflow: hidden;
    }
    .lci-header-grid { position:absolute;inset:0;background-image:linear-gradient(rgba(201,168,76,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(201,168,76,.04) 1px,transparent 1px);background-size:56px 56px;mask-image:radial-gradient(ellipse 80% 70% at 50% 50%,black 0%,transparent 70%);pointer-events:none; }
    .lci-header-tag { font-family:'Syne',sans-serif;font-size:11px;font-weight:600;letter-spacing:.2em;text-transform:uppercase;color:#C9A84C;display:flex;align-items:center;gap:10px;margin-bottom:12px; }
    .lci-header-tag::before { content:'';width:28px;height:1px;background:#C9A84C; }
    .lci-header-title { font-family:'Playfair Display',serif;font-size:clamp(22px,3.5vw,34px);font-weight:900;color:#E8EAF0;margin-bottom:4px; }
    .lci-header-sub { font-size:13px;color:#6B7590; }

    .cb-btn-outline {
        display:inline-flex;align-items:center;gap:7px;background:transparent;color:#6B7590 !important;
        font-family:'Syne',sans-serif;font-weight:600;font-size:11px;letter-spacing:.08em;text-transform:uppercase;
        padding:8px 14px;border:1px solid rgba(255,255,255,.08);border-radius:3px;text-decoration:none;transition:all .25s;
    }
    .cb-btn-outline:hover { border-color:#C9A84C;color:#C9A84C !important; }

    /* Form wrapper */
    .lci-form-wrap {
        max-width: 660px; margin: 36px auto 0;
        background: #0C1120; border: 1px solid rgba(201,168,76,.1);
        border-radius: 6px; overflow: hidden;
    }
    .lci-form-wrap::before {
        content:'';display:block;height:2px;
        background:linear-gradient(90deg,#C9A84C,#0FCFA4,transparent);
    }
    .lci-form-inner { padding: 36px 36px 32px; }

    .lci-notice {
        background: rgba(201,168,76,.05); border: 1px solid rgba(201,168,76,.12);
        border-radius: 4px; padding: 12px 16px;
        font-size: 13px; color: #A0A8C0; line-height: 1.6; margin-bottom: 28px;
    }

    /* Field styles */
    .lci-field-lbl {
        font-family:'Syne',sans-serif;font-size:11px;font-weight:700;
        letter-spacing:.1em;text-transform:uppercase;color:#A0A8C0;
        margin-bottom:8px;display:block;
    }
    .lci-field-lbl .req { color:#FF6B6B;margin-left:3px; }
    .lci-input {
        width:100%;background:rgba(255,255,255,.04);
        border:1px solid rgba(255,255,255,.09);
        border-radius:3px;padding:11px 14px;color:#E8EAF0;font-size:14px;
        font-family:'DM Sans',sans-serif;line-height:1.5;
        transition:border-color .2s,background .2s;
        display:block;
    }
    .lci-input:focus { outline:none;border-color:rgba(201,168,76,.4);background:rgba(255,255,255,.06); }
    .lci-input::placeholder { color:#3E4560; }
    .lci-textarea { resize:vertical;min-height:96px; }
    .lci-select { appearance:none;cursor:pointer; }

    /* Submit button */
    .cb-btn-gold {
        display:inline-flex;align-items:center;gap:8px;justify-content:center;width:100%;
        background:linear-gradient(135deg,#C9A84C,#9B6B15);
        color:#050810 !important;font-family:'Syne',sans-serif;
        font-weight:800;font-size:13px;letter-spacing:.07em;text-transform:uppercase;
        padding:14px 28px;border:none;border-radius:3px;cursor:pointer;
        text-decoration:none;transition:all .3s;margin-top:8px;
    }
    .cb-btn-gold:hover { box-shadow:0 8px 28px rgba(201,168,76,.3);transform:translateY(-1px); }

    .cbr { opacity:0;transform:translateY(16px);transition:all .6s cubic-bezier(.16,1,.3,1); }
    .cbr.on { opacity:1;transform:translateY(0); }
</style>
@endpush

@section('content')
<div class="lci-page">
    <div class="lci-header">
        <div class="lci-header-grid"></div>
        <div class="container" style="position:relative;z-index:1;">
            <div class="lci-header-tag">LettreCI · Formulaire</div>
            <h1 class="lci-header-title">{{ $template['icon'] ?? '📄' }} {{ $template['name'] }}</h1>
            <p class="lci-header-sub">{{ $template['description'] ?? 'Remplissez les informations pour générer votre lettre.' }}</p>
        </div>
    </div>

    <div class="container">
        <a href="{{ route('lettreci.new') }}" class="cb-btn-outline mt-4 mb-1 d-inline-flex">← Choisir un autre modèle</a>

        <div class="lci-form-wrap cbr">
            <div class="lci-form-inner">
                <div class="lci-notice">
                    🤖 La lettre sera rédigée automatiquement par l'IA Claude en moins de 30 secondes.
                    Vous pourrez la modifier avant d'exporter en PDF.
                </div>

                @if($errors->any())
                <div class="alert alert-danger mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                        <li style="font-size:13px;">{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('lettreci.submit', $slug) }}">
                    @csrf
                    @foreach($template['fields'] as $field)
                    <div class="mb-4">
                        <label class="lci-field-lbl" for="f_{{ $field['name'] }}">
                            {{ $field['label'] }}
                            @if($field['required'] ?? true)<span class="req">*</span>@endif
                        </label>

                        @if($field['type'] === 'textarea')
                            <textarea
                                id="f_{{ $field['name'] }}" name="{{ $field['name'] }}"
                                class="lci-input lci-textarea"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                {{ ($field['required'] ?? true) ? 'required' : '' }}>{{ old($field['name']) }}</textarea>

                        @elseif($field['type'] === 'select')
                            <select id="f_{{ $field['name'] }}" name="{{ $field['name'] }}"
                                class="lci-input lci-select" {{ ($field['required'] ?? true) ? 'required' : '' }}>
                                <option value="">— Sélectionner —</option>
                                @foreach($field['options'] ?? [] as $opt)
                                <option value="{{ $opt }}" {{ old($field['name']) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>

                        @elseif($field['type'] === 'date')
                            <input type="date" id="f_{{ $field['name'] }}" name="{{ $field['name'] }}"
                                class="lci-input" value="{{ old($field['name']) }}"
                                {{ ($field['required'] ?? true) ? 'required' : '' }}>

                        @elseif($field['type'] === 'number')
                            <input type="number" id="f_{{ $field['name'] }}" name="{{ $field['name'] }}"
                                class="lci-input" placeholder="{{ $field['placeholder'] ?? '' }}"
                                value="{{ old($field['name']) }}"
                                {{ ($field['required'] ?? true) ? 'required' : '' }}>

                        @else
                            <input type="text" id="f_{{ $field['name'] }}" name="{{ $field['name'] }}"
                                class="lci-input" placeholder="{{ $field['placeholder'] ?? '' }}"
                                value="{{ old($field['name']) }}"
                                {{ ($field['required'] ?? true) ? 'required' : '' }}>
                        @endif
                    </div>
                    @endforeach

                    <button type="submit" class="cb-btn-gold">
                        ✨ Générer ma lettre
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.cbr').forEach(el => {
        new IntersectionObserver(([e]) => { if(e.isIntersecting) e.target.classList.add('on'); }, {threshold:.05}).observe(el);
    });
</script>
@endpush
