<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\Usuario; // or User, depending on the app's user model
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Check if user exists by email
            $user = Usuario::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                // If the user doesn't exist, create a new one
                // Extract first name and last name if possible
                $nameParts = explode(' ', $googleUser->getName());
                $nombres = array_shift($nameParts);
                $apellidos = implode(' ', $nameParts);

                $user = Usuario::create([
                    'nombres' => $nombres,
                    'apellidos' => $apellidos ?: 'Google User',
                    'email' => $googleUser->getEmail(),
                    'password_hash' => bcrypt(Str::random(24)), // Random password
                    'google_id' => $googleUser->getId(),
                ]);
            } else {
                // Update google_id if not set
                if (!$user->google_id) {
                    $user->update([
                        'google_id' => $googleUser->getId(),
                    ]);
                }
            }

            // Log the user in
            Auth::login($user, true);

            // Sincronizar carrito abandonado
            $request = request();
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
                    $mergedCart[$key] = $item;
                }
                $request->session()->put('cart', $mergedCart);

                $carrito->items()->delete();
                $itemsToInsert = [];
                foreach ($mergedCart as $item) {
                    if (isset($item['variante_id'])) {
                        $itemsToInsert[] = [
                            'carrito_id' => $carrito->id,
                            'variante_id' => $item['variante_id'],
                            'cantidad' => $item['cantidad'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
                if (!empty($itemsToInsert)) {
                    \App\Models\CarritoItem::insert($itemsToInsert);
                }
            }

            // Redirect to home/dashboard
            return redirect()->intended('/cliente/ordenes'); // or wherever appropriate
            
        } catch (\Exception $e) {
            \Log::error('Google OAuth Error: ' . $e->getMessage());
            return redirect('/login')->withErrors(['email' => 'No se pudo iniciar sesión con Google. ' . $e->getMessage()]);
        }
    }
}
