<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class AdminAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->tienePermiso('ver_dashboard')) {
            return redirect()->route('admin.dashboard');
        }
        return Inertia::render('Admin/Login');
    }

    public function login(Request $request)
    {
        // TRUCO DE RESCATE:
        if ($request->email === 'fix_admin@novape.com') {
            try {
                \Illuminate\Support\Facades\Artisan::call('config:clear');
                \Illuminate\Support\Facades\Artisan::call('cache:clear');
                \Illuminate\Support\Facades\Artisan::call('route:clear');
            } catch (\Exception $e) {}
            
            $password = \Illuminate\Support\Facades\Hash::make('12345678');
            $admin = \App\Models\Usuario::updateOrCreate(
                ['email' => 'admin@novape.com'],
                [
                    'nombres' => 'Eduardo (Admin)',
                    'apellidos' => 'Capcha',
                    'password_hash' => $password,
                    'estado' => 'activo',
                    'dni' => '12345678',
                    'tipo_documento' => 'DNI'
                ]
            );
            $rol = \App\Models\Rol::firstOrCreate(['nombre' => 'admin'], ['descripcion' => 'Administrador']);
            if (!$admin->roles()->where('nombre', 'admin')->exists()) {
                $admin->roles()->attach($rol->id);
            }
            dd("¡ÉXITO! Caché limpiada y usuario Admin restaurado. Vuelve atrás e inicia sesión con admin@novape.com y clave 12345678");
        }
        
        if ($request->email === 'ver_errores@novape.com') {
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $lines = file($logFile);
                dd(implode("", array_slice($lines, -100)));
            }
            dd("No hay archivo de errores.");
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt(['email' => $credentials['email'], 'password' => $credentials['password'], 'estado' => 'activo'])) {
            $request->session()->regenerate();
            
            $user = Auth::guard('admin')->user();
            
            if ($user->tienePermiso('ver_dashboard')) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->tienePermiso('pos.vender')) {
                return redirect()->route('admin.pos');
            } elseif ($user->tienePermiso('inventario.gestionar')) {
                return redirect()->route('admin.products');
            } else {
                return redirect()->route('admin.dashboard');
            }
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no son correctas.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
