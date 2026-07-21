<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$imagenes = App\Models\ProductoImagen::take(5)->get();
foreach($imagenes as $pi) {
    echo $pi->url . "\n";
}
