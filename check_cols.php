<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
try {
    $cols = DB::select('SHOW COLUMNS FROM usuario');
    foreach($cols as $c) {
        echo $c->Field . " - " . $c->Type . "\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
