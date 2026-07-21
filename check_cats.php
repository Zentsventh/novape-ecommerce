<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$cats = ['Mundo Gamer', 'Audio', 'Cámaras y Drones', 'Smartwatches', 'Smarthome y domótica'];

foreach ($cats as $cat) {
    $c = App\Models\Categoria::where('nombre', $cat)->first();
    if ($c) {
        $count = App\Models\Producto::whereHas('categorias', function($q) use ($cat) {
            $q->where('nombre', $cat);
        })->count();
        echo "Categoría '{$cat}' (ID {$c->id}) tiene {$count} productos.\n";
    } else {
        echo "Categoría '{$cat}' no encontrada.\n";
    }
}
