<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DireccionUsuario extends Model
{
    protected $table = 'direccion_usuario';

    protected $fillable = [
        'usuario_id',
        'direccion',
        'referencia',
        'departamento',
        'provincia',
        'distrito',
        'codigo_postal',
        'principal'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
