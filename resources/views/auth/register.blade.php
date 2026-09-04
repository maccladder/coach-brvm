<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center px-4 py-10 bg-gray-50">
        <div class="w-full max-w-md">
            {{-- Header logo --}}
            <div class="text-center mb-6">
                <a href="{{ route('landing') }}" class="inline-flex items-center justify-center">
                    <span style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:700;color:#1a1a1a;letter-spacing:-.01em;">Boursiv</span>
                </a>
                <h1 class="mt-3 text-2xl font-bold text-gray-900">Créer un compte</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Inscris-toi pour accéder au portefeuille virtuel et aux services Boursiv.
                </p>
            </div>

            {{-- Card --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">

                {{-- ✅ Google Sign-up --}}
                <a href="{{ route('auth.google') }}"
                   class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-sm text-gray-700 hover:bg-gray-50">
                    <svg class="w-5 h-5" viewBox="0 0 48 48" aria-hidden="true">
                        <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303C33.659 32.676 29.192 36 24 36c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.996 6.053 29.754 4 24 4 12.955 4 4 12.955 4 24s8.955 20 20 20 20-8.955 20-20c0-1.341-.138-2.651-.389-3.917z"/>
                        <path fill="#FF3D00" d="M6.306 14.691l6.571 4.819C14.655 16.108 19.027 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.996 6.053 29.754 4 24 4c-7.682 0-14.33 4.326-17.694 10.691z"/>
                        <path fill="#4CAF50" d="M24 44c5.08 0 9.82-1.941 13.357-5.097l-6.172-5.226C29.205 35.091 26.715 36 24 36c-5.171 0-9.625-3.299-11.267-7.915l-6.52 5.02C9.553 39.556 16.23 44 24 44z"/>
                        <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a11.96 11.96 0 0 1-4.118 5.677l.002-.001 6.172 5.226C36.93 39.326 44 34 44 24c0-1.341-.138-2.651-.389-3.917z"/>
                    </svg>
                    S’inscrire avec Google
                </a>

                {{-- Séparateur --}}
                <div class="my-5 flex items-center gap-3">
                    <div class="h-px bg-gray-200 flex-1"></div>
                    <span class="text-xs uppercase tracking-wider text-gray-400">ou</span>
                    <div class="h-px bg-gray-200 flex-1"></div>
                </div>

                <form method="POST" action="{{ route('register') }}">
                    @csrf

                    <!-- Name -->
                    <div>
                        <x-input-label for="name" :value="__('Nom')" />
                        <x-text-input id="name"
                                      class="block mt-1 w-full"
                                      type="text"
                                      name="name"
                                      :value="old('name')"
                                      required
                                      autofocus
                                      autocomplete="name" />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Email Address -->
                    <div class="mt-4">
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email"
                                      class="block mt-1 w-full"
                                      type="email"
                                      name="email"
                                      :value="old('email')"
                                      required
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
                                      autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Confirm Password -->
                    <div class="mt-4">
                        <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
                        <x-text-input id="password_confirmation"
                                      class="block mt-1 w-full"
                                      type="password"
                                      name="password_confirmation"
                                      required
                                      autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>

                    <!-- hCaptcha -->
                    <div class="mt-4">
                        <div class="h-captcha" data-sitekey="{{ config('services.hcaptcha.site_key') }}"></div>
                        <x-input-error :messages="$errors->get('h-captcha-response')" class="mt-2" />
                    </div>

                    <div class="mt-6">
                        <x-primary-button class="w-full justify-center">
                            {{ __('Créer mon compte') }}
                        </x-primary-button>
                    </div>

                    <div class="mt-5 text-center text-sm text-gray-600">
                        Déjà inscrit ?
                        <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 underline">
                            Se connecter
                        </a>
                    </div>
                </form>
            </div>

            <p class="mt-6 text-center text-xs text-gray-500">
                © {{ date('Y') }} Boursiv – CHENGGONG SARL
            </p>
        </div>
    </div>

    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
</x-guest-layout>
