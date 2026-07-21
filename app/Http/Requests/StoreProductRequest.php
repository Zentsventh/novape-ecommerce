<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }



    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:150',
            'marca_id' => 'required|integer|exists:marca,id',
            'proveedor_id' => 'nullable|integer|exists:proveedor,id',
            'sku_base' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
            'garantias' => 'nullable|string',
            'activo' => 'boolean',
            'precio' => 'required|numeric|min:0',
            'peso_kg' => 'required|numeric|min:0',
            'categorias' => 'array',
            'categorias.*' => 'integer|exists:categoria,id',
            'imagenes' => 'array',
            // Can be string (URL) or uploaded file
            'imagenes.*' => 'nullable',
            'stock' => 'required|integer|min:0',
            'especificaciones' => 'present|array',
            'especificaciones.*.nombre' => 'required|string',
            'especificaciones.*.valor' => 'required|string',
        ];
    }
}
