<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$u = App\Models\Usuario::first();
echo "Initial state: " . $u->estado . "\n";
$u->estado = 'bloqueado';
echo "Is dirty? " . ($u->isDirty() ? 'Yes' : 'No') . "\n";
var_dump($u->getDirty());
$u->save();
echo "After save state: " . $u->estado . "\n";
$u->refresh();
echo "After refresh state: " . $u->estado . "\n";

echo "Delete test:\n";
try {
    $u->delete();
    echo "Trashed? " . ($u->trashed() ? 'Yes' : 'No') . "\n";
    $u->restore();
} catch (\Exception $e) {
    echo "Delete failed: " . $e->getMessage() . "\n";
}
