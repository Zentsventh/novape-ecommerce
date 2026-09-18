<?php

declare(strict_types=1);

namespace App\Services\Admin\Users;

use App\Models\Usuario;
use App\Models\ActividadLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class UserManagementService
{
    public function getCustomers(array $filters): LengthAwarePaginator
    {
        $query = Usuario::query()->with('roles');

        if (!empty($filters['buscar'])) {
            $buscar = $filters['buscar'];
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%")
                  ->orWhere('dni', 'like', "%{$buscar}%");
            });
        }

        $query->where(function ($q) {
            $q->whereHas('roles', function ($q2) {
                $q2->where('nombre', 'cliente');
            })->orWhereDoesntHave('roles');
        });

        return $query->withCount('pedidos')->orderBy('id', 'desc')->paginate(12);
    }

    public function getStaff(array $filters): LengthAwarePaginator
    {
        $query = Usuario::query()->with('roles');

        if (!empty($filters['buscar'])) {
            $buscar = $filters['buscar'];
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%")
                  ->orWhere('dni', 'like', "%{$buscar}%");
            });
        }

        $query->whereHas('roles', function ($q) {
            $q->where('nombre', '!=', 'cliente');
        });

        return $query->withCount('pedidos')->orderBy('id', 'desc')->paginate(12);
    }

    public function createUser(array $data, bool $isStaff = false): Usuario
    {
        return DB::transaction(function () use ($data, $isStaff) {
            $usuario = Usuario::create([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'dni' => $data['dni'] ?? null,
                'telefono' => $data['telefono'] ?? null,
                'estado' => 'activo'
            ]);

            if ($isStaff && !empty($data['roles'])) {
                $usuario->roles()->attach($data['roles']);
            }

            ActividadLog::log('Creó un nuevo ' . ($isStaff ? 'trabajador' : 'cliente'), 'usuario', $usuario->id, $usuario->toArray());

            return $usuario;
        });
    }

    public function updateUser(Usuario $usuario, array $data, bool $isStaff = false): Usuario
    {
        return DB::transaction(function () use ($usuario, $data, $isStaff) {
            $dataToUpdate = [
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'email' => $data['email'],
                'dni' => $data['dni'] ?? null,
                'telefono' => $data['telefono'] ?? null,
            ];

            if (!empty($data['password'])) {
                $dataToUpdate['password_hash'] = Hash::make($data['password']);
            }

            $usuario->update($dataToUpdate);

            if ($isStaff && isset($data['roles'])) {
                $usuario->roles()->sync($data['roles']);
            }

            ActividadLog::log('Actualizó un usuario', 'usuario', $usuario->id, $usuario->toArray());

            return $usuario;
        });
    }

    public function deleteUser(Usuario $usuario, int $currentUserId): void
    {
        if ($usuario->id === $currentUserId) {
            throw new \Exception('No puedes eliminar tu propia cuenta.');
        }

        $usuario->delete();
        ActividadLog::log('Eliminó un usuario (Soft Delete)', 'usuario', $usuario->id);
    }

    public function toggleBlockStatus(Usuario $usuario, int $currentUserId): void
    {
        if ($usuario->id === $currentUserId) {
            throw new \Exception('No puedes bloquear tu propia cuenta.');
        }

        $usuario->estado = $usuario->estado === 'bloqueado' ? 'activo' : 'bloqueado';
        $usuario->save();

        $accion = $usuario->estado === 'bloqueado' ? 'bloqueada' : 'desbloqueada';
        ActividadLog::log("Cuenta de usuario $accion", 'usuario', $usuario->id);
    }

    public function resetPassword(Usuario $usuario): string
    {
        $newPassword = 'Novape' . date('Y') . '!';
        $usuario->password_hash = Hash::make($newPassword);
        $usuario->save();

        ActividadLog::log('Restableció contraseña de usuario', 'usuario', $usuario->id);

        return $newPassword;
    }
}
