<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Categoria;

$subsToCreate = [
    'cómputo' => ['Laptops', 'Computadoras de Escritorio', 'Monitores', 'Componentes PC', 'Almacenamiento', 'Impresoras', 'Accesorios PC', 'Apple Mac'],
    'mundo gamer' => ['Laptops Gamer', 'PCs Gamer', 'Monitores Gamer', 'Sillas Gamer', 'Periféricos Gamer', 'Componentes Gamer'],
    'audio' => ['Audífonos Bluetooth', 'Audífonos con Cable', 'Parlantes Portátiles', 'Barras de Sonido', 'Equipos de Sonido', 'Alta Fidelidad'],
    'tv' => ['Smart TVs', 'OLED y QLED', 'Televisores 4K', 'Soportes y Racks', 'Reproductores Streaming', 'Proyectores'],
    'videojuegos' => ['Consolas PlayStation', 'Consolas Nintendo', 'Consolas Xbox', 'Juegos Físicos', 'Accesorios para Consolas', 'Realidad Virtual'],
    'smartwatches' => ['Apple Watch', 'Galaxy Watch', 'Smartbands', 'Relojes Deportivos', 'Correas y Accesorios'],
];

$rootCategories = Categoria::whereNull('categoria_padre_id')->get();

foreach ($rootCategories as $root) {
    $key = strtolower(trim($root->nombre));
    if (isset($subsToCreate[$key])) {
        foreach ($subsToCreate[$key] as $subName) {
            // Check if sub exists
            $exists = Categoria::where('nombre', $subName)->where('categoria_padre_id', $root->id)->exists();
            if (!$exists) {
                Categoria::create([
                    'nombre' => $subName,
                    'categoria_padre_id' => $root->id,
                    'descripcion' => 'Subcategoría de ' . $root->nombre
                ]);
            }
        }
        echo "Created subs for {$root->nombre}\n";
    }
}
