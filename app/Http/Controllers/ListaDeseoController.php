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
        
        // Find the item only if it belongs to a list owned by the user
        $item = \App\Models\UsuarioListaItem::whereHas('lista', function($q) use ($usuario) {
            $q->where('usuario_id', $usuario->id);
        })->findOrFail($id);

        $item->delete();

        return back()->with('success', 'Producto eliminado de la lista.');
    }
}
