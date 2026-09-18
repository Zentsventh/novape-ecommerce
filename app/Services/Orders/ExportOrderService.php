<?php

declare(strict_types=1);

namespace App\Services\Orders;

use App\Models\Pedido;

class ExportOrderService
{
    /**
     * Generates CSV content for orders.
     * Uses chunking to prevent memory issues with large datasets.
     */
    public function exportCsv(): string
    {
        $csv = "ID,Código,Cliente,Email,Total,Estado,Fecha\n";

        Pedido::with('usuario')->chunkById(500, function ($pedidos) use (&$csv) {
            foreach ($pedidos as $p) {
                $clienteNombre = $p->usuario ? $p->usuario->nombres . ' ' . $p->usuario->apellidos : 'N/A';
                $email = $p->usuario ? $p->usuario->email : '';
                
                $csv .= implode(',', [
                    $p->id,
                    $p->codigo,
                    '"' . $clienteNombre . '"',
                    $email,
                    $p->total,
                    $p->estado,
                    $p->created_at,
                ]) . "\n";
            }
        });

        return $csv;
    }
}
