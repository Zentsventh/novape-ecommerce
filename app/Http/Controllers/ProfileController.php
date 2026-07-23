<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use App\Mail\VerificarCelularMail;
use App\Http\Requests\UpdateProfileRequest;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $usuario = Auth::user();

        $pedidos = $usuario->pedidos()->with(['items.variante.producto.imagenes', 'items.variante.producto.proveedor'])->orderBy('id', 'desc')->get();
        $direcciones = $usuario->direcciones()->get();
        $tarjetas = $usuario->tarjetas()->get();
        $datosReembolso = $usuario->datosReembolso()->first();
        $listas = $usuario->listas()->with('items.producto.imagenes')->get();
        $sesiones = \DB::table('sessions')->where('user_id', $usuario->id)->orderBy('last_activity', 'desc')->get();
        $tab = $request->query('tab', 'home');

        $categoriaProductos = \Illuminate\Support\Facades\Cache::remember('home_categorias', 3600, function () {
            return \App\Models\Categoria::whereNull('categoria_padre_id')
                ->where(function ($q) {
                    $q->whereNotIn('slug', ['cyber-bombas', 'retiro-inmediato'])->orWhereNull('slug');
                })
                ->with(['subcategorias', 'productos' => function ($query) {
                    $query->where('producto.activo', 1)
                          ->with(['marca', 'variantes', 'imagenes']);
                }])
                ->get();
        });

        return Inertia::render('Auth/Profile', [
            'usuario' => $usuario,
            'pedidos' => $pedidos,
            'direcciones' => $direcciones,
            'tarjetas' => $tarjetas,
            'datosReembolso' => $datosReembolso,
            'listas' => $listas,
            'sesiones' => $sesiones,
            'activeTabParam' => $tab,
            'categoriaProductos' => $categoriaProductos,
        ]);
    }

    public function showOrder($codigo)
    {
        $usuario = Auth::user();

        $pedido = \App\Models\Pedido::with(['items.variante.producto.imagenes', 'items.variante.producto.proveedor', 'usuario'])
            ->where('codigo', $codigo)
            ->where('usuario_id', $usuario->id)
            ->firstOrFail();

        return Inertia::render('Auth/OrderDetails', [
            'pedido' => $pedido,
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $usuario = Auth::user();
        
        $usuario->update([
            'nombres' => $request->nombres,
            'apellidos' => $request->apellidos,
            'dni' => $request->dni,
            // El celular se actualiza por OTP, no por este método general
        ]);

        return back()->with('success', 'Perfil actualizado exitosamente.');
    }

    public function requestPhoneUpdateOtp(Request $request)
    {
        $request->validate([
            'telefono' => 'required|string|min:9|max:15'
        ]);

        $usuario = Auth::user();
        
        // Generar código de 6 dígitos
        $codigo = (string) rand(100000, 999999);
        
        // Guardar en sesión por 10 minutos
        Session::put('phone_update_otp', $codigo);
        Session::put('phone_update_new_number', $request->telefono);
        Session::put('phone_update_expires_at', now()->addMinutes(10));

        // Enviar correo
        Mail::to($usuario->email)->send(new VerificarCelularMail($usuario, $codigo));

        return back()->with('success', 'Código enviado a tu correo.');
    }

    public function verifyPhoneUpdateOtp(Request $request)
    {
        $request->validate([
            'codigo' => 'required|string|size:6'
        ]);

        $codigoGuardado = Session::get('phone_update_otp');
        $expiraEn = Session::get('phone_update_expires_at');
        $nuevoCelular = Session::get('phone_update_new_number');

        if (!$codigoGuardado || !$expiraEn || now()->greaterThan($expiraEn)) {
            return back()->withErrors(['codigo' => 'El código ha expirado o no es válido. Solicita uno nuevo.']);
        }

        if ($request->codigo !== $codigoGuardado) {
            return back()->withErrors(['codigo' => 'El código ingresado es incorrecto.']);
        }

        // Todo correcto, actualizar celular
        $usuario = Auth::user();
        $usuario->update(['telefono' => $nuevoCelular]);

        // Limpiar sesión
        Session::forget(['phone_update_otp', 'phone_update_new_number', 'phone_update_expires_at']);

        return back()->with('success', 'Celular actualizado exitosamente.');
    }

    public function updatePassword(Request $request)
    {
        $usuario = Auth::user();

        $rules = [
            'password' => [
                'required',
                'min:8',
                'regex:/[a-z]/',      // Al menos una minúscula
                'regex:/[A-Z]/',      // Al menos una mayúscula
                'regex:/[0-9]/',      // Al menos un número
                'regex:/[@$!%*#?&]/', // Al menos un símbolo
                'confirmed'
            ],
        ];

        if ($usuario->has_set_password) {
            $rules['current_password'] = 'required';
        }

        $request->validate($rules, [
            'password.regex' => 'La contraseña debe contener al menos una mayúscula, una minúscula, un número y un símbolo especial (@$!%*#?&).'
        ]);

        if ($usuario->has_set_password && ! \Hash::check($request->current_password, $usuario->password_hash)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $usuario->update([
            'password_hash' => bcrypt($request->password),
            'has_set_password' => true,
        ]);

        return back()->with('success', 'Tu contraseña se ha actualizado correctamente.');
    }

    public function storeDireccion(Request $request)
    {
        $usuario = Auth::user();

        $request->validate([
            'direccion' => 'required',
            'referencia' => 'nullable',
            'departamento' => 'required',
            'provincia' => 'required',
            'distrito' => 'required',
            'codigo_postal' => 'nullable',
            'principal' => 'boolean|nullable',
        ]);

        $data = $request->all();
        $isPrincipal = $request->input('principal', false);

        if ($isPrincipal) {
            $usuario->direcciones()->update(['principal' => false]);
        }

        $usuario->direcciones()->create($data);

        return back()->with('success', 'Dirección agregada correctamente.');
    }

    public function setPrincipalDireccion($id)
    {
        $usuario = Auth::user();
        
        $usuario->direcciones()->update(['principal' => false]);
        
        $direccion = $usuario->direcciones()->findOrFail($id);
        $direccion->principal = true;
        $direccion->save();

        return back()->with('success', 'Dirección establecida como principal.');
    }

    public function destroyDireccion($id)
    {
        $usuario = Auth::user();
        $direccion = $usuario->direcciones()->findOrFail($id);
        $direccion->delete();

        return back()->with('success', 'Dirección eliminada.');
    }

    public function storeTarjeta(Request $request)
    {
        $usuario = Auth::user();
        $request->validate([
            'numero_tarjeta' => 'required|string|size:16',
            'fecha_vencimiento' => 'required|string',
            'cvv' => 'required|string',
            'nombre_titular' => 'required|string'
        ]);

        // Simulación: solo guardamos los últimos 4 dígitos y marca
        $ultimos = substr($request->numero_tarjeta, -4);
        // Lógica simple para marca
        $marca = str_starts_with($request->numero_tarjeta, '4') ? 'Visa' : (str_starts_with($request->numero_tarjeta, '5') ? 'Mastercard' : 'Amex');
        
        $usuario->tarjetas()->create([
            'ultimos_digitos' => $ultimos,
            'marca' => $marca,
            'principal' => $usuario->tarjetas()->count() === 0,
            'token_simulado' => 'tok_' . uniqid(),
        ]);

        return back()->with('success', 'Tarjeta agregada exitosamente (Simulación).');
    }

    public function destroyTarjeta($id)
    {
        $usuario = Auth::user();
        $tarjeta = $usuario->tarjetas()->findOrFail($id);
        $tarjeta->delete();

        return back()->with('success', 'Tarjeta eliminada.');
    }

    public function updateDatosReembolso(Request $request)
    {
        $usuario = Auth::user();
        $validated = $request->validate([
            'tipo_documento' => 'required|string',
            'numero_documento' => 'required|string',
            'nombres_titular' => 'required|string',
            'apellidos_titular' => 'required|string',
            'telefono_titular' => 'required|string',
            'correo_titular' => 'required|email',
            'banco' => 'required|string',
            'tipo_cuenta' => 'required|string',
            'numero_cuenta' => 'required|string',
            'cci' => 'required|string',
        ]);

        $datos = $usuario->datosReembolso()->first();
        if ($datos) {
            $datos->update($validated);
        } else {
            $usuario->datosReembolso()->create($validated);
        }

        return back()->with('success', 'Datos de reembolso actualizados.');
    }

    public function destroySession($id)
    {
        $usuario = Auth::user();
        \DB::table('sessions')->where('id', $id)->where('user_id', $usuario->id)->delete();
        return back()->with('success', 'Sesión cerrada exitosamente.');
    }

    public function destroyAccount(Request $request)
    {
        $usuario = Auth::user();
        $request->validate([
            'password' => 'required'
        ]);

        if (! \Hash::check($request->password, $usuario->password_hash)) {
            return back()->withErrors(['password' => 'La contraseña no es correcta.']);
        }

        Auth::logout();
        $usuario->delete(); // Soft delete

        return redirect('/')->with('success', 'Tu cuenta ha sido eliminada.');
    }
}
