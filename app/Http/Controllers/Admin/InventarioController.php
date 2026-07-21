<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class InventarioController extends Controller
{
    public function dashboard()
    {
        $logoUrl = ConfiguracionSitio::obtener('logo_url');
        $currentMonth = date('m');
        $currentYear = date('Y');

        // Ventas totales por variante (POS + E-commerce)
        $ventasPorVariante = DB::query()
            ->fromSub(function($query) {
                $query->select('variante_id', 'cantidad')
                      ->from('venta_pos_items')
                      ->unionAll(
                          DB::table('pedido_item')
                            ->join('pedido', 'pedido.id', '=', 'pedido_item.pedido_id')
                            ->where('pedido.estado', 'completado')
                            ->select('variante_id', 'cantidad')
                      );
            }, 'ventas_combinadas')
            ->selectRaw('variante_id, SUM(cantidad) as total_vendido')
            ->groupBy('variante_id')
            ->pluck('total_vendido', 'variante_id');

        // Compras totales por variante
        $comprasPorVariante = DB::table('compra_items')
            ->join('compras', 'compra_items.compra_id', '=', 'compras.id')
            ->where('compras.estado', 'completado')
            ->selectRaw('variante_id, SUM(compra_items.cantidad) as total_comprado')
            ->groupBy('variante_id')
            ->pluck('total_comprado', 'variante_id');

        // Demanda diaria por variante (últimos 15 días, POS + E-commerce)
        $demandaRaw = DB::query()
            ->fromSub(function($query) {
                $query->select('variante_id', DB::raw('DATE(created_at) as fecha'), 'cantidad')
                      ->from('venta_pos_items')
                      ->where('created_at', '>=', \Carbon\Carbon::now()->subDays(15))
                      ->unionAll(
                          DB::table('pedido_item')
                            ->join('pedido', 'pedido.id', '=', 'pedido_item.pedido_id')
                            ->where('pedido.estado', 'completado')
                            ->where('pedido_item.created_at', '>=', \Carbon\Carbon::now()->subDays(15))
                            ->select('variante_id', DB::raw('DATE(pedido_item.created_at) as fecha'), 'cantidad')
                      );
            }, 'demanda_combinada')
            ->selectRaw('variante_id, fecha, SUM(cantidad) as cantidad')
            ->groupBy('variante_id', 'fecha')
            ->get();

        // Productos / Variantes con su info (ÚNICOS, sin duplicar por categorías)
        $productos = \App\Models\Variante::with(['producto.categorias', 'producto.marca'])
            ->whereHas('producto', function($q) {
                $q->where('activo', true);
            })
            ->get()
            ->map(function($v) use ($ventasPorVariante, $comprasPorVariante) {
                // Buscamos la categoría principal (raíz) para la vista del dashboard
                $catPadre = $v->producto->categorias->whereNull('categoria_padre_id')->first();
                $cat = $catPadre ?? $v->producto->categorias->first();

                return [
                    'id' => $v->id,
                    'producto_nombre' => $v->producto->nombre,
                    'producto_id' => $v->producto->id,
                    'sku' => $v->sku,
                    'stock' => $v->stock,
                    'precio' => $v->precio,
                    'precio_compra' => $v->precio_compra,
                    'stock_minimo' => $v->stock_minimo,
                    'stock_maximo' => $v->stock_maximo,
                    'stock_seguridad' => $v->stock_seguridad,
                    'categoria' => $cat ? $cat->nombre : 'Sin Categoría',
                    'categoria_id' => $cat ? $cat->id : null,
                    'marca' => $v->producto->marca ? $v->producto->marca->nombre : 'Sin Marca',
                    'marca_id' => $v->producto->marca ? $v->producto->marca->id : null,
                    'unidades_vendidas' => $ventasPorVariante->get($v->id, 0),
                    'unidades_compradas' => $comprasPorVariante->get($v->id, 0),
                ];
            });

        // Obtener TODAS las categorías activas principales (raíz) para el filtro
        $categorias = DB::table('categoria')
            ->where('activa', true)
            ->whereNull('categoria_padre_id')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Admin/Inventario/Dashboard', [
            'logoUrl' => $logoUrl,
            'productos' => $productos,
            'categorias' => $categorias,
            'demandaRaw' => $demandaRaw, // Raw data para que React agrupe
            'usuario_nombre' => auth()->user()->nombre ?? 'Administrador',
        ]);
    }
}
