<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedido';

    protected $fillable = ['usuario_id', 'codigo', 'subtotal', 'descuento', 'costo_envio', 'total', 'estado', 'tracking_number', 'courier_name', 'tipo_comprobante', 'documento_cliente', 'nombre_facturacion', 'direccion_facturacion', 'direccion_envio_snapshot', 'cupon_id'];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'created_at' => 'datetime',
        'direccion_envio_snapshot' => 'array',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function items()
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }

    public function envio()
    {
        return $this->hasOne(Envio::class, 'pedido_id');
    }

    public function pago()
    {
        return $this->hasOne(Pago::class, 'pedido_id');
    }

    public function comprobante()
    {
        return $this->hasOne(Comprobante::class, 'pedido_id');
    }

    public function cupon()
    {
        return $this->belongsTo(Cupon::class, 'cupon_id');
    }
}
