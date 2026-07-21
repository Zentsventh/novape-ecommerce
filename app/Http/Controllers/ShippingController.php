<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ShippoService;
use App\Models\Producto;

class ShippingController extends Controller
{
    protected $shippoService;

    public function __construct(ShippoService $shippoService)
    {
        $this->shippoService = $shippoService;
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'address.departamento' => 'required|string',
            'address.provincia' => 'required|string',
            'address.distrito' => 'required|string',
            'address.codigo_postal' => 'nullable|string', // A veces los usuarios no lo saben en Perú
        ]);

        $cart = $request->input('cart');
        if (!$cart || empty($cart)) {
            $cart = session()->get('cart', []);
        }
        
        if (empty($cart)) {
            return response()->json(['costo' => 0]);
        }

        // Calcular peso total del carrito
        $pesoTotalKg = 0;
        foreach ($cart as $item) {
            // Buscamos el peso del producto en la BD (asumiendo 1kg si no tiene)
            $producto = Producto::find($item['id']);
            $pesoUnidad = $producto ? $producto->peso_kg : 1.0;
            $pesoTotalKg += ($pesoUnidad * $item['cantidad']);
        }

        // Determinar código postal destino (aproximación básica para FedEx si no lo proveen)
        // En Perú, si es Lima, usamos 15001 por defecto si está vacío.
        $destZip = $request->input('address.codigo_postal');
        if (empty($destZip)) {
            if (strtolower($request->input('address.departamento')) === 'lima') {
                $destZip = '15001';
            } else {
                $destZip = '04001'; // Default genérico provincia (Arequipa ej.)
            }
        }

        // Zip de origen de la Tienda (Ejemplo: Lima, SJL)
        $originZip = '15401'; 

        $costoEnvio = $this->shippoService->getShippingRate($originZip, $destZip, $pesoTotalKg);

        // Guardar costo en sesión para el checkout
        session(['checkout_shipping_cost' => $costoEnvio]);

        return response()->json([
            'costo' => $costoEnvio,
            'peso_total' => $pesoTotalKg,
            'courier' => 'Shippo'
        ]);
    }

    public function validateAddress(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'direccion' => 'required|string',
            'departamento' => 'nullable|string',
            'provincia' => 'nullable|string',
            'distrito' => 'nullable|string',
        ]);

        $addressData = [
            'name' => $request->input('nombres') . ' ' . $request->input('apellidos'),
            'street1' => $request->input('direccion'),
            'city' => $request->input('provincia', 'Lima'),
            'state' => $request->input('departamento', 'LMA'),
            'country' => 'PE'
        ];

        $validation = $this->shippoService->validateAddress($addressData);

        return response()->json($validation);
    }

    public function trackPage(Request $request)
    {
        $codigo = $request->query('codigo');
        if (!$codigo) {
            return redirect('/perfil')->withErrors(['error' => 'Código de pedido no proporcionado.']);
        }

        // Buscar el pedido y asegurarse que pertenece al usuario (si es cliente)
        $pedido = \App\Models\Pedido::where('codigo', $codigo)->firstOrFail();

        // En caso de que no haya carrier o tracking (aún no enviado), mostramos la página con estado inicial.
        $carrier = $pedido->courier_name ?: 'shippo'; // shippo por defecto para sandbox
        $trackingNumber = $pedido->tracking_number;

        $trackingData = null;
        if ($trackingNumber) {
            $trackingData = $this->shippoService->trackPackage($carrier, $trackingNumber);
        }

        return \Inertia\Inertia::render('Auth/Tracking', [
            'pedido' => $pedido,
            'trackingData' => $trackingData
        ]);
    }
}
