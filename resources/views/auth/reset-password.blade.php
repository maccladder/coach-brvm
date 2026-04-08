<x-guest-layout>

    <div style="text-align:center;margin-bottom:28px;">
        <h1 style="font-family:'Playfair Display',serif;font-size:26px;font-weight:900;color:#E8EAF0;margin-bottom:6px;">
            Nouveau mot de passe
        </h1>
        <p style="font-family:'DM Sans',sans-serif;font-size:14px;color:#6B7590;font-weight:300;">
            Choisis un nouveau mot de passe sécurisé.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div style="margin-bottom:18px;">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email"
                          style="margin-top:6px;"
                          type="email"
                          name="email"
                          :value="old('email', $request->email)"
                          required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div style="margin-bottom:18px;">
            <x-input-label for="password" :value="__('Nouveau mot de passe')" />
            <x-text-input id="password"
                          style="margin-top:6px;"
                          type="password"
                          name="password"
                          required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div style="margin-bottom:28px;">
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
            <x-text-input id="password_confirmation"
                          style="margin-top:6px;"
                          type="password"
                          name="password_confirmation"
                          required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button type="submit">
            Réinitialiser le mot de passe →
        </button>

    </form>

</x-guest-layout>
