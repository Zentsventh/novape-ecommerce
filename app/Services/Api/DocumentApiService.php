<?php

declare(strict_types=1);

namespace App\Services\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentApiService
{
    public function consultar(string $tipo, string $numero): array
    {
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
            return $mockData;
        }

        try {
            $endpoint = $tipo === 'DNI' ? "https://apiperu.dev/api/dni/{$numero}" : "https://apiperu.dev/api/ruc/{$numero}";
            
            $response = Http::withToken($token)
                            ->withHeaders(['Accept' => 'application/json'])
                            ->get($endpoint);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['success']) && $data['success'] === true) {
                    return $data;
                }
            }

            return $mockData;

        } catch (\Exception $e) {
            Log::error("Error consultando API Perú: " . $e->getMessage());
            return $mockData;
        }
    }
}
