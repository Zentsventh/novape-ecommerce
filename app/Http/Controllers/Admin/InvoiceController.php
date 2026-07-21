<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\Facades\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    private function numeroALetras($number)
    {
        $centenas = ['', 'CIENTO ', 'DOSCIENTOS ', 'TRESCIENTOS ', 'CUATROCIENTOS ', 'QUINIENTOS ', 'SEISCIENTOS ', 'SETECIENTOS ', 'OCHOCIENTOS ', 'NOVECIENTOS '];
        $decenas = ['', 'DIEZ ', 'VEINTE ', 'TREINTA ', 'CUARENTA ', 'CINCUENTA ', 'SESENTA ', 'SETENTA ', 'OCHENTA ', 'NOVENTA '];
        $unidades = ['', 'UNO ', 'DOS ', 'TRES ', 'CUATRO ', 'CINCO ', 'SEIS ', 'SIETE ', 'OCHO ', 'NUEVE ', 'DIEZ ', 'ONCE ', 'DOCE ', 'TRECE ', 'CATORCE ', 'QUINCE ', 'DIECISEIS ', 'DIECISIETE ', 'DIECIOCHO ', 'DIECINUEVE ', 'VEINTE ', 'VEINTIUNO ', 'VEINTIDOS ', 'VEINTITRES ', 'VEINTICUATRO ', 'VEINTICINCO ', 'VEINTISEIS ', 'VEINTISIETE ', 'VEINTIOCHO ', 'VEINTINUEVE '];

        $convertGroup = function($n) use ($centenas, $decenas, $unidades) {
            $output = '';
            if ($n == 100) return 'CIEN ';
            if ($n >= 100) {
                $output .= $centenas[floor($n / 100)];
                $n = $n % 100;
            }
            if ($n < 30 && $n > 0) {
                $output .= $unidades[$n];
            } elseif ($n >= 30) {
                $output .= $decenas[floor($n / 10)];
                if ($n % 10 > 0) {
                    $output .= 'Y ' . $unidades[$n % 10];
                }
            }
            return $output;
        };

        $intPart = (int) floor($number);
        $decimalPart = round(($number - $intPart) * 100);
        $decimalStr = str_pad($decimalPart, 2, '0', STR_PAD_LEFT);

        if ($intPart == 0) {
            $letras = 'CERO ';
        } else {
            $letras = '';
            if ($intPart >= 1000000) {
                $millones = floor($intPart / 1000000);
                $letras .= $millones == 1 ? 'UN MILLON ' : $convertGroup($millones) . 'MILLONES ';
                $intPart = $intPart % 1000000;
            }
            if ($intPart >= 1000) {
                $miles = floor($intPart / 1000);
                $letras .= $miles == 1 ? 'MIL ' : $convertGroup($miles) . 'MIL ';
                $intPart = $intPart % 1000;
            }
            if ($intPart > 0) {
                $letras .= $convertGroup($intPart);
            }
        }

        return "SON: " . trim($letras) . " CON {$decimalStr}/100 SOLES";
    }

    /**
     * Genera la representación impresa (PDF) de una venta del POS
     */
    public function generarFacturaPos($id)
    {
        // 1. Obtener datos de la venta POS
        $venta = DB::table('ventas_pos')
            ->leftJoin('clientes', 'ventas_pos.cliente_id', '=', 'clientes.id')
            ->leftJoin('usuario', 'ventas_pos.cajero_id', '=', 'usuario.id')
            ->select('ventas_pos.*', 
                     'clientes.nombre_razon_social as cliente_nombre', 
                     'clientes.numero_documento as cliente_doc',
                     'clientes.tipo_documento as cliente_tipo_doc', 
                     'clientes.direccion as cliente_direccion', 
                     'usuario.nombres as cajero_nombre')
            ->where('ventas_pos.id', $id)
            ->first();

        if (!$venta) {
            abort(404, 'Venta no encontrada');
        }

        // Obtener ítems
        $items = DB::table('venta_pos_items')
            ->where('venta_pos_id', $id)
            ->get();

        // 2. Lógica Tributaria SUNAT (Cálculos de Totales)
        $igvPorcentaje = 0.18; // Esto también podría venir de ConfiguracionSitio
        $total = (float) $venta->total;
        $operacionesGravadas = round($total / (1 + $igvPorcentaje), 2);
        $igvCalculado = round($total - $operacionesGravadas, 2);

        // Lógica de validación SUNAT de clientes
        $nombreCliente = $venta->cliente_nombre;
        $docCliente = $venta->cliente_doc;
        $tipoDocCliente = $venta->cliente_tipo_doc === 'RUC' ? '6' : '1';

        if ($venta->tipo_comprobante === 'factura') {
            if (empty($docCliente) || $tipoDocCliente !== '6') {
                $nombreCliente = "FACTURA REQUIERE RUC VÁLIDO";
            }
        } else if ($venta->tipo_comprobante === 'boleta') {
            if ($total >= 700 && empty($docCliente)) {
                $nombreCliente = "REQUIERE DNI (MONTO >= S/700)";
            } else if (empty($docCliente)) {
                $nombreCliente = "CLIENTES VARIOS";
                $docCliente = "00000000";
            }
        } else {
            // Nota de Venta (Ticket)
            if (empty($nombreCliente)) {
                $nombreCliente = "Público General";
                $docCliente = "---";
            }
        }

        $importeEnLetras = $this->numeroALetras($total);

        // 3. Preparar QR Code (Ahora será una URL apuntando al comprobante en PDF)
        $qrUrl = url("/comprobante/{$venta->codigo_ticket}");
        
        // Generar QR en formato SVG Base64 para inyectarlo al PDF
        $qrCodeSvg = QrCode::size(120)->generate($qrUrl);
        $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);

        // Guardar el QR físicamente en una carpeta específica (como solicitó el usuario)
        $qrStoragePath = storage_path("app/public/qrs");
        if (!file_exists($qrStoragePath)) {
            mkdir($qrStoragePath, 0755, true);
        }
        file_put_contents("{$qrStoragePath}/qr_{$venta->codigo_ticket}.svg", $qrCodeSvg);

        // Logo Base64
        $logoPath = public_path('images/logofactura.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $empresaRuc = '20123456789';
        // Estructurar toda la data para la vista
        $data = [
            'venta' => $venta,
            'items' => $items,
            'operacionesGravadas' => $operacionesGravadas,
            'igvCalculado' => $igvCalculado,
            'total' => $total,
            'importeEnLetras' => $importeEnLetras,
            'nombreCliente' => $nombreCliente,
            'docCliente' => $docCliente,
            'qrBase64' => $qrBase64,
            'logoBase64' => $logoBase64,
            'empresa' => [
                'razon_social' => 'NOVAPE S.A.C.',
                'ruc' => $empresaRuc,
                'direccion' => 'Av. José Carlos Mariátegui, Lote 60 Zona A',
                'telefono' => '+51 986 784 384',
                'email' => 'atencionalcliente@novape.me',
                'horario' => 'Lunes a Viernes de 9 am a 6 pm'
            ]
        ];

        // 4. Generación del PDF (HTML to PDF) con Puppeteer
        $pdfName = "{$venta->codigo_ticket}.pdf";
        
        // Carpeta donde se guardará físicamente
        $storagePath = storage_path("app/public/facturas");
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        $fullPath = "{$storagePath}/{$pdfName}";

        // Compilar la vista Blade y tomar la fotografía PDF (Formato Ticket 80mm)
        // 80mm width is approximately 3.15 inches (ancho típico de ticketera térmica)
        // Height lo dejamos algo holgado (ej. 297mm o auto si el paquete lo permite, usaremos papel continuo)
        Pdf::view('pdf.ticket_pos', $data)
            ->paperSize(80, 297, 'mm') // 80mm width. Height 297mm (se corta al imprimir). En ticketeras el rollo es continuo.
            ->margins(0, 0, 0, 0)
            ->save($fullPath);

        // Devolver el archivo al navegador para visualizar/descargar en una nueva pestaña (inline)
        return response()->download($fullPath, $pdfName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfName . '"'
        ]);
    }

    /**
     * Endpoint público para cuando el usuario escanea el QR
     */
    public function verComprobantePublico($codigo_ticket)
    {
        $venta = DB::table('ventas_pos')->where('codigo_ticket', $codigo_ticket)->first();
        
        if (!$venta) {
            abort(404, 'Comprobante no encontrado');
        }

        // Devolver el PDF que ya fue generado y guardado en la carpeta de facturas
        $pdfPath = storage_path("app/public/facturas/{$venta->codigo_ticket}.pdf");
        
        if (file_exists($pdfPath)) {
            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $venta->codigo_ticket . '.pdf"'
            ]);
        }
        
        // Si no existe físicamente, generarlo al vuelo
        return $this->generarFacturaPos($venta->id);
    }
}
