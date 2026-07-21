<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante {{ $venta->codigo_ticket }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 14px;
            margin: 0;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .company-info {
            width: 50%;
        }
        .company-info img {
            max-width: 180px;
            margin-bottom: 15px;
        }
        .company-details {
            font-size: 12px;
            line-height: 1.6;
        }
        .invoice-details {
            width: 40%;
            text-align: center;
            border: 2px solid #333;
            border-radius: 8px;
            padding: 15px;
            background-color: #f9f9f9;
        }
        .invoice-details h2 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }
        .invoice-details p {
            margin: 5px 0;
            font-weight: bold;
            font-size: 16px;
        }
        .client-info {
            margin-bottom: 30px;
        }
        .client-info table {
            width: 100%;
            font-size: 13px;
        }
        .client-info td {
            padding: 5px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            background-color: #333;
            color: #fff;
            padding: 12px 10px;
            text-align: left;
            font-size: 13px;
        }
        .items-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #ddd;
            font-size: 13px;
        }
        .items-table th.text-right, .items-table td.text-right {
            text-align: right;
        }
        .items-table th.text-center, .items-table td.text-center {
            text-align: center;
        }
        .totals-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .qr-section {
            width: 30%;
        }
        .totals-table {
            width: 45%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 10px;
            font-size: 14px;
        }
        .totals-table tr.total-row {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #333;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 11px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            line-height: 1.5;
        }
        .amount-in-words {
            margin-top: 20px;
            font-size: 13px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-info">
            @if($logoBase64)
                <img src="{{ $logoBase64 }}" alt="Logo">
            @else
                <h1 style="margin:0; margin-bottom:10px;">{{ $empresa['razon_social'] }}</h1>
            @endif
            <div class="company-details">
                <strong>{{ $empresa['razon_social'] }}</strong><br>
                ATENCIÓN AL CLIENTE | NOVAPE<br>
                {{ $empresa['direccion'] }}<br>
                {{ $empresa['telefono'] }} | {{ $empresa['email'] }}<br>
                {{ $empresa['horario'] }}
            </div>
        </div>
        <div class="invoice-details">
            <p>RUC: {{ $empresa['ruc'] }}</p>
            <h2>
                @if($venta->tipo_comprobante === 'factura')
                    FACTURA ELECTRÓNICA
                @elseif($venta->tipo_comprobante === 'boleta')
                    BOLETA DE VENTA ELECTRÓNICA
                @else
                    TICKET DE VENTA
                @endif
            </h2>
            <p>{{ $venta->codigo_ticket }}</p>
        </div>
    </div>

    <div class="client-info">
        <table>
            <tr>
                <td width="120"><strong>Cliente:</strong></td>
                <td>{{ $venta->cliente_nombre ?? 'CLIENTES VARIOS' }}</td>
                <td width="120"><strong>Fecha Emisión:</strong></td>
                <td>{{ \Carbon\Carbon::parse($venta->created_at)->format('d/m/Y H:i:s') }}</td>
            </tr>
            <tr>
                <td><strong>Doc. Identidad:</strong></td>
                <td>{{ $venta->cliente_doc ?? '---' }}</td>
                <td><strong>Moneda:</strong></td>
                <td>SOLES (PEN)</td>
            </tr>
            <tr>
                <td><strong>Dirección:</strong></td>
                <td>{{ $venta->cliente_direccion ?? '---' }}</td>
                <td><strong>Forma de Pago:</strong></td>
                <td>Contado</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th width="10%" class="text-center">CANT.</th>
                <th width="15%" class="text-center">U.M.</th>
                <th width="45%">DESCRIPCIÓN</th>
                <th width="15%" class="text-right">P. UNITARIO</th>
                <th width="15%" class="text-right">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td class="text-center">{{ $item->cantidad }}</td>
                <td class="text-center">NIU</td>
                <td>{{ $item->producto_nombre }}</td>
                <td class="text-right">S/ {{ number_format($item->precio_unitario, 2) }}</td>
                <td class="text-right">S/ {{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <div class="qr-section">
            <img src="{{ $qrBase64 }}" alt="QR Code" style="width:140px;height:140px;">
        </div>
        
        <table class="totals-table">
            <tr>
                <td>Operaciones Gravadas:</td>
                <td class="text-right">S/ {{ number_format($operacionesGravadas, 2) }}</td>
            </tr>
            <tr>
                <td>Operaciones Inafectas:</td>
                <td class="text-right">S/ 0.00</td>
            </tr>
            <tr>
                <td>IGV (18%):</td>
                <td class="text-right">S/ {{ number_format($igvCalculado, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>IMPORTE TOTAL:</td>
                <td class="text-right">S/ {{ number_format($total, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="amount-in-words">
        SON: {{ number_format($total, 2) }} CON 00/100 SOLES
    </div>

    <div class="footer">
        <p>Representación impresa de la @if($venta->tipo_comprobante === 'factura') FACTURA ELECTRÓNICA @elseif($venta->tipo_comprobante === 'boleta') BOLETA DE VENTA ELECTRÓNICA @else NOTA DE VENTA @endif.</p>
        <p>Puede consultar su comprobante en <strong>https://sunat.gob.pe</strong> o en <strong>https://novape.me/comprobantes</strong></p>
        <p>Hash de Seguridad: {{ hash('sha256', $venta->codigo_ticket . $venta->created_at . 'secret') }}</p>
    </div>

</body>
</html>
