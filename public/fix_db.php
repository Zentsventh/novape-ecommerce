<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

try {
    DB::table('categoria')->update(['nombre' => DB::raw("REPLACE(nombre, 'C├│mputo', 'Cómputo')")]);
    DB::table('categoria')->update(['nombre' => DB::raw("REPLACE(nombre, 'Electrodom├®sticos', 'Electrodomésticos')")]);
    DB::table('categoria')->update(['nombre' => DB::raw("REPLACE(nombre, 'L├¡nea Blanca', 'Línea Blanca')")]);
    DB::table('categoria')->update(['nombre' => DB::raw("REPLACE(nombre, 'C├ímaras y Drones', 'Cámaras y Drones')")]);
    DB::table('categoria')->update(['nombre' => DB::raw("REPLACE(nombre, 'dom├│tica', 'domótica')")]);

    echo "<h1>✅ Tipografia arreglada con exito en la base de datos!</h1>";
    echo "<p>Ya puedes borrar este archivo (fix_db.php) por seguridad.</p>";
} catch (\Exception $e) {
    echo "<h1>Error:</h1>" . $e->getMessage();
}
