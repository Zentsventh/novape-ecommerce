<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ProcessStripePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'coupon' => 'nullable|string',
            'shippingCost' => 'nullable|numeric|min:0',
            'deliveryType' => 'nullable|string',
            'distrito' => 'nullable|string',
            'shippingAddress' => 'nullable|array',
            'facturacion' => 'nullable|array',
            'email' => 'nullable|email',
        ];
    }
}
