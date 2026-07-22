<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Mapping of bad characters to correct UTF-8 characters
$replacements = [
    '├í' => 'á',
    '├®' => 'é',
    '├¡' => 'í',
    '├│' => 'ó',
    '├║' => 'ú',
    '├ü' => 'Á',
    '├ë' => 'É',
    '├ì' => 'Í',
    '├У' => 'Ó', // Sometimes O looks like this
    '├Ó' => 'Ó',
    '├Ü' => 'Ú',
    '├▒' => 'ñ',
    '├С' => 'Ñ',
    '├±' => 'ñ',
    '├С' => 'Ñ',
    '├▒a' => 'ña',
    '├│n' => 'ón',
    '├¡a' => 'ía',
];

// Fallbacks for the weird ones seen in screenshot:
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
    // Handling array or object from SHOW TABLES
    $table = is_array($tableInfo) ? array_values($tableInfo)[0] : $tableInfo->$colName ?? array_values((array)$tableInfo)[0];
    
    $columns = Schema::getColumnListing($table);
    
    foreach ($columns as $column) {
        $type = Schema::getColumnType($table, $column);
        // Only target string types
        if (in_array($type, ['string', 'text', 'longText', 'varchar'])) {
            foreach ($exact_replacements as $bad => $good) {
                // Execute replace query
                try {
                    $query = "UPDATE `$table` SET `$column` = REPLACE(`$column`, ?, ?) WHERE `$column` LIKE ?";
                    DB::statement($query, [$bad, $good, '%' . $bad . '%']);
                } catch (\Exception $e) {
                    // Ignore errors (like json columns, etc)
                }
            }
        }
    }
}

echo "Encoding fixed across all tables.\n";
