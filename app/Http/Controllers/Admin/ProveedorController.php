<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $sort = $request->query('sort', 'id');
        $direction = $request->query('direction', 'desc');

        $query = Proveedor::query();

        if (!empty($search)) {
            $query->where('nombre', 'like', "%{$search}%")
                  ->orWhere('ruc', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        $proveedores = $query->orderBy($sort, $direction)->paginate(10)->withQueryString();

        return Inertia::render('Admin/Proveedores/Index', [
            'proveedores' => $proveedores,
            'filters' => (object) $request->only(['search', 'sort', 'direction'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Proveedores/Form', [
            'proveedor' => null
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'ruc' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'contacto' => 'nullable|string|max:150',
            'activo' => 'boolean',
        ]);

        Proveedor::create($validated);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor creado exitosamente.');
    }

    public function edit(string $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        return Inertia::render('Admin/Proveedores/Form', [
            'proveedor' => $proveedor
        ]);
    }

    public function update(Request $request, string $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'ruc' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:150',
            'contacto' => 'nullable|string|max:150',
            'activo' => 'boolean',
        ]);

        $proveedor->update($validated);

        return redirect()->route('proveedores.index')->with('success', 'Proveedor actualizado exitosamente.');
    }

    public function destroy(string $id)
    {
        $proveedor = Proveedor::findOrFail($id);
        // Soft delete? No, Proveedor doesn't have soft deletes by default in our migration. We just delete it.
        // Wait, if it has products, we might not want to delete it.
        // The foreign key is set null on delete, so it's safe to delete.
        $proveedor->delete();

        return redirect()->route('proveedores.index')->with('success', 'Proveedor eliminado exitosamente.');
    }
}
