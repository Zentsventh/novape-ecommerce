<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$id = 1;
$movimientos = DB::table('movimientos_almacen')
    ->join('variante', 'movimientos_almacen.variante_id', '=', 'variante.id')
    ->join('producto', 'variante.producto_id', '=', 'producto.id')
    ->leftJoin('almacenes as destino', 'movimientos_almacen.almacen_destino_id', '=', 'destino.id')
    ->select(
        'movimientos_almacen.*',
        'producto.nombre as producto_nombre',
        'variante.sku',
        'destino.nombre as destino_nombre'
    )
    ->where('movimientos_almacen.almacen_id', $id)
    ->orderBy('movimientos_almacen.created_at', 'desc')
    ->paginate(20);

echo json_encode($movimientos);
