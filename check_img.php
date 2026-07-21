<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$productos = App\Models\Producto::take(5)->get();
foreach($productos as $p) {
    echo $p->imagen . "\n";
}
