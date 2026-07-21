<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$controller = app()->make(\App\Http\Controllers\HomeController::class);
$response = $controller->index();
$props = $response->toResponse(request())->getData();

$data = json_decode(json_encode($props), true);
if (isset($data['page']['props']['categoriaProductos'])) {
    foreach ($data['page']['props']['categoriaProductos'] as $cat) {
        if ($cat['nombre'] == 'Mundo Gamer') {
            echo "Mundo Gamer productos count: " . count($cat['productos']) . "\n";
            if (count($cat['productos']) > 0) {
                echo "Primer producto: " . $cat['productos'][0]['nombre'] . "\n";
            }
        }
    }
} else {
    echo "No categoriaProductos found\n";
}
