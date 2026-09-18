<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Profile\SyncWishlistsRequest;
use App\Http\Requests\Profile\ToggleWishlistRequest;
use App\Http\Requests\Profile\StoreWishlistRequest;
use App\Services\Profile\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class ListaDeseoController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService
    ) {}

    public function storeLista(StoreWishlistRequest $request): RedirectResponse
    {

        $this->wishlistService->createList(
            \Auth::user(),
            $request->input('nombre'),
            (bool) $request->input('es_publica', false)
        );

        return back()->with('success', 'Lista creada exitosamente.');
    }

    public function destroyLista(int $id): RedirectResponse
    {
        $this->wishlistService->deleteList(\Auth::user(), $id);
        return back()->with('success', 'Lista eliminada.');
    }

    public function getLists(): JsonResponse
    {
        $usuario = \Auth::user();
        if (!$usuario) {
            return response()->json([]);
        }

        $listas = $this->wishlistService->getLists($usuario);
        return response()->json($listas);
    }

    public function syncWishlists(SyncWishlistsRequest $request): RedirectResponse
    {
        $this->wishlistService->syncWishlists(
            \Auth::user(),
            (int) $request->input('producto_id'),
            $request->input('lista_ids', [])
        );

        return back()->with('success', 'Listas guardadas exitosamente.');
    }

    public function toggleWishlist(ToggleWishlistRequest $request): RedirectResponse
    {
        $message = $this->wishlistService->toggleWishlist(
            \Auth::user(),
            (int) $request->input('producto_id'),
            $request->input('lista_id') ? (int) $request->input('lista_id') : null
        );

        return back()->with('success', $message);
    }
}
