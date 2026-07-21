<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Variante extends Model
{
    use SoftDeletes;
    protected $table = 'variante';

    protected $fillable = ['producto_id', 'sku', 'precio', 'activo', 'stock', 'stock_reservado'];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
