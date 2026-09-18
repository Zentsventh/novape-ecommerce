<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use App\Services\Auth\SocialAuthService;

class GoogleAuthController extends Controller
{
    public function __construct(
        private readonly SocialAuthService $socialAuthService
    ) {}

    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $sessionCart = session()->get('cart', []);
            $user = $this->socialAuthService->handleGoogleUser($googleUser, session()->getId(), $sessionCart);

            Auth::login($user, true);

            $intendedUrl = session()->pull('url.intended', '/cliente/ordenes');
            if (\Illuminate\Support\Str::contains($intendedUrl, '/admin')) {
                $intendedUrl = '/cliente/ordenes';
            }
            return redirect()->to($intendedUrl);
            
        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['email' => 'No se pudo iniciar sesión con Google. ' . $e->getMessage()]);
        }
    }
}
