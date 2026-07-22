<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

try {
    $admin = Usuario::where('email', 'admin@novape.com')->first();
    
    if (!$admin) {
        echo "⚠️ El usuario admin@novape.com NO EXISTE en tu base de datos local.\n";
        echo "Creándolo ahora...\n";
        
        $admin = new Usuario();
        $admin->nombres = 'Eduardo';
        $admin->apellidos = 'Capcha';
        $admin->email = 'admin@novape.com';
        $admin->rol = 'admin';
        $admin->estado = 'activo';
        // Valores por defecto requeridos por tu BD
        $admin->dni = '12345678';
        $admin->telefono = '987654321';
        $admin->tipo_documento = 'DNI';
    }
    
    // Forzar siempre la contraseña a 12345678
    $admin->password_hash = Hash::make('12345678');
    $admin->save();
    
    echo "\n✅ ¡Listo! Base de datos actualizada.\n";
    echo "=====================================\n";
    echo " CORREO: admin@novape.com\n";
    echo " CLAVE:  12345678\n";
    echo "=====================================\n";
    
} catch (\Exception $e) {
    echo "❌ Error de conexión a tu base de datos local:\n";
    echo $e->getMessage() . "\n";
}
