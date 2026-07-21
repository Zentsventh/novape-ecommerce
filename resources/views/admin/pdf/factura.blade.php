<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura Pedido #{{ $pedido->id }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 14px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #8a2be2; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #8a2be2; font-size: 24px; }
        .header p { margin: 5px 0 0; color: #666; font-size: 12px; }
        .details-table { width: 100%; margin-bottom: 30px; border-collapse: collapse; }
        .details-table td { padding: 5px; vertical-align: top; }
        .details-table h3 { margin: 0 0 10px 0; font-size: 14px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .items-table th { background: #f8f8f8; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
        .items-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .totals { float: right; width: 300px; }
        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 5px 10px; text-align: right; }
        .totals-table .total-row { font-weight: bold; font-size: 18px; color: #8a2be2; border-top: 2px solid #ddd; }
        .footer { clear: both; margin-top: 50px; text-align: center; font-size: 12px; color: #888; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>NOVAPE</h1>
        <p>Factura de Venta / Comprobante</p>
    </div>

    <table class="details-table">
        <tr>
            <td width="50%">
                <h3>Datos del Cliente</h3>
                <strong>{{ $pedido->usuario->nombres }} {{ $pedido->usuario->apellidos }}</strong><br>
                Email: {{ $pedido->usuario->email }}<br>
                Teléfono: {{ $pedido->usuario->telefono ?? 'N/A' }}<br>
                DNI: {{ $pedido->usuario->dni ?? 'N/A' }}
            </td>
            <td width="50%" style="text-align: right;">
                <h3>Detalles del Pedido</h3>
                <strong>Pedido #:</strong> {{ $pedido->codigo }}<br>
                <strong>Fecha:</strong> {{ $pedido->created_at->format('d/m/Y H:i') }}<br>
                <strong>Estado:</strong> {{ strtoupper($pedido->estado) }}<br>
                <strong>Método de Pago:</strong> {{ $pedido->pago ? ucfirst($pedido->pago->metodo) : 'Pendiente' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th>Producto / SKU</th>
                <th style="text-align: center;">Cant.</th>
                <th style="text-align: right;">Precio Unit.</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pedido->items as $item)
            <tr>
                <td>
                    {{ $item->variante->producto->nombre }}
                    @if($item->variante->sku)
                    <br><small style="color: #888;">SKU: {{ $item->variante->sku }}</small>
                    @endif
                </td>
                <td style="text-align: center;">{{ $item->cantidad }}</td>
                <td style="text-align: right;">S/ {{ number_format($item->precio_unitario, 2) }}</td>
                <td style="text-align: right;">S/ {{ number_format($item->cantidad * $item->precio_unitario, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-table">
            <tr>
                <td>Subtotal:</td>
                <td>S/ {{ number_format($pedido->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Costo de Envío:</td>
                <td>S/ {{ number_format($pedido->costo_envio, 2) }}</td>
            </tr>
            @if($pedido->descuento > 0)
            <tr>
                <td>Descuento:</td>
                <td style="color: #e53e3e;">- S/ {{ number_format($pedido->descuento, 2) }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total a Pagar:</td>
                <td>S/ {{ number_format($pedido->total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Gracias por su compra en Novape.<br>
        Documento generado automáticamente el {{ date('d/m/Y H:i:s') }}.
    </div>
</body>
</html>
