<x-guest-layout>

    <div style="text-align:center;margin-bottom:28px;">
        <h1 style="font-family:'Playfair Display',serif;font-size:26px;font-weight:900;color:#E8EAF0;margin-bottom:6px;">
            Mot de passe oublié
        </h1>
        <p style="font-family:'DM Sans',sans-serif;font-size:14px;color:#6B7590;font-weight:300;">
            Saisis ton email — on t'envoie un lien de réinitialisation.
        </p>
    </div>

    {{-- Message de confirmation --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div style="margin-bottom:24px;">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email"
                          style="margin-top:6px;"
                          type="email"
                          name="email"
                          :value="old('email')"
                          required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit">
            Envoyer le lien →
        </button>

        <p style="text-align:center;margin-top:20px;font-family:'DM Sans',sans-serif;
                  font-size:13px;color:#6B7590;">
            Tu te souviens de ton mot de passe ?
            <a href="{{ route('login') }}"
               style="font-family:'Syne',sans-serif;font-weight:700;font-size:12px;
                      letter-spacing:.06em;text-transform:uppercase;color:#C9A84C;">
                Se connecter
            </a>
        </p>
    </form>

</x-guest-layout>
