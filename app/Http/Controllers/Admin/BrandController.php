<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Marca;

class BrandController extends Controller
{
    public function index()
    {
        $marcas = Marca::withCount('productos')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Marcas/Index', [
            'marcas' => $marcas
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Marcas/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:marca,nombre',
        ]);

        Marca::create($validated);

        return redirect()->route('admin.marcas')->with('success', 'Marca creada exitosamente.');
    }

    public function edit($id)
    {
        $marca = Marca::findOrFail($id);
        return Inertia::render('Admin/Marcas/Form', [
            'marca' => $marca
        ]);
    }

    public function update(Request $request, $id)
    {
        $marca = Marca::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:100|unique:marca,nombre,' . $id,
        ]);

        $marca->update($validated);

        return redirect()->route('admin.marcas')->with('success', 'Marca actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $marca = Marca::findOrFail($id);
        $marca->delete();
        return redirect()->route('admin.marcas')->with('success', 'Marca eliminada exitosamente.');
    }
}
