<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\User;

class DireccionService
{
    /**
     * @param array<string, mixed> $data
     */
    public function store(User $usuario, array $data): void
    {
        $isPrincipal = filter_var($data['principal'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($isPrincipal) {
            $usuario->direcciones()->update(['principal' => false]);
        }

        $usuario->direcciones()->create($data);
    }

    public function setPrincipal(User $usuario, int $id): void
    {
        $usuario->direcciones()->update(['principal' => false]);
        
        $direccion = $usuario->direcciones()->findOrFail($id);
        $direccion->principal = true;
        $direccion->save();
    }

    public function destroy(User $usuario, int $id): void
    {
        $direccion = $usuario->direcciones()->findOrFail($id);
        $direccion->delete();
    }
}
