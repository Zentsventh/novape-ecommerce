<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Categoria;

class CategoryController extends Controller
{
    public function index()
    {
        $categorias = Categoria::with('padre')
            ->withCount('productos')
            ->orderBy('id', 'desc')
            ->get();

        return Inertia::render('Admin/Categorias/Index', [
            'categorias' => $categorias
        ]);
    }

    public function create()
    {
        $categoriasPadre = Categoria::whereNull('categoria_padre_id')->get();
        return Inertia::render('Admin/Categorias/Form', [
            'categoriasPadre' => $categoriasPadre
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'categoria_padre_id' => 'nullable|integer|exists:categoria,id',
        ]);

        Categoria::create($validated);

        return redirect()->route('admin.categorias')->with('success', 'Categoría creada exitosamente.');
    }

    public function storeApi(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'categoria_padre_id' => 'nullable|integer|exists:categoria,id',
        ]);

        $categoria = Categoria::create($validated);

        return response()->json([
            'success' => true,
            'categoria' => $categoria
        ]);
    }

    public function edit($id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoriasPadre = Categoria::whereNull('categoria_padre_id')
            ->where('id', '!=', $id)
            ->get();

        return Inertia::render('Admin/Categorias/Form', [
            'categoria' => $categoria,
            'categoriasPadre' => $categoriasPadre
        ]);
    }

    public function update(Request $request, $id)
    {
        $categoria = Categoria::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string|max:255',
            'categoria_padre_id' => 'nullable|integer|exists:categoria,id',
        ]);

        $categoria->update($validated);

        return redirect()->route('admin.categorias')->with('success', 'Categoría actualizada exitosamente.');
    }

    public function destroy($id)
    {
        $categoria = Categoria::findOrFail($id);
        $categoria->delete();
        return redirect()->route('admin.categorias')->with('success', 'Categoría eliminada exitosamente.');
    }
}
