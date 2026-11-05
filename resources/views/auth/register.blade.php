<x-guest-layout>
    <x-slot name="title">Créer un compte</x-slot>

    <div class="ba-head">
        <h1 class="ba-title">Crée ton compte gratuit</h1>
        <p class="ba-sub">Sauvegarde ta progression, entraîne-toi sur la BRVM et accède aux formations.</p>
    </div>

    @include('auth.partials.google', ['label' => "S'inscrire avec Google"])

    <div class="ba-sep"><span>ou avec ton email</span></div>

    <form method="POST" action="{{ route('register') }}" data-loading-form novalidate>
        @csrf

        {{-- Nom (apostrophes autorisées : N'Guessan…) --}}
        <div class="ba-field">
            <label for="name" class="ba-label">Nom</label>
            <input id="name" name="name" type="text"
                   class="ba-input @error('name') is-invalid @enderror"
                   value="{{ old('name') }}"
                   required autofocus autocomplete="name" autocapitalize="words"
                   placeholder="Ex. Aya N'Guessan"
                   @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
            @error('name')
                <p class="ba-error" id="name-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div class="ba-field">
            <label for="email" class="ba-label">Email</label>
            <input id="email" name="email" type="email" inputmode="email"
                   class="ba-input @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   required autocomplete="email" autocapitalize="none" spellcheck="false"
                   placeholder="toi@exemple.com"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p class="ba-error" id="email-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mot de passe --}}
        <div class="ba-field">
            <label for="password" class="ba-label">Mot de passe</label>
            @include('auth.partials.password', ['id' => 'password', 'autocomplete' => 'new-password'])
            @unless($errors->has('password'))
                <p class="ba-hint">8 caractères minimum.</p>
            @endunless
        </div>

        {{-- Confirmation --}}
        <div class="ba-field">
            <label for="password_confirmation" class="ba-label">Confirmer le mot de passe</label>
            @include('auth.partials.password', ['id' => 'password_confirmation', 'autocomplete' => 'new-password'])
        </div>

        {{-- hCaptcha --}}
        <div class="ba-captcha">
            <div class="h-captcha" data-sitekey="{{ config('services.hcaptcha.site_key') }}" data-theme="dark"></div>
            @error('h-captcha-response')
                <p class="ba-error" id="captcha-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="ba-submit" data-label="Créer mon compte" data-loading-text="Création du compte…">
            <span class="ba-spinner" aria-hidden="true"></span>
            <span class="ba-submit-label">Créer mon compte</span>
        </button>

        <p class="ba-alt">
            Déjà inscrit ?
            <a href="{{ route('login') }}" class="ba-link">Se connecter</a>
        </p>
    </form>

    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
</x-guest-layout>
