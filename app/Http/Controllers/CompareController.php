<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Producto;
use App\Models\ConfiguracionSitio;

class CompareController extends Controller
{
    public function index()
    {
        $compareIds = session('compare', []);

        $productos = collect();
        if (count($compareIds) > 0) {
            $productos = Producto::whereIn('id', $compareIds)
                ->with(['marca', 'imagenes', 'variantes', 'productoEspecificaciones.especificacion'])
                ->get()
                ->map(function ($prod) {
                    $variante = $prod->variantes->first();
                    return [
                        'id' => $prod->id,
                        'nombre' => $prod->nombre,
                        'marca' => $prod->marca ? $prod->marca->nombre : 'Genérico',
                        'precio' => $variante ? (float) $variante->precio : 0,
                        'imagen' => $prod->imagenes->first() ? $prod->imagenes->first()->url : null,
                        'descripcion' => $prod->descripcion,
                        'especificaciones' => $prod->productoEspecificaciones->map(function ($pe) {
                            return [
                                'nombre' => $pe->especificacion->nombre,
                                'valor' => $pe->valor
                            ];
                        })->toArray()
                    ];
                });
        }

        // Obtener todas las especificaciones únicas entre los productos para armar la tabla
        $todasEspecificaciones = [];
        foreach ($productos as $prod) {
            foreach ($prod['especificaciones'] as $esp) {
                if (!in_array($esp['nombre'], $todasEspecificaciones)) {
                    $todasEspecificaciones[] = $esp['nombre'];
                }
            }
        }

        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        return Inertia::render('Comparador', [
            'productos' => $productos,
            'especificacionesUnicas' => $todasEspecificaciones,
            'logoUrl' => $logoUrl
        ]);
    }

    public function add(Request $request)
    {
        $id = $request->input('producto_id');
        $compare = session('compare', []);

        if (count($compare) >= 4) {
            return back()->with('error', 'Solo puedes comparar hasta 4 productos a la vez.');
        }

        if (!in_array($id, $compare)) {
            $compare[] = $id;
            session(['compare' => $compare]);
            return back()->with('success', 'Producto agregado al comparador.');
        }

        return back()->with('success', 'El producto ya está en el comparador.');
    }

    public function remove(Request $request)
    {
        $id = $request->input('producto_id');
        $compare = session('compare', []);
        
        if (($key = array_search($id, $compare)) !== false) {
            unset($compare[$key]);
            session(['compare' => array_values($compare)]);
        }

        return back()->with('success', 'Producto removido del comparador.');
    }

    public function clear()
    {
        session()->forget('compare');
        return back()->with('success', 'Comparador limpiado.');
    }
}
