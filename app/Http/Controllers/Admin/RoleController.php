<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Rol;
use App\Models\Permiso;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $query = Rol::withCount('usuarios');
        
        if ($request->filled('buscar')) {
            $query->where('nombre', 'like', "%{$request->buscar}%")
                  ->orWhere('descripcion', 'like', "%{$request->buscar}%");
        }

        $roles = $query->paginate(12);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            'filtros' => $request->only('buscar')
        ]);
    }

    public function create()
    {
        $permisos = Permiso::all();
        return Inertia::render('Admin/Roles/Create', [
            'permisos' => $permisos
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:rol,nombre',
            'descripcion' => 'required|string|max:255',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:permiso,id'
        ], [
            'nombre.unique' => 'Ya existe un rol con este nombre.'
        ]);

        $rol = Rol::create([
            'nombre' => strtolower($validated['nombre']),
            'descripcion' => $validated['descripcion'],
        ]);

        if (!empty($validated['permisos'])) {
            $rol->permisos()->attach($validated['permisos']);
        }

        return redirect()->route('admin.roles')->with('success', 'Rol creado exitosamente.');
    }

    public function edit($id)
    {
        $rol = Rol::with('permisos')->findOrFail($id);
        $permisos = Permiso::all();

        return Inertia::render('Admin/Roles/Edit', [
            'rol' => $rol,
            'permisos' => $permisos
        ]);
    }

    public function update(Request $request, $id)
    {
        $rol = Rol::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:rol,nombre,' . $id,
            'descripcion' => 'required|string|max:255',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:permiso,id'
        ], [
            'nombre.unique' => 'Ya existe un rol con este nombre.'
        ]);

        $rol->update([
            'nombre' => strtolower($validated['nombre']),
            'descripcion' => $validated['descripcion'],
        ]);

        // Sync actualiza quitando los viejos y agregando los nuevos
        $rol->permisos()->sync($validated['permisos'] ?? []);

        return redirect()->route('admin.roles')->with('success', 'Rol actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $rol = Rol::findOrFail($id);

        // Opcional: Proteger roles core
        if (in_array($rol->nombre, ['admin', 'cajero', 'almacen'])) {
            return redirect()->route('admin.roles')->with('error', 'No se pueden eliminar los roles base del sistema.');
        }

        // Si eliminamos, a los usuarios que tenían este rol se les desasigna automáticamente gracias al cascade o podemos hacerlo manual
        $rol->permisos()->detach();
        $rol->usuarios()->detach();
        $rol->delete();

        return redirect()->route('admin.roles')->with('success', 'Rol eliminado exitosamente.');
    }
}
