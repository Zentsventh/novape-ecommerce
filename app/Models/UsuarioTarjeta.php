<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioTarjeta extends Model
{
    use HasFactory;

    protected $table = 'usuario_tarjetas';

    protected $fillable = [
        'usuario_id',
        'ultimos_digitos',
        'marca',
        'principal',
        'token_simulado',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
