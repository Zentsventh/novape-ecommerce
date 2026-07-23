<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ListaDeseoController extends Controller
{
    public function storeLista(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'es_publica' => 'boolean'
        ]);

        $usuario = \Auth::user();
        $usuario->listas()->create([
            'nombre' => $request->nombre,
            'es_publica' => $request->es_publica ?? false
        ]);

        return back()->with('success', 'Lista creada exitosamente.');
    }

    public function destroyLista($id)
    {
        $usuario = \Auth::user();
        $lista = $usuario->listas()->findOrFail($id);
        $lista->delete();

        return back()->with('success', 'Lista eliminada.');
    }

    public function storeListaItem(Request $request)
    {
        $request->validate([
            'lista_id' => 'required|exists:usuario_listas,id',
            'producto_id' => 'required|exists:productos,id',
        ]);

        $usuario = \Auth::user();
        $lista = $usuario->listas()->findOrFail($request->lista_id);
        
        $lista->items()->firstOrCreate([
            'producto_id' => $request->producto_id
        ]);

        return back()->with('success', 'Producto agregado a la lista.');
    }

    public function destroyListaItem($id)
    {
        $usuario = \Auth::user();
        
        $item = \App\Models\UsuarioListaItem::whereHas('lista', function($q) use ($usuario) {
            $q->where('usuario_id', $usuario->id);
        })->findOrFail($id);

        $item->delete();

        return back()->with('success', 'Producto eliminado de la lista.');
    }

    public function getLists()
    {
        $usuario = \Auth::user();
        if (!$usuario) return response()->json([]);

        $listas = $usuario->listas()->with('items')->get();
        return response()->json($listas);
    }

    public function syncWishlists(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:producto,id',
            'lista_ids' => 'array',
            'lista_ids.*' => 'exists:usuario_listas,id'
        ]);

        $usuario = \Auth::user();
        $producto_id = $request->producto_id;
        $nuevas_listas_ids = $request->lista_ids ?? [];

        // Obtener todas las listas del usuario
        $mis_listas = $usuario->listas()->pluck('id')->toArray();

        // Eliminar el producto de las listas no seleccionadas, y agregarlo a las seleccionadas
        foreach ($mis_listas as $lista_id) {
            $lista = $usuario->listas()->find($lista_id);
            if (!$lista) continue;

            $item = $lista->items()->where('producto_id', $producto_id)->first();
            $debe_estar = in_array($lista_id, $nuevas_listas_ids);

            if ($debe_estar && !$item) {
                $lista->items()->create(['producto_id' => $producto_id]);
            } elseif (!$debe_estar && $item) {
                $item->delete();
            }
        }

        return back()->with('success', 'Listas guardadas exitosamente.');
    }

    public function toggleWishlist(Request $request)
    {
        $request->validate([
            'producto_id' => 'required|exists:producto,id',
            'lista_id' => 'nullable|exists:usuario_listas,id'
        ]);
        $usuario = \Auth::user();
        
        $lista = null;
        if ($request->lista_id) {
            $lista = $usuario->listas()->findOrFail($request->lista_id);
        } else {
            $lista = $usuario->listas()->first();
            if (!$lista) {
                $lista = $usuario->listas()->create([
                    'nombre' => 'Mis Favoritos',
                    'es_publica' => false
                ]);
            }
        }
        
        $item = $lista->items()->where('producto_id', $request->producto_id)->first();
        if ($item) {
            $item->delete();
            return back()->with('success', 'Producto removido de tus listas.');
        } else {
            $lista->items()->create(['producto_id' => $request->producto_id]);
            return back()->with('success', 'Producto agregado a tu lista.');
        }
    }
}
