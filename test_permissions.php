<?php
$u = App\Models\Usuario::find(11);
echo json_encode([
    'email' => $u->email,
    'esAdmin' => $u->esAdmin(),
    'tienePermiso' => $u->tienePermiso('ver_dashboard'),
    'roles' => $u->roles->pluck('nombre'),
    'permisos' => $u->getAllPermisos()
]);
