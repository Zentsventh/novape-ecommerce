<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Pos;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPosSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check(); // Podrías añadir validación de roles aquí
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.variante_id' => 'required|exists:variante,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'items.*.producto_nombre' => 'required|string',
            'metodo_pago_id' => 'required|exists:metodos_pago,id',
            'tipo_comprobante' => 'in:ticket,boleta,factura',
            'cliente' => 'nullable|array',
            'descuento' => 'nullable|numeric|min:0',
            'pagos' => 'nullable|array'
        ];
    }
}
