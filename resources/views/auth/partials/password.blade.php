{{-- Champ mot de passe + bouton afficher/masquer ; erreur sous le champ --}}
@php($fieldError = $errors->first($id))
<div class="ba-pass">
    <input id="{{ $id }}" name="{{ $id }}" type="password"
           class="ba-input {{ $fieldError ? 'is-invalid' : '' }}"
           required autocomplete="{{ $autocomplete }}"
           @if($fieldError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif>
    <button type="button" class="ba-eye" data-toggle-password="{{ $id }}"
            aria-controls="{{ $id }}" aria-pressed="false" aria-label="Afficher le mot de passe">
        <svg class="ba-eye-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="ba-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.6 10.6 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-3.2 4.2M6.1 6.1C3.6 7.8 2 12 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
    </button>
</div>
@if($fieldError)
    <p class="ba-error" id="{{ $id }}-error">{{ $fieldError }}</p>
@endif
