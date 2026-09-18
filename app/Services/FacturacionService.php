<?php

namespace App\Services;

use App\Models\Pedido;
use Illuminate\Support\Facades\Log;

class FacturacionService
{
    /**
     * Envía el comprobante electrónico a SUNAT (vía API u OSE).
     * En producción se debe integrar con el proveedor (ej. NubeFact, PSE).
     * 
     * @param Pedido $pedido
     * @return array
     */
    public function emitirComprobante(Pedido $pedido)
    {
        Log::info("Iniciando emisión de comprobante electrónico para pedido #{$pedido->codigo}");
        
        try {
            // TODO: Integración real con API de facturación
            // $client = new \GuzzleHttp\Client();
            // $response = $client->post(env('FACTURACION_API_URL'), [...]);
            
            // Simulación de respuesta exitosa por SUNAT
            return [
                'exito' => true,
                'enlace_pdf' => 'https://api.facturacion.ejemplo.com/v1/pdf/' . $pedido->codigo,
                'enlace_xml' => 'https://api.facturacion.ejemplo.com/v1/xml/' . $pedido->codigo,
                'enlace_cdr' => 'https://api.facturacion.ejemplo.com/v1/cdr/' . $pedido->codigo,
                'sunat_ticket' => 'TICKET-' . rand(100000, 999999),
                'sunat_respuesta' => 'Aceptado por SUNAT'
            ];
            
        } catch (\Exception $e) {
            Log::error("Error al emitir comprobante para pedido #{$pedido->codigo}: " . $e->getMessage());
            return [
                'exito' => false,
                'error' => 'No se pudo conectar con el servicio de facturación.'
            ];
        }
    }
}
