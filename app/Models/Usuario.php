<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Usuario extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $table = 'usuario';

    protected $fillable = [
        'nombres', 'apellidos', 'tipo_documento', 'dni', 'email', 'telefono', 'password_hash', 'estado',
        'google_id', 'fecha_nacimiento', 'has_set_password'
    ];

    /**
     * Laravel usa 'password' por defecto para auth.
     * Nuestro campo se llama 'password_hash', lo mapeamos.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getAuthPasswordName()
    {
        return 'password_hash';
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuario_rol', 'usuario_id', 'rol_id');
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class, 'usuario_id');
    }

    public function notas()
    {
        return $this->hasMany(ClienteNota::class, 'cliente_id');
    }

    public function direcciones()
    {
        return $this->hasMany(DireccionUsuario::class, 'usuario_id');
    }

    public function carrito()
    {
        return $this->hasOne(Carrito::class, 'usuario_id');
    }

    public function tarjetas()
    {
        return $this->hasMany(UsuarioTarjeta::class, 'usuario_id');
    }

    public function datosReembolso()
    {
        return $this->hasMany(UsuarioDatosReembolso::class, 'usuario_id');
    }

    public function listas()
    {
        return $this->hasMany(UsuarioLista::class, 'usuario_id');
    }

    public function getNombreCompletoAttribute()
    {
        return $this->nombres . ' ' . $this->apellidos;
    }

    public function esAdmin()
    {
        return $this->roles()->where('nombre', 'admin')->exists();
    }

    public function esCliente()
    {
        return $this->roles()->where('nombre', 'cliente')->exists();
    }

    public function tieneRol($nombreRol)
    {
        return $this->roles()->where('nombre', $nombreRol)->exists();
    }

    /**
     * Devuelve una colección con todos los nombres de los permisos que tiene este usuario.
     */
    public function getAllPermisos()
    {
        return $this->roles()->with('permisos')->get()
            ->pluck('permisos')
            ->flatten()
            ->pluck('nombre')
            ->unique()
            ->values();
    }

    /**
     * Verifica si el usuario tiene un permiso específico o si es admin.
     */
    public function tienePermiso($permiso)
    {
        // Si es admin tiene acceso a todo
        if ($this->esAdmin()) {
            return true;
        }

        return $this->roles()->whereHas('permisos', function ($q) use ($permiso) {
            $q->where('nombre', $permiso);
        })->exists();
    }
}
