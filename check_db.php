<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
try {
    $tables = DB::select('SHOW TABLES');
    echo "Tables:\n";
    foreach ($tables as $table) {
        $arr = (array)$table;
        echo reset($arr) . "\n";
    }
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
