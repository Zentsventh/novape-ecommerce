<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class AlmacenController extends Controller
{
    public function index()
    {
        $almacenes = DB::table('almacenes')->orderBy('id', 'asc')->get();
        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        // Get stock summary per almacen
        $stockPorAlmacen = DB::table('stock_almacen')
            ->select('almacen_id', DB::raw('SUM(cantidad) as total_unidades'), DB::raw('COUNT(DISTINCT variante_id) as total_skus'))
            ->groupBy('almacen_id')
            ->get()
            ->keyBy('almacen_id');

        foreach ($almacenes as $almacen) {
            $almacen->total_unidades = $stockPorAlmacen[$almacen->id]->total_unidades ?? 0;
            $almacen->total_skus = $stockPorAlmacen[$almacen->id]->total_skus ?? 0;
        }

        // Get all products for transfer
        $productos = DB::table('producto')
            ->join('variante', 'variante.producto_id', '=', 'producto.id')
            ->select('producto.nombre', 'variante.id as variante_id', 'variante.sku', 'variante.stock')
            ->where('producto.activo', true)
            ->orderBy('producto.nombre')
            ->get();

        return Inertia::render('Admin/Almacenes/Index', [
            'almacenes' => $almacenes,
            'productos' => $productos,
            'logoUrl' => $logoUrl
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'activo' => 'boolean'
        ]);

        DB::table('almacenes')->insert([
            'nombre' => $request->nombre,
            'direccion' => $request->direccion,
            'activo' => $request->activo ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Almacén registrado correctamente.');
    }

    public function destroy($id)
    {
        // First check if there is stock
        $stock = DB::table('stock_almacen')->where('almacen_id', $id)->sum('cantidad');
        if ($stock > 0) {
            return redirect()->back()->with('error', 'No puedes eliminar un almacén con stock. Transfiere los productos primero.');
        }

        DB::table('stock_almacen')->where('almacen_id', $id)->delete();
        DB::table('movimientos_almacen')->where('almacen_id', $id)->delete();
        DB::table('almacenes')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Almacén eliminado.');
    }

    public function kardex($id)
    {
        $almacen = DB::table('almacenes')->where('id', $id)->first();
        if (!$almacen) abort(404);

        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        $movimientos = DB::table('movimientos_almacen')
            ->join('variante', 'movimientos_almacen.variante_id', '=', 'variante.id')
            ->join('producto', 'variante.producto_id', '=', 'producto.id')
            ->leftJoin('almacenes as destino', 'movimientos_almacen.almacen_destino_id', '=', 'destino.id')
            ->select(
                'movimientos_almacen.*',
                'producto.nombre as producto_nombre',
                'variante.sku',
                'destino.nombre as destino_nombre'
            )
            ->where('movimientos_almacen.almacen_id', $id)
            ->orderBy('movimientos_almacen.created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Admin/Almacenes/Kardex', [
            'almacen' => $almacen,
            'movimientos' => $movimientos,
            'logoUrl' => $logoUrl
        ]);
    }

    public function transferir(Request $request)
    {
        $request->validate([
            'almacen_origen_id' => 'required|exists:almacenes,id',
            'almacen_destino_id' => 'required|exists:almacenes,id|different:almacen_origen_id',
            'variante_id' => 'required|exists:variante,id',
            'cantidad' => 'required|integer|min:1',
            'referencia' => 'nullable|string'
        ]);

        $origenId = $request->almacen_origen_id;
        $destinoId = $request->almacen_destino_id;
        $varianteId = $request->variante_id;
        $cantidad = $request->cantidad;

        DB::beginTransaction();
        try {
            // Check stock in origin
            $stockOrigen = DB::table('stock_almacen')
                ->where('almacen_id', $origenId)
                ->where('variante_id', $varianteId)
                ->lockForUpdate()
                ->first();

            if (!$stockOrigen || $stockOrigen->cantidad < $cantidad) {
                throw new \Exception("Stock insuficiente en el almacén de origen.");
            }

            // Deduct from origin
            DB::table('stock_almacen')->where('id', $stockOrigen->id)->decrement('cantidad', $cantidad);

            // Add to destination
            $stockDestino = DB::table('stock_almacen')
                ->where('almacen_id', $destinoId)
                ->where('variante_id', $varianteId)
                ->lockForUpdate()
                ->first();

            if ($stockDestino) {
                DB::table('stock_almacen')->where('id', $stockDestino->id)->increment('cantidad', $cantidad);
            } else {
                DB::table('stock_almacen')->insert([
                    'almacen_id' => $destinoId,
                    'variante_id' => $varianteId,
                    'cantidad' => $cantidad,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Record movement in Kardex (Salida from origen)
            DB::table('movimientos_almacen')->insert([
                'almacen_id' => $origenId,
                'variante_id' => $varianteId,
                'tipo' => 'transferencia',
                'cantidad' => -$cantidad,
                'referencia' => $request->referencia ?? 'Transferencia manual',
                'almacen_destino_id' => $destinoId,
                'usuario_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Record movement in Kardex (Entrada to destino)
            DB::table('movimientos_almacen')->insert([
                'almacen_id' => $destinoId,
                'variante_id' => $varianteId,
                'tipo' => 'entrada',
                'cantidad' => $cantidad,
                'referencia' => 'Transferencia desde almacén ID: ' . $origenId . ' - ' . ($request->referencia ?? ''),
                'usuario_id' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return redirect()->back()->with('success', 'Transferencia realizada correctamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
