<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoImagen extends Model
{
    protected $table = 'producto_imagen';
    public $timestamps = false;

    protected $fillable = ['producto_id', 'url', 'orden'];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function getUrlAttribute($value)
    {
        if (empty($value)) return $value;
        if (str_starts_with($value, 'http')) return $value;
        if (str_starts_with($value, '/storage/')) return $value;
        return '/storage/' . $value;
    }
}
