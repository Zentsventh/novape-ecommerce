<?php

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$adminRol = Rol::firstOrCreate(['nombre' => 'admin'], ['descripcion' => 'Administrador']);
$clienteRol = Rol::firstOrCreate(['nombre' => 'cliente'], ['descripcion' => 'Cliente']);

$user = Usuario::where('email', 'admin@novape.com')->first();
if (!$user) {
    $user = Usuario::create([
        'nombres' => 'Admin',
        'apellidos' => 'Novape',
        'email' => 'admin@novape.com',
        'password_hash' => Hash::make('password'),
        'estado' => 'activo'
    ]);
    $user->roles()->attach($adminRol->id);
    echo "Admin user created: admin@novape.com / password\n";
} else {
    // Force update password to be sure it's bcrypt
    $user->update(['password_hash' => Hash::make('password')]);
    if (!$user->esAdmin()) {
        $user->roles()->attach($adminRol->id);
    }
    echo "Admin user exists and password reset to 'password': " . $user->email . "\n";
}
