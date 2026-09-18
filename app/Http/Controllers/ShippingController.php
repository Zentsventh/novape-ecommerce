<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Shipping\CalculateShippingRequest;
use App\Http\Requests\Shipping\ValidateAddressRequest;
use App\Services\Shipping\ShippingCalculationService;
use App\Models\Pedido;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShippingController extends Controller
{
    public function __construct(
        private readonly ShippingCalculationService $shippingCalculationService
    ) {}

    public function calculate(CalculateShippingRequest $request)
    {
        $cart = $request->input('cart');
        if (!$cart || empty($cart)) {
            $cart = session()->get('cart', []);
        }

        $result = $this->shippingCalculationService->calculateCost($cart, $request->input('address', []));

        return response()->json($result);
    }

    public function validateAddress(ValidateAddressRequest $request)
    {
        $validation = $this->shippingCalculationService->validateAddress($request->validated());
        return response()->json($validation);
    }

    public function trackPage(Request $request)
    {
        $codigo = $request->query('codigo');
        if (!$codigo) {
            return redirect('/perfil')->withErrors(['error' => 'Código de pedido no proporcionado.']);
        }

        $pedido = Pedido::where('codigo', $codigo)->firstOrFail();
        $carrier = $pedido->courier_name ?: 'shippo';
        $trackingNumber = $pedido->tracking_number;

        $trackingData = null;
        if ($trackingNumber) {
            $trackingData = $this->shippingCalculationService->getTrackingData($carrier, $trackingNumber);
        }

        return Inertia::render('Auth/Tracking', [
            'pedido' => $pedido,
            'trackingData' => $trackingData
        ]);
    }
}
