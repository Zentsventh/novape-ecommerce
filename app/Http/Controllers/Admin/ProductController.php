<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\Marca;
use App\Models\ProductoImagen;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with(['marca', 'imagenes', 'variantes', 'categorias']);

        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('sku_base', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('categoria_id') && $request->categoria_id != '') {
            $query->whereHas('categorias', function($q) use ($request) {
                $q->where('categoria.id', $request->categoria_id);
            });
        }

        if ($request->has('marca_id') && $request->marca_id != '') {
            $query->where('marca_id', $request->marca_id);
        }

        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $query->orderBy($sort, $direction);

        $productos = $query->paginate(10)->withQueryString();

        $categoriasQuery = \App\Models\Categoria::where('activa', true)->whereNull('categoria_padre_id');
        if ($request->has('marca_id') && $request->marca_id != '') {
            $categoriasQuery->whereHas('productos', function($q) use ($request) {
                $q->where('marca_id', $request->marca_id);
            });
        }
        $categorias = $categoriasQuery->orderBy('nombre')->get();

        $marcasQuery = \App\Models\Marca::query();
        if ($request->has('categoria_id') && $request->categoria_id != '') {
            $marcasQuery->whereHas('productos', function($q) use ($request) {
                $q->whereHas('categorias', function($q2) use ($request) {
                    $q2->where('categoria.id', $request->categoria_id);
                });
            });
        }
        $marcas = $marcasQuery->orderBy('nombre')->get();

        return Inertia::render('Admin/Products/Index', [
            'productos' => $productos,
            'categorias' => $categorias,
            'marcas' => $marcas,
            'filters' => (object) $request->only(['search', 'categoria_id', 'marca_id', 'sort', 'direction'])
        ]);
    }

    public function create()
    {
        $marcas = Marca::all();
        $categorias = Categoria::all();
        $proveedores = \App\Models\Proveedor::where('activo', true)->get();

        return Inertia::render('Admin/Products/Form', [
            'marcas' => $marcas,
            'categorias' => $categorias,
            'proveedores' => $proveedores,
            'costoPromedio' => 0,
            'listaEspecificaciones' => []
        ]);
    }

    public function store(\App\Http\Requests\StoreProductRequest $request)
    {
        $validated = $request->validated();

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated) {
            $producto = Producto::create([
                'nombre' => $validated['nombre'],
                'marca_id' => $validated['marca_id'],
                'proveedor_id' => $validated['proveedor_id'] ?? null,
                'sku_base' => $validated['sku_base'],
                'descripcion' => $validated['descripcion'] ?? null,
                'garantias' => $validated['garantias'] ?? null,
                'activo' => $validated['activo'] ?? true,
            ]);



        $variante = \App\Models\Variante::create([
            'producto_id' => $producto->id,
            'sku' => $validated['sku_base'] ?? ('SKU-' . $producto->id),
            'precio' => $validated['precio'],
            'peso' => $validated['peso_kg'] ?? 1.0,
            'stock' => 0, // Se recalcula inmediatamente
            'activo' => true
        ]);
        
        $stockInicial = $validated['stock'];
        if ($stockInicial > 0) {
            $almacenEcommerceId = \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
            \Illuminate\Support\Facades\DB::table('stock_almacen')->insert([
                'almacen_id' => $almacenEcommerceId,
                'variante_id' => $variante->id,
                'cantidad' => $stockInicial,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::table('movimientos_almacen')->insert([
                'almacen_id' => $almacenEcommerceId,
                'variante_id' => $variante->id,
                'tipo' => 'ajuste',
                'cantidad' => $stockInicial,
                'referencia' => 'Ajuste inicial al crear producto',
                'usuario_id' => auth()->id() ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::statement("UPDATE variante SET stock = (SELECT COALESCE(SUM(cantidad), 0) FROM stock_almacen WHERE variante_id = ?) WHERE id = ?", [$variante->id, $variante->id]);
        }

        if (!empty($validated['categorias'])) {
            $producto->categorias()->sync($validated['categorias']);
        }

        if (!empty($validated['imagenes'])) {
            $manager = new ImageManager(new Driver());
            foreach ($validated['imagenes'] as $i => $imageItem) {
                $url = '';
                if ($imageItem instanceof \Illuminate\Http\UploadedFile) {
                    // Procesar la imagen subida
                    $filename = Str::uuid() . '.webp';
                    $image = $manager->read($imageItem->getPathname());
                    $encoded = $image->toWebp(80);
                    Storage::disk('public')->put('productos/' . $filename, $encoded->toString());
                    $url = 'productos/' . $filename;
                } else if (is_string($imageItem)) {
                    // Es una URL o string (como antes)
                    $url = $imageItem;
                }

                if (!empty($url)) {
                    ProductoImagen::create([
                        'producto_id' => $producto->id,
                        'url' => $url,
                        'orden' => $i,
                    ]);
                }
            }
        }

        if (isset($validated['especificaciones'])) {
            foreach ($validated['especificaciones'] as $espec) {
                \App\Models\ProductoEspecificacion::create([
                    'producto_id' => $producto->id,
                    'clave' => $espec['nombre'],
                    'valor' => $espec['valor'],
                ]);
            }
        }
        });

        return redirect()->route('admin.products')->with('success', 'Producto creado exitosamente.');
    }

    public function edit($id)
    {
        $producto = Producto::with(['categorias', 'imagenes', 'productoEspecificaciones', 'variantes'])->findOrFail($id);
        $marcas = Marca::all();
        $categorias = Categoria::all();
        $proveedores = \App\Models\Proveedor::where('activo', true)->get();

        return Inertia::render('Admin/Products/Form', [
            'producto' => $producto,
            'marcas' => $marcas,
            'categorias' => $categorias,
            'proveedores' => $proveedores,
            'listaEspecificaciones' => []
        ]);
    }

    public function show($id)
    {
        $producto = Producto::with(['categorias', 'imagenes', 'productoEspecificaciones', 'variantes', 'marca', 'proveedor'])->findOrFail($id);
        
        $costoPromedio = \Illuminate\Support\Facades\DB::table('compra_items')
            ->where('producto_id', $id)
            ->avg('costo_unitario') ?? 0;

        $historialCompras = \Illuminate\Support\Facades\DB::table('compra_items')
            ->join('compras', 'compra_items.compra_id', '=', 'compras.id')
            ->leftJoin('proveedor', 'compras.proveedor_id', '=', 'proveedor.id')
            ->where('compra_items.producto_id', $id)
            ->where('compras.estado', 'completado')
            ->select(
                'proveedor.id as proveedor_id',
                'proveedor.nombre as proveedor_nombre',
                'compras.numero_orden',
                'compras.fecha_compra',
                'compra_items.cantidad',
                'compra_items.costo_unitario',
                'compra_items.subtotal'
            )
            ->orderBy('compras.fecha_compra', 'desc')
            ->get();

        $comparativaProveedores = [];
        foreach($historialCompras as $hc) {
            $pid = $hc->proveedor_id ?? 0;
            if (!isset($comparativaProveedores[$pid])) {
                $comparativaProveedores[$pid] = [
                    'proveedor_id' => $pid,
                    'proveedor_nombre' => $hc->proveedor_nombre ?? 'Sin proveedor registrado',
                    'ultima_compra' => $hc->fecha_compra,
                    'ultimo_costo' => $hc->costo_unitario,
                    'total_unidades' => 0,
                    'frecuencia' => 0,
                ];
            }
            $comparativaProveedores[$pid]['total_unidades'] += $hc->cantidad;
            $comparativaProveedores[$pid]['frecuencia'] += 1;
        }

        return Inertia::render('Admin/Products/Show', [
            'producto' => $producto,
            'costoPromedio' => (float) $costoPromedio,
            'historialCompras' => $historialCompras,
            'comparativaProveedores' => array_values($comparativaProveedores),
        ]);
    }

    public function update(\App\Http\Requests\UpdateProductRequest $request, $id)
    {
        $producto = Producto::findOrFail($id);
        $validated = $request->validated();

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $producto) {
            $producto->update([
                'nombre' => $validated['nombre'],
                'marca_id' => $validated['marca_id'],
                'proveedor_id' => $validated['proveedor_id'] ?? null,
                'sku_base' => $validated['sku_base'],
                'descripcion' => $validated['descripcion'] ?? null,
                'garantias' => $validated['garantias'] ?? null,
                'activo' => $validated['activo'] ?? true,
            ]);



        $variante = \App\Models\Variante::where('producto_id', $producto->id)->first();
        $nuevoStock = $validated['stock'];
        $diferencia = 0;
        
        if ($variante) {
            $diferencia = $nuevoStock - $variante->stock;
            $variante->update([
                'sku' => $validated['sku_base'] ?? $variante->sku,
                'precio' => $validated['precio'],
                'peso' => $validated['peso_kg'] ?? 1.0,
                // stock recalculado luego
            ]);
        } else {
            $variante = \App\Models\Variante::create([
                'producto_id' => $producto->id,
                'sku' => $validated['sku_base'] ?? ('SKU-' . $producto->id),
                'precio' => $validated['precio'],
                'peso' => $validated['peso_kg'] ?? 1.0,
                'stock' => 0,
                'activo' => true
            ]);
            $diferencia = $nuevoStock;
        }

        if ($diferencia != 0) {
            $almacenEcommerceId = \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
            $stockAlmacen = \Illuminate\Support\Facades\DB::table('stock_almacen')
                ->where('almacen_id', $almacenEcommerceId)
                ->where('variante_id', $variante->id)
                ->first();
            
            if ($stockAlmacen) {
                \Illuminate\Support\Facades\DB::table('stock_almacen')->where('id', $stockAlmacen->id)->increment('cantidad', $diferencia);
            } else {
                \Illuminate\Support\Facades\DB::table('stock_almacen')->insert([
                    'almacen_id' => $almacenEcommerceId,
                    'variante_id' => $variante->id,
                    'cantidad' => $diferencia,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            \Illuminate\Support\Facades\DB::table('movimientos_almacen')->insert([
                'almacen_id' => $almacenEcommerceId,
                'variante_id' => $variante->id,
                'tipo' => 'ajuste',
                'cantidad' => $diferencia,
                'referencia' => 'Ajuste manual desde edición de producto',
                'usuario_id' => auth()->id() ?? 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            \Illuminate\Support\Facades\DB::statement("UPDATE variante SET stock = (SELECT COALESCE(SUM(cantidad), 0) FROM stock_almacen WHERE variante_id = ?) WHERE id = ?", [$variante->id, $variante->id]);
        }

        if (isset($validated['categorias'])) {
            $producto->categorias()->sync($validated['categorias']);
        }

        if (isset($validated['imagenes'])) {
            $manager = new ImageManager(new Driver());
            $producto->imagenes()->delete();
            foreach ($validated['imagenes'] as $i => $imageItem) {
                $url = '';
                if ($imageItem instanceof \Illuminate\Http\UploadedFile) {
                    // Procesar la imagen subida
                    $filename = Str::uuid() . '.webp';
                    $image = $manager->read($imageItem->getPathname());
                    $encoded = $image->toWebp(80);
                    Storage::disk('public')->put('productos/' . $filename, $encoded->toString());
                    $url = 'productos/' . $filename;
                } else if (is_string($imageItem)) {
                    // Mantener la URL o string si ya existia o viene de DB
                    // Cuando viene del frontend, para mantener una imagen existente, puede venir como `/storage/productos/...`
                    // Lo guardamos tal cual (aunque podriamos limpiar `/storage/` si estuviese presente)
                    $url = str_replace('/storage/', '', $imageItem);
                }

                if (!empty($url)) {
                    ProductoImagen::create([
                        'producto_id' => $producto->id,
                        'url' => $url,
                        'orden' => $i,
                    ]);
                }
            }
        }

        if (isset($validated['especificaciones'])) {
            \App\Models\ProductoEspecificacion::where('producto_id', $producto->id)->delete();
            foreach ($validated['especificaciones'] as $espec) {
                \App\Models\ProductoEspecificacion::create([
                    'producto_id' => $producto->id,
                    'clave' => $espec['nombre'],
                    'valor' => $espec['valor'],
                ]);
            }
        }
        });

        return redirect()->route('admin.products')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy($id)
    {
        $producto = Producto::findOrFail($id);
        $producto->imagenes()->delete();
        $producto->variantes()->delete();
        $producto->delete();
        return redirect()->route('admin.products')->with('success', 'Producto eliminado exitosamente.');
    }
    
    public function export()
    {
        $productos = Producto::with(['marca', 'variantes', 'categorias'])->get();

        $csv = "ID,Nombre,Marca,Categoría,SKU,Stock,Precio,Estado\n";
        foreach ($productos as $p) {
            $stock = 0;
            foreach ($p->variantes as $v) {
                $stock += $v->stock;
            }
            $precio = $p->variantes->first() ? $p->variantes->first()->precio : 0;
            $csv .= implode(',', [
                $p->id,
                '"' . str_replace('"', '""', $p->nombre) . '"',
                '"' . ($p->marca ? $p->marca->nombre : '') . '"',
                '"' . ($p->categorias->first() ? $p->categorias->first()->nombre : '') . '"',
                $p->sku_base,
                $stock,
                $precio,
                $p->activo ? 'Activo' : 'Inactivo',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="productos_' . date('Y-m-d') . '.csv"',
        ]);
    }
}
