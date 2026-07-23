<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use Illuminate\Http\Request;
use function Spatie\LaravelPdf\Support\pdf;

class InvoiceController extends Controller
{
    public function descargarComprobante($pedidoId)
    {
        // 1. Consultar datos del pedido con sus relaciones
        $pedido = Pedido::with(['items.variante.producto', 'usuario'])->findOrFail($pedidoId);
        
        // 2. Definir nombre del archivo
        $filename = 'Comprobante-PED-' . ($pedido->codigo_pedido ?? $pedido->id) . '.pdf';

        // 3. Preparar recursos (Logo y QR) en base64 para garantizar que Chromium los lea localmente
        $logoPath = public_path('images/logofactura.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        } else {
            // Fallback to standard logo
            $logoPath = public_path('images/logo.png');
            if (file_exists($logoPath)) {
                $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            }
        }

        // Generate a dummy QR code for the hash (or use an actual library if installed)
        // For now, we leave it null so the template shows the placeholder, unless a library is used.
        $qrBase64 = null;
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            $qrContent = "Comprobante: " . $filename . " | Hash: " . md5($pedido->id . $pedido->codigo_pedido . time());
            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::size(150)->generate($qrContent);
            $qrBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);
        }

        $letras = null;
        if (class_exists(\App\Helpers\NumberToWords::class)) {
            $letras = \App\Helpers\NumberToWords::convert($pedido->total);
        }

        // 4. Generar PDF usando Spatie (Chromium Headless)
        return pdf()
            ->view('pdf.invoice', [
                'pedido' => $pedido,
                'logoBase64' => $logoBase64,
                'qrBase64' => $qrBase64,
                'letras' => $letras
            ])
            ->format('a4')
            ->name($filename)
            ->download();
    }
}
