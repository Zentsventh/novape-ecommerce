<?php

namespace App\Policies;

use App\Models\Direccion;
use App\Models\Usuario;
use Illuminate\Auth\Access\Response;

class DireccionPolicy
{
    public function update(Usuario $user, Direccion $direccion): bool
    {
        return $user->id === $direccion->usuario_id;
    }

    public function delete(Usuario $user, Direccion $direccion): bool
    {
        return $user->id === $direccion->usuario_id;
    }
}
