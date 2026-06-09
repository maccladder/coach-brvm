<x-guest-layout>

    {{-- Titre de la card --}}
    <div style="text-align:center;margin-bottom:28px;">
        <h1 style="font-family:'Playfair Display',serif;font-size:26px;font-weight:900;color:#E8EAF0;margin-bottom:6px;">
            Connexion
        </h1>
        <p style="font-family:'DM Sans',sans-serif;font-size:14px;color:#6B7590;font-weight:300;">
            Accède à ton espace Boursiv
        </p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- Google --}}
    <a href="{{ route('auth.google') }}" class="cb-google-btn">
        <svg style="width:18px;height:18px;flex-shrink:0;" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.659 32.676 29.192 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.996 6.053 29.754 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.651-.389-3.917z"/>
            <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 16.108 19.027 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.996 6.053 29.754 4 24 4c-7.682 0-14.33 4.326-17.694 10.691z"/>
            <path fill="#4CAF50" d="M24 44c5.08 0 9.82-1.941 13.357-5.097l-6.172-5.226C29.205 35.091 26.715 36 24 36c-5.171 0-9.625-3.299-11.267-7.915l-6.52 5.02C9.553 39.556 16.23 44 24 44z"/>
            <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a11.96 11.96 0 0 1-4.118 5.677l6.172 5.226C36.93 39.326 44 34 44 24c0-1.341-.138-2.651-.389-3.917z"/>
        </svg>
        Continuer avec Google
    </a>

    {{-- Séparateur --}}
    <div class="cb-sep">
        <div class="cb-sep-line"></div>
        <span class="cb-sep-text">ou</span>
        <div class="cb-sep-line"></div>
    </div>

    {{-- Formulaire --}}
    <form method="POST" action="{{ route('login') }}">
        @csrf

        {{-- Email --}}
        <div style="margin-bottom:18px;">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email"
                          style="margin-top:6px;"
                          type="email"
                          name="email"
                          :value="old('email')"
                          required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Mot de passe --}}
        <div style="margin-bottom:18px;">
            <x-input-label for="password" :value="__('Mot de passe')" />
            <x-text-input id="password"
                          style="margin-top:6px;"
                          type="password"
                          name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Remember + Forgot --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:8px;">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                <input id="remember_me" type="checkbox" name="remember">
                <span style="font-family:'DM Sans',sans-serif;font-size:13px;color:#6B7590;">
                    Se souvenir de moi
                </span>
            </label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   style="font-family:'Syne',sans-serif;font-size:11px;font-weight:600;
                          letter-spacing:.06em;text-transform:uppercase;color:#6B7590;">
                    Mot de passe oublié ?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit">
            Se connecter →
        </button>

        {{-- Lien inscription --}}
        <p style="text-align:center;margin-top:20px;font-family:'DM Sans',sans-serif;
                  font-size:13px;color:#6B7590;">
            Pas encore de compte ?
            <a href="{{ route('register') }}"
               style="font-family:'Syne',sans-serif;font-weight:700;font-size:12px;
                      letter-spacing:.06em;text-transform:uppercase;color:#C9A84C;">
                S'inscrire
            </a>
        </p>

    </form>

</x-guest-layout>
