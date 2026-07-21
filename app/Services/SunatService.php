<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SunatService
{
    protected $apiUrl;
    protected $apiToken;

    public function __construct()
    {
        $this->apiUrl = env('API_PERU_URL', 'https://apiperu.dev/api/cpe');
        $this->apiToken = env('API_PERU_TOKEN', 'SIMULACION_TOKEN');
    }

    /**
     * Emite un comprobante electrónico (Boleta o Factura) para un pedido pagado.
     * En modo simulación no envía a la SUNAT real.
     */
    public function emitirComprobante(Pedido $pedido)
    {
        // Evitar duplicidades
        if ($pedido->facturado_sunat) {
            return [
                'success' => false,
                'message' => 'El pedido ya fue facturado.'
            ];
        }

        // Determinar si es Boleta (DNI) o Factura (RUC) basándonos en los datos del pedido o checkout
        // Aquí asumimos que tienes un campo en `checkout_facturacion` o usas el DNI del usuario
        $esFactura = false; // Lógica para detectar si pidieron factura
        $tipoDoc = $esFactura ? '01' : '03'; // 01=Factura, 03=Boleta
        $serie = $esFactura ? 'F001' : 'B001';
        $correlativo = str_pad($pedido->id, 6, '0', STR_PAD_LEFT);

        // Construir el Payload formato API Peru
        $payload = [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => $tipoDoc,
            'serie' => $serie,
            'numero' => $correlativo,
            'sunat_transaction' => '1',
            'cliente_tipo_de_documento' => $esFactura ? '6' : '1', // 6=RUC, 1=DNI
            'cliente_numero_de_documento' => '00000000', // DNI de prueba
            'cliente_denominacion' => 'CLIENTE DE PRUEBA',
            'cliente_direccion' => 'LIMA',
            'cliente_email' => $pedido->usuario ? $pedido->usuario->email : '',
            'fecha_de_emision' => date('Y-m-d'),
            'moneda' => '1', // Soles
            'porcentaje_de_igv' => 18.00,
            'total_gravada' => round($pedido->total / 1.18, 2),
            'total_igv' => round($pedido->total - ($pedido->total / 1.18), 2),
            'total' => $pedido->total,
            'enviar_automaticamente_a_la_sunat' => true,
            'enviar_automaticamente_al_cliente' => true,
            'items' => []
        ];

        // Rellenar Items
        foreach ($pedido->items as $item) {
            $precioSinIgv = $item->precio / 1.18;
            $payload['items'][] = [
                'unidad_de_medida' => 'NIU', // Producto
                'codigo' => $item->variante ? $item->variante->sku : 'P01',
                'descripcion' => $item->variante ? $item->variante->producto->nombre : 'Producto',
                'cantidad' => $item->cantidad,
                'valor_unitario' => round($precioSinIgv, 2),
                'precio_unitario' => $item->precio,
                'subtotal' => round($precioSinIgv * $item->cantidad, 2),
                'tipo_de_igv' => '1',
                'igv' => round(($item->precio - $precioSinIgv) * $item->cantidad, 2),
                'total' => $item->precio * $item->cantidad,
                'anticipo_regularizacion' => false
            ];
        }

        // Simulación: Si no hay token real, devolvemos fake URLs
        if ($this->apiToken === 'SIMULACION_TOKEN') {
            Log::info("SIMULACION API PERU: Comprobante $serie-$correlativo generado.", $payload);
            
            $pedido->comprobante_tipo = $esFactura ? 'Factura' : 'Boleta';
            $pedido->comprobante_serie = $serie;
            $pedido->comprobante_correlativo = $correlativo;
            $pedido->enlace_pdf = 'https://apiperu.dev/comprobantes/fake-pdf-link';
            $pedido->enlace_xml = 'https://apiperu.dev/comprobantes/fake-xml-link';
            $pedido->facturado_sunat = true;
            $pedido->save();

            return [
                'success' => true,
                'pdf' => $pedido->enlace_pdf,
                'xml' => $pedido->enlace_xml
            ];
        }

        // Llamada Real a la API (Cuando el usuario pegue su token)
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json'
            ])->post($this->apiUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['success']) && $data['success']) {
                    $pedido->comprobante_tipo = $esFactura ? 'Factura' : 'Boleta';
                    $pedido->comprobante_serie = $serie;
                    $pedido->comprobante_correlativo = $correlativo;
                    $pedido->enlace_pdf = $data['data']['enlaces']['pdf'] ?? null;
                    $pedido->enlace_xml = $data['data']['enlaces']['xml'] ?? null;
                    $pedido->facturado_sunat = true;
                    $pedido->save();

                    Log::info("SUNAT EXITO: Comprobante generado $serie-$correlativo");
                    return ['success' => true];
                }
            }

            Log::error("SUNAT ERROR API: " . $response->body());
            return ['success' => false, 'error' => 'La API de SUNAT rechazó la petición.'];

        } catch (\Exception $e) {
            Log::error("SUNAT EXCEPTION: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
