<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Usuario;
use App\Models\Pedido;

try {
    $usuario = Usuario::first();
    echo "User: " . $usuario->nombres . "\n";
    
    $monto = 100.50;
    $codigoPedido = 'TEST-123';
    
    // Simulate PaymentController DB Insert
    DB::beginTransaction();
    $pedido = Pedido::create([
        'usuario_id' => $usuario ? $usuario->id : null,
        'codigo' => $codigoPedido,
        'subtotal' => $monto,
        'descuento' => 0,
        'total' => $monto,
        'estado' => 'Pagado'
    ]);
    echo "Pedido ID: " . $pedido->id . "\n";
    DB::rollBack();
    echo "Success!\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
