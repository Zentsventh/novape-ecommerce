<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$user = DB::table('usuario')->where('email', 'admin@novape.com')->first();
echo "Nombres: " . var_export($user->nombres, true) . "\n";
