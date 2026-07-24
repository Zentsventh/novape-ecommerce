<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CuponController extends Controller
{
    public function index()
    {
        $cupones = Cupon::orderBy('id', 'desc')->get();
        return Inertia::render('Admin/Cupones/Index', [
            'cupones' => $cupones
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:cupones,codigo',
            'tipo' => 'required|in:porcentaje,fijo',
            'valor' => 'required|numeric|min:0',
            'monto_minimo' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'limite_usos' => 'nullable|integer|min:1',
            'activo' => 'boolean',
            'unico_por_cliente' => 'boolean',
        ]);

        Cupon::create($validated);

        return redirect()->back()->with('success', 'Cupón creado correctamente.');
    }

    public function update(Request $request, $id) 
    {
        $cupon = Cupon::findOrFail($id);

        $validated = $request->validate([
            'codigo' => 'required|string|unique:cupones,codigo,' . $cupon->id,
            'tipo' => 'required|in:porcentaje,fijo',
            'valor' => 'required|numeric|min:0',
            'monto_minimo' => 'nullable|numeric|min:0',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'limite_usos' => 'nullable|integer|min:1',
            'activo' => 'boolean',
            'unico_por_cliente' => 'boolean',
        ]);

        $cupon->update($validated);

        return redirect()->back()->with('success', 'Cupón actualizado correctamente.');
    }

    public function destroy($id)
    {
        $cupon = Cupon::findOrFail($id);
        $cupon->delete();

        return redirect()->back()->with('success', 'Cupón eliminado correctamente.');
    }
}
