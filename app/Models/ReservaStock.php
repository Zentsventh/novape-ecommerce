<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReservaStock extends Model
{
    protected $table = 'reservas_stock';

    protected $fillable = [
        'session_id',
        'variante_id',
        'cantidad',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    public function variante()
    {
        return $this->belongsTo(Variante::class, 'variante_id');
    }
}
