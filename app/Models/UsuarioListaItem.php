<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsuarioListaItem extends Model
{
    use HasFactory;

    protected $table = 'usuario_lista_items';

    protected $fillable = [
        'lista_id',
        'producto_id',
    ];

    public function lista()
    {
        return $this->belongsTo(UsuarioLista::class, 'lista_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
