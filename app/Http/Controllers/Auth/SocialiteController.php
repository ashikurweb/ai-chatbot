<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialiteController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect('/login')->with('error', 'Authentication failed.');
        }

        $user = User::updateOrCreate([
            'email' => $socialUser->getEmail(),
        ], [
            'name' => $socialUser->getName() ?? $socialUser->getNickname(),
            $provider . '_id' => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            // Passwords are not really needed for social users, but we set a random one
            // if it's a new user to satisfy database constraints if any (though we made it nullable)
            'password' => $user->password ?? null, 
        ]);

        Auth::login($user);

        return redirect('/');
    }
}
