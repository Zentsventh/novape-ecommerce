<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $usuario = App\Models\Usuario::first();
    if (!$usuario) {
        echo "No users found\n";
        exit;
    }
    echo "User: " . $usuario->nombres . "\n";
    $pedidos = $usuario->pedidos()->orderBy('id', 'desc')->get();
    echo "Pedidos fetched successfully: " . count($pedidos) . "\n";
} catch (\Throwable $e) {
    echo "Error fetching pedidos: " . $e->getMessage() . "\n";
}
