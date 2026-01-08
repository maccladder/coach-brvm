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
                <h1 class="mt-3 text-2xl font-bold text-gray-900">Créer un compte</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Inscris-toi pour accéder au portefeuille virtuel et aux services Coach BRVM.
                </p>
            </div>

            {{-- Card --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
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
                © {{ date('Y') }} Coach BRVM – CHENGGONG SARL
            </p>
        </div>
    </div>
</x-guest-layout>
