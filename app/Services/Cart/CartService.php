<?php

declare(strict_types=1);

namespace App\Services\Cart;

use App\Models\CarritoItem;
use App\Models\ConfiguracionSitio;
use App\Models\ReservaStock;
use App\Models\Variante;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function syncCartToDB(array $cart, string $sessionId): void
    {
        if (auth()->check()) {
            $user = auth()->user();
            $carrito = $user->carrito()->firstOrCreate(['session_id' => $sessionId]);
            
            if (empty($cart)) {
                $carrito->items()->delete();
                return;
            }

            $variantesActuales = [];
            foreach ($cart as $item) {
                if (isset($item['variante_id'])) {
                    $variantesActuales[] = $item['variante_id'];
                    CarritoItem::updateOrCreate(
                        ['carrito_id' => $carrito->id, 'variante_id' => $item['variante_id']],
                        ['cantidad' => $item['cantidad']]
                    );
                }
            }
            
            $carrito->items()->whereNotIn('variante_id', $variantesActuales)->delete();
        }
    }

    public function syncReserva(?int $varianteId, int $cantidad, string $sessionId): void
    {
        if (!$varianteId) return;
        
        if ($cantidad > 0) {
            ReservaStock::updateOrCreate(
                ['session_id' => $sessionId, 'variante_id' => $varianteId],
                ['cantidad' => $cantidad, 'expires_at' => now()->addMinutes(15)]
            );
        } else {
            ReservaStock::where('session_id', $sessionId)
                ->where('variante_id', $varianteId)
                ->delete();
        }
    }

    public function getStockDisponible(?Variante $variante, string $sessionId): int
    {
        if (!$variante) return 0;

        $almacenEcommerceId = (int) ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
        
        $stockAlmacen = DB::table('stock_almacen')
            ->where('almacen_id', $almacenEcommerceId)
            ->where('variante_id', $variante->id)
            ->first();
            
        $stockActual = $stockAlmacen ? (int)$stockAlmacen->cantidad : 0;

        $stockReservado = (int) ReservaStock::where('variante_id', $variante->id)
            ->where('session_id', '!=', $sessionId)
            ->where('expires_at', '>', now())
            ->sum('cantidad');

        return max(0, $stockActual - $stockReservado);
    }

    public function mergeSessionAndDbCart(array $sessionCart, \App\Models\Usuario $user, string $sessionId): void
    {
        $carrito = $user->carrito()->firstOrCreate(['session_id' => $sessionId]);
        $dbItems = $carrito->items()->with(['variante.producto.imagenes'])->get();

        $dbCart = [];
        foreach ($dbItems as $dbItem) {
            $variante = $dbItem->variante;
            $producto = $variante ? $variante->producto : null;
            $imagen = $producto && $producto->imagenes->first() ? $producto->imagenes->first()->url : null;
            if ($producto) {
                $dbCart[$producto->id] = [
                    'id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'cantidad' => $dbItem->cantidad,
                    'precio' => (float)$variante->precio,
                    'imagen' => $imagen,
                    'variante_id' => $variante->id
                ];
            }
        }
        
        if (empty($sessionCart) && !empty($dbCart)) {
            session()->put('cart', $dbCart);
        } elseif (!empty($sessionCart)) {
            $mergedCart = $dbCart;
            foreach ($sessionCart as $key => $item) {
                if (isset($mergedCart[$key])) {
                    $mergedCart[$key]['cantidad'] += $item['cantidad'];
                } else {
                    $mergedCart[$key] = $item;
                }
            }
            session()->put('cart', $mergedCart);
            $this->syncCartToDB($mergedCart, $sessionId);
        }
    }
}
