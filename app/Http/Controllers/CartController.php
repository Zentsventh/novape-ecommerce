<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartRequest;
use App\Http\Requests\Cart\RemoveCartRequest;
use App\Models\Producto;
use App\Models\ReservaStock;
use App\Models\Variante;
use App\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly CartService $cartService
    ) {}

    public function add(AddToCartRequest $request): RedirectResponse
    {
        $productoId = (int) $request->input('producto_id');
        $cantidad = (int) $request->input('cantidad');
        
        $producto = Producto::with(['imagenes', 'variantes'])->findOrFail($productoId);
        $imagen = $producto->imagenes->first();
        $variante = $producto->variantes->first();
        
        $sessionId = session()->getId();
        $stockDisponible = $this->cartService->getStockDisponible($variante, $sessionId);

        $cart = session()->get('cart', []);
        $currentQuantity = isset($cart[$productoId]) ? (int) $cart[$productoId]['cantidad'] : 0;
        $maxPermitido = min(5, $stockDisponible);

        if (($currentQuantity + $cantidad) > $maxPermitido) {
            if ($stockDisponible < 5) {
                return back()->with('error', "Stock insuficiente. Solo puedes tener hasta {$stockDisponible} unidades de este producto.");
            }
            return back()->with('error', "No puedes agregar más de 5 unidades del mismo producto al carrito.");
        }

        $precioFinal = $variante ? (float) $variante->precio : 0.0;

        if (isset($cart[$productoId])) {
            $cart[$productoId]['cantidad'] += $cantidad;
            $cart[$productoId]['precio'] = $precioFinal;
        } else {
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
        $this->cartService->syncCartToDB($cart, $sessionId);
        
        if ($variante) {
            $this->cartService->syncReserva($variante->id, $cart[$productoId]['cantidad'], $sessionId);
        }

        return back()->with('success', 'Producto agregado al carrito exitosamente.');
    }

    public function update(UpdateCartRequest $request): RedirectResponse
    {
        $cart = session()->get('cart', []);
        $productoId = (int) $request->input('producto_id');
        $cantidadSolicitada = (int) $request->input('cantidad');

        if (!isset($cart[$productoId])) {
            return back()->with('error', 'El producto no se encontró en el carrito.');
        }

        $varianteId = $cart[$productoId]['variante_id'] ?? null;
        $sessionId = session()->getId();
        
        if ($varianteId) {
            $variante = Variante::find($varianteId);
            $stockDisponible = $this->cartService->getStockDisponible($variante, $sessionId);
            $maxPermitido = min(5, $stockDisponible);
            $nuevaCantidad = min($cantidadSolicitada, $maxPermitido);
        } else {
            $nuevaCantidad = $cantidadSolicitada;
        }

        $cart[$productoId]['cantidad'] = $nuevaCantidad;
        session()->put('cart', $cart);
        $this->cartService->syncCartToDB($cart, $sessionId);
        
        if ($varianteId) {
            $this->cartService->syncReserva($varianteId, $nuevaCantidad, $sessionId);
        }
        
        return back()->with('success', 'Carrito actualizado.');
    }

    public function remove(RemoveCartRequest $request): RedirectResponse
    {

        $cart = session()->get('cart', []);
        $productoId = (int) $request->input('producto_id');

        if (isset($cart[$productoId])) {
            $varianteId = $cart[$productoId]['variante_id'] ?? null;
            unset($cart[$productoId]);
            session()->put('cart', $cart);
            
            $sessionId = session()->getId();
            $this->cartService->syncCartToDB($cart, $sessionId);
            
            if ($varianteId) {
                $this->cartService->syncReserva($varianteId, 0, $sessionId);
            }
        }

        return back()->with('success', 'Producto eliminado del carrito.');
    }

    public function clear(): RedirectResponse
    {
        session()->forget('cart');
        $sessionId = session()->getId();
        
        $this->cartService->syncCartToDB([], $sessionId);
        ReservaStock::where('session_id', $sessionId)->delete();
        
        return back()->with('success', 'El carrito ha sido vaciado.');
    }
}
