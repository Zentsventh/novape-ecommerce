<?php
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Categoria;

define('LARAVEL_START', microtime(true));
// Register the Composer autoloader...
if (file_exists(__DIR__.'/../novape/vendor/autoload.php')) {
    require __DIR__.'/../novape/vendor/autoload.php'; // cPanel
} else {
    require __DIR__.'/../vendor/autoload.php'; // Local
}

if (file_exists(__DIR__.'/../novape/bootstrap/app.php')) {
    $app = require_once __DIR__.'/../novape/bootstrap/app.php'; // cPanel
    $app->usePublicPath(__DIR__);
} else {
    $app = require_once __DIR__.'/../bootstrap/app.php'; // Local
}

$kernel = $app->make(Kernel::class);
$response = $kernel->handle(
    $request = Request::capture()
);

$messages = [];

try {
    // 1. Limpiar Caché
    Artisan::call('optimize:clear');
    $messages[] = "✅ Caché y configuraciones limpiadas con éxito.";

    // 2. Corregir Codificación BD
    $exact_replacements = [
        'Comisi├│n' => 'Comisión',
        'Campa├▒a' => 'Campaña',
        'Asesor├¡a' => 'Asesoría',
        'Almac├®n' => 'Almacén',
        '├│' => 'ó',
        '├▒' => 'ñ',
        '├¡' => 'í',
        '├®' => 'é',
        '├í' => 'á',
        '├║' => 'ú'
    ];
    $tables = DB::select('SHOW TABLES');
    $dbName = DB::connection()->getDatabaseName();
    $colName = "Tables_in_" . $dbName;

    foreach ($tables as $tableInfo) {
        $table = is_array($tableInfo) ? array_values($tableInfo)[0] : $tableInfo->$colName ?? array_values((array)$tableInfo)[0];
        $columns = Schema::getColumnListing($table);
        foreach ($columns as $column) {
            $type = Schema::getColumnType($table, $column);
            if (in_array($type, ['string', 'text', 'longText', 'varchar'])) {
                foreach ($exact_replacements as $bad => $good) {
                    try {
                        $query = "UPDATE `$table` SET `$column` = REPLACE(`$column`, ?, ?) WHERE `$column` LIKE ?";
                        DB::statement($query, [$bad, $good, '%' . $bad . '%']);
                    } catch (\Exception $e) {}
                }
            }
        }
    }
    $messages[] = "✅ Codificación de Base de Datos corregida (Letras extrañas eliminadas).";

    // 3. Sembrar Subcategorías
    $subsToCreate = [
        'cómputo' => ['Laptops', 'Computadoras de Escritorio', 'Monitores', 'Componentes PC', 'Almacenamiento', 'Impresoras', 'Accesorios PC', 'Apple Mac'],
        'mundo gamer' => ['Laptops Gamer', 'PCs Gamer', 'Monitores Gamer', 'Sillas Gamer', 'Periféricos Gamer', 'Componentes Gamer'],
        'audio' => ['Audífonos Bluetooth', 'Audífonos con Cable', 'Parlantes Portátiles', 'Barras de Sonido', 'Equipos de Sonido', 'Alta Fidelidad'],
        'tv' => ['Smart TVs', 'OLED y QLED', 'Televisores 4K', 'Soportes y Racks', 'Reproductores Streaming', 'Proyectores'],
        'videojuegos' => ['Consolas PlayStation', 'Consolas Nintendo', 'Consolas Xbox', 'Juegos Físicos', 'Accesorios para Consolas', 'Realidad Virtual'],
        'smartwatches' => ['Apple Watch', 'Galaxy Watch', 'Smartbands', 'Relojes Deportivos', 'Correas y Accesorios'],
    ];

    $rootCategories = Categoria::whereNull('categoria_padre_id')->get();
    foreach ($rootCategories as $root) {
        $key = strtolower(trim($root->nombre));
        if (isset($subsToCreate[$key])) {
            foreach ($subsToCreate[$key] as $subName) {
                $exists = Categoria::where('nombre', $subName)->where('categoria_padre_id', $root->id)->exists();
                if (!$exists) {
                    Categoria::create(['nombre' => $subName, 'categoria_padre_id' => $root->id, 'descripcion' => 'Subcategoría de ' . $root->nombre]);
                }
            }
        }
    }
    $messages[] = "✅ Subcategorías inyectadas exitosamente.";

} catch (\Exception $e) {
    $messages[] = "❌ Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Despliegue a Producción</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); width: 100%; max-width: 600px; }
        h1 { color: #0f172a; margin-top: 0; }
        .msg { padding: 15px; border-radius: 8px; margin-bottom: 10px; background: #f8fafc; border-left: 4px solid #00B4FF; font-weight: 500; }
        .alert { background: #fff1f2; border-left: 4px solid #f43f5e; color: #9f1239; padding: 15px; border-radius: 8px; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🚀 Despliegue Completado</h1>
        <?php foreach($messages as $msg): ?>
            <div class="msg"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
        <div class="alert">
            ¡Importante! Por razones de seguridad, elimina este archivo (public/deploy_cpanel.php) de tu servidor inmediatamente.
        </div>
        <a href="/" style="display:inline-block; margin-top:20px; padding: 10px 20px; background: #00B4FF; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">Ir a la tienda</a>
    </div>
</body>
</html>
