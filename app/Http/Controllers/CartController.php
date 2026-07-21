<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Producto;

class CartController extends Controller
{
    private function syncCartToDB($cart)
    {
        if (auth()->check()) {
            $user = auth()->user();
            $carrito = $user->carrito()->firstOrCreate(['session_id' => session()->getId()]);
            
            if (empty($cart)) {
                $carrito->items()->delete();
                return;
            }

            $variantesActuales = [];
            foreach ($cart as $item) {
                if (isset($item['variante_id'])) {
                    $variantesActuales[] = $item['variante_id'];
                    \App\Models\CarritoItem::updateOrCreate(
                        ['carrito_id' => $carrito->id, 'variante_id' => $item['variante_id']],
                        ['cantidad' => $item['cantidad']]
                    );
                }
            }
            
            // Eliminar los que ya no están en el carrito actual
            $carrito->items()->whereNotIn('variante_id', $variantesActuales)->delete();
        }
    }

    /**
     * Agrega un producto al carrito en la sesión.
     */
    public function add(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer|exists:producto,id',
            'cantidad'    => 'required|integer|min:1'
        ]);

        $productoId = $request->producto_id;
        $cantidad = $request->cantidad;
        
        $producto = Producto::with(['imagenes', 'variantes'])->findOrFail($productoId);
        $imagen = $producto->imagenes->first();
        
        // Buscar la primera variante y su stock en el almacén E-Commerce
        $almacenEcommerceId = \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
        $variante = $producto->variantes->first();
        
        $stockActual = 0;
        if ($variante) {
            $stockAlmacen = \Illuminate\Support\Facades\DB::table('stock_almacen')
                ->where('almacen_id', $almacenEcommerceId)
                ->where('variante_id', $variante->id)
                ->first();
            $stockActual = $stockAlmacen ? $stockAlmacen->cantidad : 0;
        }

        $stockReservado = 0;
        if ($variante) {
            $stockReservado = \App\Models\ReservaStock::where('variante_id', $variante->id)
                ->where('session_id', '!=', session()->getId())
                ->where('expires_at', '>', now())
                ->sum('cantidad');
        }
        $stockDisponible = max(0, $stockActual - $stockReservado);

        $cart = session()->get('cart', []);
        $currentQuantity = isset($cart[$productoId]) ? $cart[$productoId]['cantidad'] : 0;

        $maxPermitido = min(5, $stockDisponible);

        if (($currentQuantity + $cantidad) > $maxPermitido) {
            if ($stockDisponible < 5) {
                return back()->with('error', "Stock insuficiente. Solo puedes tener hasta {$stockDisponible} unidades de este producto.");
            } else {
                return back()->with('error', "No puedes agregar más de 5 unidades del mismo producto al carrito.");
            }
        }

        $precioFinal = $variante ? (float) $variante->precio : 0;

        // Si el producto ya está en el carrito, sumamos la cantidad
        if (isset($cart[$productoId])) {
            $cart[$productoId]['cantidad'] += $cantidad;
            $cart[$productoId]['precio'] = $precioFinal; // Actualizar precio por si cambió
        } else {
            // Si no está, lo agregamos
            $cart[$productoId] = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'cantidad' => $cantidad,
                'precio' => $precioFinal,
                'imagen' => $imagen ? $imagen->url : null,
                'variante_id' => $variante ? $variante->id : null
            ];
        }

        session()->put('cart', $cart);
        $this->syncCartToDB($cart);
        
        if ($variante) {
            $this->syncReserva($variante->id, $cart[$productoId]['cantidad']);
        }

        return back()->with('success', 'Producto agregado al carrito exitosamente.');
    }

    /**
     * Actualiza la cantidad de un producto en el carrito.
     */
    public function update(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer',
            'cantidad'    => 'required|integer|min:1'
        ]);

        $cart = session()->get('cart', []);
        $productoId = $request->producto_id;

        if (isset($cart[$productoId])) {
            $varianteId = $cart[$productoId]['variante_id'] ?? null;
            $stockDisponible = 0;
            if ($varianteId) {
                $almacenEcommerceId = \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
                $variante = \App\Models\Variante::find($varianteId);
                
                $stockActual = 0;
                if ($variante) {
                    $stockAlmacen = \Illuminate\Support\Facades\DB::table('stock_almacen')
                        ->where('almacen_id', $almacenEcommerceId)
                        ->where('variante_id', $variante->id)
                        ->first();
                    $stockActual = $stockAlmacen ? $stockAlmacen->cantidad : 0;
                }
                $stockReservado = 0;
                if ($variante) {
                    $stockReservado = \App\Models\ReservaStock::where('variante_id', $variante->id)
                        ->where('session_id', '!=', session()->getId())
                        ->where('expires_at', '>', now())
                        ->sum('cantidad');
                }
                $stockDisponible = max(0, $stockActual - $stockReservado);
                $maxPermitido = min(5, $stockDisponible);

                $nuevaCantidad = min((int)$request->cantidad, $maxPermitido);
            } else {
                $nuevaCantidad = (int)$request->cantidad;
            }

            $cart[$productoId]['cantidad'] = $nuevaCantidad;
            session()->put('cart', $cart);
            $this->syncCartToDB($cart);
            
            if ($varianteId) {
                $this->syncReserva($varianteId, $nuevaCantidad);
            }
            
            return back()->with('success', 'Carrito actualizado.');
        }

        return back()->with('error', 'El producto no se encontró en el carrito.');
    }

    /**
     * Elimina un producto específico del carrito.
     */
    public function remove(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|integer'
        ]);

        $cart = session()->get('cart', []);
        $productoId = $request->producto_id;

        if (isset($cart[$productoId])) {
            $varianteId = $cart[$productoId]['variante_id'] ?? null;
            unset($cart[$productoId]);
            session()->put('cart', $cart);
            $this->syncCartToDB($cart);
            
            if ($varianteId) {
                $this->syncReserva($varianteId, 0);
            }
        }

        return back()->with('success', 'Producto eliminado del carrito.');
    }

    /**
     * Vacía completamente el carrito.
     */
    public function clear()
    {
        session()->forget('cart');
        $this->syncCartToDB([]);
        \App\Models\ReservaStock::where('session_id', session()->getId())->delete();
        return back()->with('success', 'El carrito ha sido vaciado.');
    }

    private function syncReserva($varianteId, $cantidad)
    {
        if (!$varianteId) return;
        $sessionId = session()->getId();
        if ($cantidad > 0) {
            \App\Models\ReservaStock::updateOrCreate(
                ['session_id' => $sessionId, 'variante_id' => $varianteId],
                ['cantidad' => $cantidad, 'expires_at' => now()->addMinutes(15)]
            );
        } else {
            \App\Models\ReservaStock::where('session_id', $sessionId)
                ->where('variante_id', $varianteId)
                ->delete();
        }
    }
}
