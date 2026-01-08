<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-4 py-10 bg-gray-50">
        <div class="w-full max-w-md">
            {{-- Header logo --}}
            <div class="text-center mb-6">
                <a href="{{ route('landing') }}" class="inline-flex items-center justify-center">
                    <img src="{{ asset('img/coach-brvm-logo.png') }}"
                         alt="Coach BRVM"
                         class="h-16 w-auto drop-shadow-sm">
                </a>
                <h1 class="mt-3 text-2xl font-bold text-gray-900">Connexion</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Accède à ton espace Coach BRVM (portefeuille virtuel, analyses, etc.).
                </p>
            </div>

            {{-- Card --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Address -->
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email"
                                      class="block mt-1 w-full"
                                      type="email"
                                      name="email"
                                      :value="old('email')"
                                      required
                                      autofocus
                                      autocomplete="username" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div class="mt-4">
                        <x-input-label for="password" :value="__('Mot de passe')" />
                        <x-text-input id="password"
                                      class="block mt-1 w-full"
                                      type="password"
                                      name="password"
                                      required
                                      autocomplete="current-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between mt-4">
                        <label for="remember_me" class="inline-flex items-center">
                            <input id="remember_me"
                                   type="checkbox"
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                   name="remember">
                            <span class="ms-2 text-sm text-gray-600">{{ __('Se souvenir de moi') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-sm text-gray-600 hover:text-gray-900 underline"
                               href="{{ route('password.request') }}">
                                {{ __('Mot de passe oublié ?') }}
                            </a>
                        @endif
                    </div>

                    <div class="mt-6">
                        <x-primary-button class="w-full justify-center">
                            {{ __('Se connecter') }}
                        </x-primary-button>
                    </div>

                    <div class="mt-5 text-center text-sm text-gray-600">
                        Pas encore de compte ?
                        <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 underline">
                            Créer un compte
                        </a>
                    </div>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-gray-500">
                © {{ date('Y') }} Coach BRVM – CHENGGONG SARL
            </p>
        </div>
    </div>
</x-guest-layout>
