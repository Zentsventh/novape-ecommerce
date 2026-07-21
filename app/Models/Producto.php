<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;
    protected $table = 'producto';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'marca_id',
        'proveedor_id',
        'activo',
        'garantias',
        'sku_base'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($producto) {
            if (empty($producto->slug)) {
                $baseSlug = \Illuminate\Support\Str::slug($producto->nombre);
                $slug = $baseSlug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $producto->id)->exists()) {
                    $slug = $baseSlug . '-' . $count;
                    $count++;
                }
                $producto->slug = $slug;
            }
        });

        $clearCache = function () {
            \Illuminate\Support\Facades\Cache::forget('home_categorias');
            \Illuminate\Support\Facades\Cache::forget('home_mejor_semana');
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }

    public function marca()
    {
        return $this->belongsTo(Marca::class, 'marca_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }



    public function categorias()
    {
        return $this->belongsToMany(Categoria::class, 'producto_categoria', 'producto_id', 'categoria_id');
    }

    public function variantes()
    {
        return $this->hasMany(Variante::class, 'producto_id');
    }

    public function imagenes()
    {
        return $this->hasMany(ProductoImagen::class, 'producto_id')->orderBy('orden');
    }

    public function productoEspecificaciones()
    {
        return $this->hasMany(ProductoEspecificacion::class, 'producto_id');
    }

}
