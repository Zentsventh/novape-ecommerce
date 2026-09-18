<?php

declare(strict_types=1);

namespace App\Services\Admin\Roles;

use App\Models\Rol;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class RolePermissionService
{
    public function getRoles(array $filters): LengthAwarePaginator
    {
        $query = Rol::withCount('usuarios');
        
        if (!empty($filters['buscar'])) {
            $buscar = $filters['buscar'];
            $query->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('descripcion', 'like', "%{$buscar}%");
        }

        return $query->paginate(12);
    }

    public function createRole(array $data): Rol
    {
        return DB::transaction(function () use ($data) {
            $rol = Rol::create([
                'nombre' => strtolower($data['nombre']),
                'descripcion' => $data['descripcion'],
            ]);

            if (!empty($data['permisos'])) {
                $rol->permisos()->attach($data['permisos']);
            }

            return $rol;
        });
    }

    public function updateRole(Rol $rol, array $data): Rol
    {
        return DB::transaction(function () use ($rol, $data) {
            $rol->update([
                'nombre' => strtolower($data['nombre']),
                'descripcion' => $data['descripcion'],
            ]);

            $rol->permisos()->sync($data['permisos'] ?? []);

            return $rol;
        });
    }

    public function deleteRole(Rol $rol): void
    {
        if (in_array($rol->nombre, ['admin', 'cajero', 'almacen'])) {
            throw new \Exception('No se pueden eliminar los roles base del sistema.');
        }

        DB::transaction(function () use ($rol) {
            $rol->permisos()->detach();
            $rol->usuarios()->detach();
            $rol->delete();
        });
    }
}
