<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
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

        return Inertia::render('Auth/Profile', [
            'usuario' => $usuario,
            'pedidos' => $pedidos,
            'direcciones' => $direcciones,
            'tarjetas' => $tarjetas,
            'datosReembolso' => $datosReembolso,
            'listas' => $listas,
            'sesiones' => $sesiones,
            'activeTabParam' => $tab,
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $usuario = Auth::user();

        $validated = $request->validated();

        $usuario->update([
            'nombres' => $validated['nombres'],
            'apellidos' => $validated['apellidos'],
            'dni' => $validated['dni'],
            'fecha_nacimiento' => $validated['fecha_nacimiento'],
            'telefono' => $validated['telefono'],
        ]);



        return back()->with('success', 'Tus datos se han actualizado correctamente.');
    }

    public function updatePassword(Request $request)
    {
        $usuario = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        if (! \Hash::check($request->current_password, $usuario->password_hash)) {
            return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
        }

        $usuario->update([
            'password_hash' => bcrypt($request->password),
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
