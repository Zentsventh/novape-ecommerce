<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$controller = new App\Http\Controllers\Admin\CustomerController();
$controller->resetPassword(15);
echo "Reset OK\n";

$controller->toggleBloqueo(15);
echo "Toggle OK\n";

$controller->destroy(15);
echo "Destroy OK\n";
