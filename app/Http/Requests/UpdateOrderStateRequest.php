<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'estado' => 'required|string|in:pendiente,procesando,enviado,completado,cancelado',
            'tracking_number' => 'nullable|string|max:100',
            'courier_name' => 'nullable|string|max:100'
        ];
    }
}
