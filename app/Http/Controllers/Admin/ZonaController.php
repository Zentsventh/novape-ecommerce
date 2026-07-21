<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class ZonaController extends Controller
{
    public function index()
    {
        $zonas = DB::table('zonas')->orderBy('id')->get();
        $logoUrl = ConfiguracionSitio::obtener('logo_url');
        return Inertia::render('Admin/Zonas/Index', [
            'zonas' => $zonas,
            'logoUrl' => $logoUrl,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'costo_envio' => 'required|numeric|min:0',
        ]);
        DB::table('zonas')->insert([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'costo_envio' => $request->costo_envio,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Zona creada correctamente.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'costo_envio' => 'required|numeric|min:0',
        ]);
        DB::table('zonas')->where('id', $id)->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'costo_envio' => $request->costo_envio,
            'activo' => $request->activo ?? true,
            'updated_at' => now(),
        ]);
        return redirect()->back()->with('success', 'Zona actualizada.');
    }

    public function destroy($id)
    {
        DB::table('zonas')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Zona eliminada.');
    }
}
