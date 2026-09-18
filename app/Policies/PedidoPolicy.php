<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\Usuario;
use Illuminate\Auth\Access\Response;

class PedidoPolicy
{
    public function view(Usuario $user, Pedido $pedido): bool
    {
        return $user->id === $pedido->usuario_id;
    }
}
