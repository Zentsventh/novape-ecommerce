<?php

namespace App\Services;

use App\Models\Pedido;
use App\Models\Comprobante;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FacturacionService
{
    /**
     * URL base de la API del Proveedor de Servicios Electrónicos (Ej. Nubefact, API Perú)
     */
    protected $apiUrl;

    /**
     * Token de autorización de la API
     */
    protected $apiToken;

    public function __construct()
    {
        // En producción, estas variables vendrán del archivo .env
        $this->apiUrl = env('FACTURACION_API_URL', 'https://api.proveedor-pse-mock.com/v1');
        $this->apiToken = env('FACTURACION_API_TOKEN', 'test_token_123');
    }

    /**
     * Genera un comprobante electrónico para el pedido dado.
     * Si no hay RUC, genera Boleta. Si hay RUC, genera Factura.
     * 
     * @param Pedido $pedido
     * @return Comprobante|null
     */
    public function emitirComprobante(Pedido $pedido)
    {
        // 1. Determinar el tipo de comprobante basado en el usuario
        $usuario = $pedido->usuario;
        
        // Asumimos que si tiene RUC registrado (tipo_documento == 'RUC'), es Factura (01)
        // Caso contrario, o si es invitado, es Boleta (03)
        $esFactura = ($usuario && $usuario->tipo_documento === 'RUC');
        $tipoComprobante = $esFactura ? '01' : '03';
        $serie = $esFactura ? 'F001' : 'B001';
        
        // 2. Construir el JSON estandarizado para enviar a la API del PSE
        $datosFacturacion = $this->prepararDatosEnvio($pedido, $tipoComprobante, $serie);

        try {
            // 3. Realizar la petición HTTP a la API del proveedor
            // Nota: Al estar en un entorno simulado, simularemos la respuesta exitosa.
            // En producción, descomentar la llamada real HTTP.
            
            /*
            $response = Http::withToken($this->apiToken)
                            ->post($this->apiUrl . '/comprobantes', $datosFacturacion);

            if (!$response->successful()) {
                Log::error("Error al emitir comprobante para Pedido {$pedido->id}: " . $response->body());
                return null;
            }
            $data = $response->json();
            */

            // SIMULACIÓN DE RESPUESTA DE API PERÚ / NUBEFACT
            $numeroAleatorio = str_pad(rand(1, 99999), 8, '0', STR_PAD_LEFT);
            $data = [
                'aceptada_por_sunat' => true,
                'sunat_description' => 'La Boleta/Factura ha sido aceptada',
                'enlace_del_pdf' => "https://mis-comprobantes-pse.com/pdf/{$serie}-{$numeroAleatorio}.pdf",
                'enlace_del_xml' => "https://mis-comprobantes-pse.com/xml/{$serie}-{$numeroAleatorio}.xml",
                'numero' => $numeroAleatorio,
            ];

            // 4. Guardar el comprobante en la base de datos
            $comprobante = Comprobante::create([
                'pedido_id' => $pedido->id,
                'tipo_comprobante' => $tipoComprobante,
                'serie' => $serie,
                'numero' => $data['numero'],
                'fecha_emision' => now()->toDateString(),
                'total' => $pedido->total,
                'estado_sunat' => $data['aceptada_por_sunat'] ? 'Aceptado' : 'Rechazado',
                'enlace_pdf' => $data['enlace_del_pdf'] ?? null,
                'enlace_xml' => $data['enlace_del_xml'] ?? null,
            ]);

            Log::info("Comprobante {$serie}-{$data['numero']} emitido correctamente para el pedido {$pedido->id}.");
            return $comprobante;

        } catch (\Exception $e) {
            Log::error("Excepción al emitir comprobante para Pedido {$pedido->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mapea el Pedido a un array con el formato requerido por el Proveedor de Facturación.
     */
    protected function prepararDatosEnvio(Pedido $pedido, $tipoComprobante, $serie)
    {
        $usuario = $pedido->usuario;
        
        $items = [];
        foreach ($pedido->items as $item) {
            $items[] = [
                'unidad_de_medida' => 'NIU',
                'codigo' => $item->variante ? $item->variante->sku : 'ITEM-GENERICO',
                'descripcion' => $item->variante ? $item->variante->producto->nombre : 'Producto',
                'cantidad' => $item->cantidad,
                'valor_unitario' => $item->precio_unitario / 1.18, // Precio sin IGV
                'precio_unitario' => $item->precio_unitario,       // Precio con IGV
                'subtotal' => ($item->precio_unitario / 1.18) * $item->cantidad,
                'tipo_de_igv' => 1, // Gravado - Operación Onerosa
                'igv' => ($item->precio_unitario - ($item->precio_unitario / 1.18)) * $item->cantidad,
                'total' => $item->precio_unitario * $item->cantidad,
                'anticipo_regularizacion' => false,
            ];
        }

        return [
            'operacion' => 'generar_comprobante',
            'tipo_de_comprobante' => $tipoComprobante,
            'serie' => $serie,
            'numero' => 'autogenerado',
            'sunat_transaction' => 1,
            'cliente_tipo_de_documento' => ($usuario && $usuario->tipo_documento === 'RUC') ? 6 : 1, // 6 RUC, 1 DNI
            'cliente_numero_de_documento' => $usuario ? $usuario->dni : '00000000', // En caso de cliente sin cuenta, dni o 8 ceros
            'cliente_denominacion' => $usuario ? ($usuario->nombres . ' ' . $usuario->apellidos) : 'Cliente Invitado',
            'cliente_direccion' => $usuario ? $usuario->direccion : '-',
            'cliente_email' => $usuario ? $usuario->email : '-',
            'fecha_de_emision' => now()->format('Y-m-d'),
            'moneda' => 1, // 1 Soles
            'porcentaje_de_igv' => 18.00,
            'total_descuento' => $pedido->descuento,
            'total_gravada' => $pedido->total / 1.18,
            'total_igv' => $pedido->total - ($pedido->total / 1.18),
            'total' => $pedido->total,
            'detalles' => $items
        ];
    }
}
