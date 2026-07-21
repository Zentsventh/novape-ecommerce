<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$res = \DB::select('SHOW CREATE TABLE producto');
print_r($res);
$res2 = \DB::select('SHOW CREATE TABLE usuario');
print_r($res2);
