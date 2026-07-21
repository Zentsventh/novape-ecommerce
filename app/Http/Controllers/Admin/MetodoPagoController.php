<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class MetodoPagoController extends Controller
{
    public function index()
    {
        $metodos = DB::table('metodos_pago')->orderBy('id')->get();
        $logoUrl = ConfiguracionSitio::obtener('logo_url');
        return Inertia::render('Admin/MetodosPago/Index', [
            'metodos' => $metodos,
            'logoUrl' => $logoUrl,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:digital,fisico,transferencia',
            'comision_porcentaje' => 'nullable|numeric|min:0',
        ]);
        DB::table('metodos_pago')->insert([
            'nombre' => $request->nombre,
            'detalles' => $request->detalles,
            'tipo' => $request->tipo,
            'comision_porcentaje' => $request->comision_porcentaje ?? 0,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Método de pago creado.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);
        DB::table('metodos_pago')->where('id', $id)->update([
            'nombre' => $request->nombre,
            'detalles' => $request->detalles,
            'tipo' => $request->tipo ?? 'digital',
            'comision_porcentaje' => $request->comision_porcentaje ?? 0,
            'activo' => $request->activo ?? true,
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Método actualizado.');
    }

    public function destroy($id)
    {
        DB::table('metodos_pago')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Método eliminado.');
    }
}
