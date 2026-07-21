<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Pedido;
use App\Models\Comprobante;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
     * Genera y descarga el comprobante PDF de un pedido ecommerce
     */
    public function descargarComprobante($id)
    {
        $pedido = Pedido::with(['items.variante.producto.imagenes', 'usuario'])
            ->findOrFail($id);

        // Verificar que el pedido pertenece al usuario autenticado
        if (Auth::id() !== $pedido->usuario_id) {
            abort(403, 'No tienes permiso para ver este comprobante.');
        }

        $igvPorcentaje = 0.18;
        $total = (float) $pedido->total;
        $operacionesGravadas = round($total / (1 + $igvPorcentaje), 2);
        $igvCalculado = round($total - $operacionesGravadas, 2);

        $nombreCliente = $pedido->nombre_facturacion ?: ($pedido->usuario ? $pedido->usuario->nombres . ' ' . $pedido->usuario->apellidos : 'Cliente');
        $docCliente = $pedido->documento_cliente ?: ($pedido->usuario ? $pedido->usuario->dni : '---');

        $importeEnLetras = $this->numeroALetras($total);

        // QR con URL del comprobante
        $qrUrl = url("/comprobante/ecommerce/{$pedido->codigo}");
        $qrCodeSvg = QrCode::size(120)->generate($qrUrl);
        $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrCodeSvg);

        // Logo
        $logoPath = public_path('images/logofactura.png');
        $logoBase64 = '';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $data = [
            'pedido' => $pedido,
            'items' => $pedido->items,
            'operacionesGravadas' => $operacionesGravadas,
            'igvCalculado' => $igvCalculado,
            'total' => $total,
            'importeEnLetras' => $importeEnLetras,
            'nombreCliente' => $nombreCliente,
            'docCliente' => $docCliente,
            'qrBase64' => $qrBase64,
            'logoBase64' => $logoBase64,
            'tipo_comprobante' => $pedido->tipo_comprobante ?? 'Boleta',
            'empresa' => [
                'razon_social' => 'NOVAPE S.A.C.',
                'ruc' => '20123456789',
                'direccion' => 'Av. José Carlos Mariátegui, Lote 60 Zona A',
                'telefono' => '+51 986 784 384',
                'email' => 'atencionalcliente@novape.me',
                'horario' => 'Lunes a Viernes de 9 am a 6 pm'
            ]
        ];

        $pdfName = "Comprobante-{$pedido->codigo}.pdf";

        $storagePath = storage_path("app/public/comprobantes");
        if (!file_exists($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        $fullPath = "{$storagePath}/{$pdfName}";

        \Spatie\LaravelPdf\Facades\Pdf::view('pdf.comprobante_ecommerce', $data)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->save($fullPath);

        return response()->download($fullPath, $pdfName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $pdfName . '"'
        ]);
    }

    /**
     * Endpoint público para cuando escanean el QR del comprobante ecommerce
     */
    public function verComprobanteEcommerce($codigo)
    {
        $pedido = Pedido::where('codigo', $codigo)->first();

        if (!$pedido) {
            abort(404, 'Comprobante no encontrado');
        }

        $pdfPath = storage_path("app/public/comprobantes/Comprobante-{$pedido->codigo}.pdf");

        if (file_exists($pdfPath)) {
            return response()->file($pdfPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Comprobante-' . $pedido->codigo . '.pdf"'
            ]);
        }

        // Si no existe, generarlo al vuelo (temporal redirect)
        return redirect("/perfil/compras/{$pedido->codigo}");
    }
}
