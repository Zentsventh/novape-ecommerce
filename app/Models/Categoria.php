<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Categoria extends Model
{
    use SoftDeletes;
    protected $table = 'categoria';

    protected static function boot()
    {
        parent::boot();

        $clearCache = function () {
            \Illuminate\Support\Facades\Cache::forget('home_categorias');
            \Illuminate\Support\Facades\Cache::forget('home_mejor_semana');
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    protected $fillable = ['nombre', 'descripcion', 'categoria_padre_id'];

    public function padre()
    {
        return $this->belongsTo(Categoria::class, 'categoria_padre_id');
    }

    public function hijos()
    {
        return $this->hasMany(Categoria::class, 'categoria_padre_id');
    }

    public function subcategorias()
    {
        return $this->hasMany(Categoria::class, 'categoria_padre_id');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_categoria', 'categoria_id', 'producto_id');
    }
}
