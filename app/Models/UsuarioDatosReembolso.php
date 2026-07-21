<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioDatosReembolso extends Model
{
    use HasFactory;

    protected $table = 'usuario_datos_reembolso';

    protected $fillable = [
        'usuario_id',
        'tipo_documento',
        'numero_documento',
        'nombres_titular',
        'apellidos_titular',
        'telefono_titular',
        'correo_titular',
        'banco',
        'tipo_cuenta',
        'numero_cuenta',
        'cci',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
