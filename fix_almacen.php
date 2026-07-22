<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

DB::table('almacenes')->where('id', 1)->update(['nombre' => 'Almacén Central Lima']);
DB::table('almacenes')->where('id', 2)->update(['nombre' => 'Almacén Tienda Miraflores']);
DB::table('almacenes')->where('id', 3)->update(['nombre' => 'Almacén Tienda San Isidro']);
echo "Names updated.\n";

print_r(Schema::getColumnListing('movimientos_almacen'));
