<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $cart = session()->get('cart', []);
        
        $cartTotal = array_reduce($cart, function ($carry, $item) {
            return $carry + ($item['precio'] * $item['cantidad']);
        }, 0);

        $cartCount = array_reduce($cart, function ($carry, $item) {
            return $carry + $item['cantidad'];
        }, 0);

        // Usa el guard 'admin' si estamos en una ruta de admin, caso contrario el normal
        $user = $request->is('admin*') ? auth('admin')->user() : $request->user();
        if ($user) {
            $user->loadMissing('roles');
        }
        $permisos = $user ? $user->getAllPermisos() : [];

        // Detección de dispositivo server-side (User-Agent nativo — sin dependencias)
        $ua = strtolower($request->header('User-Agent', ''));
        $isMobile = (bool) preg_match('/mobile|android.*mobile|iphone|ipod|blackberry|opera mini|iemobile/i', $ua);
        $isTablet = !$isMobile && (bool) preg_match('/tablet|ipad|android(?!.*mobile)|kindle|silk/i', $ua);
        $isDesktop = !$isMobile && !$isTablet;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), ['permisos' => $permisos]) : null,
            ],
            'cart' => [
                'items' => array_values($cart),
                'count' => $cartCount,
                'total' => $cartTotal
            ],
            'device' => [
                'isMobile' => $isMobile,
                'isTablet' => $isTablet,
                'isDesktop' => $isDesktop,
            ],
            'globalConfig' => fn () => \Illuminate\Support\Facades\Cache::remember('globalConfig', 3600, function () {
                return [
                    'facebook_url' => \App\Models\ConfiguracionSitio::obtener('facebook_url', 'https://facebook.com/novape'),
                    'instagram_url' => \App\Models\ConfiguracionSitio::obtener('instagram_url', 'https://instagram.com/novape'),
                    'telefono_contacto' => \App\Models\ConfiguracionSitio::obtener('telefono_contacto', '+51 999 888 777'),
                    'email_contacto' => \App\Models\ConfiguracionSitio::obtener('email_contacto', 'contacto@novape.com'),
                    'logo_url' => \App\Models\ConfiguracionSitio::obtener('logo_url'),
                ];
            }),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'errors' => function () use ($request) {
                $errors = $request->session()->get('errors');
                if ($errors) {
                    return $errors->getBag('default')->toArray();
                }
                return (object) [];
            },
        ];
    }
}
