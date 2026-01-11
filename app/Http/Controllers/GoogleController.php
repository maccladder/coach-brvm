<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
     public function redirect()
    {
        return Socialite::driver('google')
            ->stateless()
            ->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->user();

        $email = $googleUser->getEmail();

        // 1) Si utilisateur existe déjà par email -> login
        $user = User::where('email', $email)->first();

        // 2) Sinon -> create
        if (!$user) {
            $user = User::create([
                'name' => $googleUser->getName() ?: $googleUser->getNickname() ?: 'Utilisateur',
                'email' => $email,
                'password' => bcrypt(Str::random(32)), // inutilisé mais requis si colonne non nullable
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended('/dashboard');
    }
}
