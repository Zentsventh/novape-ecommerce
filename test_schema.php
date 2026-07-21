<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$result = Illuminate\Support\Facades\DB::select('SHOW CREATE TABLE usuario');
echo $result[0]->{'Create Table'} . "\n";
