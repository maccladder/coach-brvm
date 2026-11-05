<x-guest-layout>
    <x-slot name="title">Connexion</x-slot>

    <div class="ba-head">
        <h1 class="ba-title">Content de te revoir</h1>
        <p class="ba-sub">Connecte-toi pour retrouver ta progression et ton portefeuille.</p>
    </div>

    @if (session('status'))
        <div class="ba-status" role="status">{{ session('status') }}</div>
    @endif

    @include('auth.partials.google', ['label' => 'Continuer avec Google'])

    <div class="ba-sep"><span>ou avec ton email</span></div>

    <form method="POST" action="{{ route('login') }}" data-loading-form novalidate>
        @csrf

        {{-- Email --}}
        <div class="ba-field">
            <label for="email" class="ba-label">Email</label>
            <input id="email" name="email" type="email" inputmode="email"
                   class="ba-input @error('email') is-invalid @enderror"
                   value="{{ old('email') }}"
                   required autofocus autocomplete="email" autocapitalize="none" spellcheck="false"
                   placeholder="toi@exemple.com"
                   @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')
                <p class="ba-error" id="email-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Mot de passe --}}
        <div class="ba-field">
            <label for="password" class="ba-label">Mot de passe</label>
            @include('auth.partials.password', ['id' => 'password', 'autocomplete' => 'current-password'])
        </div>

        <div class="ba-row">
            <label class="ba-check" for="remember_me">
                <input id="remember_me" type="checkbox" name="remember" @checked(old('remember'))>
                Se souvenir de moi
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="ba-link">Mot de passe oublié ?</a>
            @endif
        </div>

        <button type="submit" class="ba-submit" data-label="Se connecter" data-loading-text="Connexion…">
            <span class="ba-spinner" aria-hidden="true"></span>
            <span class="ba-submit-label">Se connecter</span>
        </button>

        <p class="ba-alt">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="ba-link">Créer un compte gratuit</a>
        </p>
    </form>
</x-guest-layout>
