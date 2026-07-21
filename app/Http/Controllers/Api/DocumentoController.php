<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentoController extends Controller
{
    public function consultar(Request $request)
    {
        $tipo = $request->input('tipo'); // 'DNI' o 'RUC'
        $numero = $request->input('numero');

        if (!$numero || !in_array($tipo, ['DNI', 'RUC'])) {
            return response()->json(['error' => 'Tipo o número de documento inválido'], 400);
        }

        $token = env('API_PERU_TOKEN');
        $mockData = [
            'success' => true,
            'data' => [
                'numero' => $numero,
                'nombre_completo' => 'USUARIO DE PRUEBA',
                'nombres' => 'USUARIO',
                'apellido_paterno' => 'DE',
                'apellido_materno' => 'PRUEBA',
                'direccion_completa' => 'AV. LOS INCAS 123',
                'nombre_o_razon_social' => 'EMPRESA DE PRUEBA SAC'
            ]
        ];

        if (!$token) {
            return response()->json($mockData);
        }

        try {
            $endpoint = $tipo === 'DNI' ? "https://apiperu.dev/api/dni/{$numero}" : "https://apiperu.dev/api/ruc/{$numero}";
            
            $response = Http::withToken($token)
                            ->withHeaders(['Accept' => 'application/json'])
                            ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['success']) && $data['success'] === true) {
                    return response()->json($data);
                }
            }

            // Fallback a mock si el API falla (ej. token inválido o expirado)
            return response()->json($mockData);

        } catch (\Exception $e) {
            Log::error("Error consultando API Perú: " . $e->getMessage());
            // Fallback a mock en caso de excepción
            return response()->json($mockData);
        }
    }
}
