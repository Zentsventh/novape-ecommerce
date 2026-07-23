<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
    {
        // Si el usuario está logueado con el guard web (cliente), redirigir a home
        if (Auth::guard('web')->check()) {
            return redirect('/');
        }
        return Inertia::render('Auth/Login');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'tipo_documento' => 'required|string|in:DNI,CE,PASAPORTE',
            'dni' => 'required|string|max:20|unique:usuario,dni',
            'email' => 'required|email|unique:usuario,email',
            'telefono' => 'nullable|string|max:15',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=(?:.*[A-Z]){2})(?=(?:.*[a-z]){2})(?=(?:.*[0-9]){2})(?=(?:.*[^a-zA-Z0-9\s]){2})(?!.*\s).+$/',
                'confirmed'
            ]
        ]);

        $usuario = \App\Models\Usuario::create([
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'tipo_documento' => $validated['tipo_documento'],
            'dni' => $validated['dni'],
            'email' => $validated['email'],
            'telefono' => $validated['telefono'],
            'password_hash' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'estado' => 'activo'
        ]);

        // Asignar rol de cliente por defecto (ID 2) si existe
        $rolCliente = \App\Models\Rol::where('nombre', 'cliente')->first();
        if ($rolCliente) {
            $usuario->roles()->attach($rolCliente->id);
        }

        Auth::login($usuario);

        return redirect()->route('perfil');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login using the 'password' field mapped to 'password_hash' in Usuario model
        // We pass 'password_hash' in the array because the authentication attempt expects the array keys
        // to match database columns if we don't map it correctly. Actually, Auth::attempt(['email' => $e, 'password' => $p])
        // will check the 'password' field. Since getAuthPassword() returns password_hash, we use 'password' as key here.
        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $user = Auth::user();
            
            if ($user->estado === 'bloqueado') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()->withErrors([
                    'email' => 'Tu cuenta ha sido bloqueada. Contacta con soporte.',
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            // Sincronizar carrito abandonado
            $sessionCart = $request->session()->get('cart', []);
            $carrito = $user->carrito()->firstOrCreate(['session_id' => session()->getId()]);
            $dbItems = $carrito->items()->with(['variante.producto.imagenes'])->get();

            $dbCart = [];
            foreach ($dbItems as $dbItem) {
                $variante = $dbItem->variante;
                $producto = $variante ? $variante->producto : null;
                $imagen = $producto && $producto->imagenes->first() ? $producto->imagenes->first()->url : null;
                if ($producto) {
                    $dbCart[$producto->id] = [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'cantidad' => $dbItem->cantidad,
                        'precio' => (float)$variante->precio,
                        'imagen' => $imagen,
                        'variante_id' => $variante->id
                    ];
                }
            }
            
            if (empty($sessionCart) && !empty($dbCart)) {
                $request->session()->put('cart', $dbCart);
            } elseif (!empty($sessionCart)) {
                $mergedCart = $dbCart;
                foreach ($sessionCart as $key => $item) {
                    if (isset($mergedCart[$key])) {
                        $mergedCart[$key]['cantidad'] += $item['cantidad'];
                    } else {
                        $mergedCart[$key] = $item;
                    }
                }
                $request->session()->put('cart', $mergedCart);

                $variantesActuales = [];
                foreach ($mergedCart as $item) {
                    if (isset($item['variante_id'])) {
                        $variantesActuales[] = $item['variante_id'];
                        \App\Models\CarritoItem::updateOrCreate(
                            ['carrito_id' => $carrito->id, 'variante_id' => $item['variante_id']],
                            ['cantidad' => $item['cantidad']]
                        );
                    }
                }
                
                if (!empty($variantesActuales)) {
                    $carrito->items()->whereNotIn('variante_id', $variantesActuales)->delete();
                } else {
                    $carrito->items()->delete();
                }
            }


            $intendedUrl = session()->pull('url.intended', '/');
            if (\Illuminate\Support\Str::contains($intendedUrl, '/admin')) {
                $intendedUrl = '/';
            }
            return redirect()->to($intendedUrl);
        }

        return back()->withErrors([
            'email' => 'Las credenciales proporcionadas no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
