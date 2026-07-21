<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class SettingController extends Controller
{
    public function index()
    {
        $configuraciones = ConfiguracionSitio::all()->pluck('valor', 'clave');

        return Inertia::render('Admin/Ajustes/Index', [
            'configuraciones' => $configuraciones
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'logo_url' => 'nullable|url|max:500',
            'nombre_sitio' => 'required|string|max:100',
            'pago_tarjeta' => 'boolean',
            'pago_transferencia' => 'boolean',
            'envio_gratis' => 'boolean',
            'igv_porcentaje' => 'required|numeric|min:0|max:100',
        ]);

        if (!empty($validated['logo_url'])) {
            ConfiguracionSitio::establecer('logo_url', $validated['logo_url']);
        }
        ConfiguracionSitio::establecer('nombre_sitio', $validated['nombre_sitio']);
        ConfiguracionSitio::establecer('pago_tarjeta', isset($validated['pago_tarjeta']) && $validated['pago_tarjeta'] ? '1' : '0');
        ConfiguracionSitio::establecer('pago_transferencia', isset($validated['pago_transferencia']) && $validated['pago_transferencia'] ? '1' : '0');
        ConfiguracionSitio::establecer('envio_gratis', isset($validated['envio_gratis']) && $validated['envio_gratis'] ? '1' : '0');
        ConfiguracionSitio::establecer('igv_porcentaje', $validated['igv_porcentaje']);

        return redirect()->route('admin.ajustes')->with('success', 'Configuración actualizada exitosamente.');
    }

    public function rolesIndex()
    {
        $roles = \App\Models\Rol::with('permisos')->get();
        $permisos = \App\Models\Permiso::all();

        return Inertia::render('Admin/Ajustes/Permisos', [
            'roles' => $roles,
            'permisos' => $permisos
        ]);
    }

    public function rolesSyncPermisos(Request $request)
    {
        $validated = $request->validate([
            'rol_id' => 'required|exists:rol,id',
            'permisos' => 'array',
            'permisos.*' => 'exists:permiso,id'
        ]);

        $rol = \App\Models\Rol::findOrFail($validated['rol_id']);
        
        if ($rol->nombre === 'admin') {
            $permisosRequeridos = \App\Models\Permiso::pluck('id')->toArray();
            $rol->permisos()->sync($permisosRequeridos);
        } else {
            $rol->permisos()->sync($validated['permisos'] ?? []);
        }

        \App\Models\ActividadLog::log('Actualizó permisos de un rol', 'rol', $rol->id, ['rol' => $rol->nombre, 'permisos' => $validated['permisos'] ?? []]);

        return redirect()->back()->with('success', 'Permisos actualizados correctamente.');
    }

    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:50|unique:rol,nombre',
            'descripcion' => 'nullable|string|max:255'
        ]);

        $rol = \App\Models\Rol::create([
            'nombre' => strtolower($validated['nombre']),
            'descripcion' => $validated['descripcion']
        ]);

        \App\Models\ActividadLog::log('Creó un nuevo rol', 'rol', $rol->id, $rol->toArray());

        return redirect()->back()->with('success', 'Rol creado exitosamente.');
    }

    public function destroyRole($id)
    {
        $rol = \App\Models\Rol::findOrFail($id);

        if ($rol->nombre === 'admin') {
            return redirect()->back()->withErrors(['error' => 'No puedes eliminar el rol de Administrador.']);
        }

        // Optional: Check if users have this role before deleting, or just detach
        $rol->permisos()->detach();
        $rol->delete();

        \App\Models\ActividadLog::log('Eliminó un rol', 'rol', $id);

        return redirect()->back()->with('success', 'Rol eliminado exitosamente.');
    }
}
