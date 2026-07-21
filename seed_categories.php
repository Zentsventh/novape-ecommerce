<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c1 = \App\Models\Categoria::firstOrCreate(['nombre' => 'Cyber Bombas', 'slug' => 'cyber-bombas', 'activa' => 1]);
$c2 = \App\Models\Categoria::firstOrCreate(['nombre' => 'Retiro Inmediato', 'slug' => 'retiro-inmediato', 'activa' => 1]);

$p1 = \App\Models\Producto::inRandomOrder()->take(20)->get();
$p2 = \App\Models\Producto::inRandomOrder()->take(20)->get();

foreach($p1 as $p) {
    $p->categorias()->syncWithoutDetaching([$c1->id]);
}

foreach($p2 as $p) {
    $p->categorias()->syncWithoutDetaching([$c2->id]);
}

echo "Seeded categories and products.\n";
