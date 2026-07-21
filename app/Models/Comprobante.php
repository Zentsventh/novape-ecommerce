<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comprobante extends Model
{
    protected $table = 'comprobantes';

    protected $fillable = [
        'pedido_id',
        'tipo',
        'serie',
        'numero',
        'codigo_ticket',
        'ruta_pdf',
        'ruta_xml',
        'estado_sunat',
        'hash_cdr',
        'total',
        'igv',
        'operaciones_gravadas',
        'cliente_nombre',
        'cliente_documento',
        'cliente_tipo_documento',
        'emitido_at',
    ];

    protected $casts = [
        'emitido_at' => 'datetime',
        'total' => 'decimal:2',
        'igv' => 'decimal:2',
        'operaciones_gravadas' => 'decimal:2',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }
}
