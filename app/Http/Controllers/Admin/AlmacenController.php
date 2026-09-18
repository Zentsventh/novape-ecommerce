<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Admin\Warehouse\WarehouseTransferRequest;
use App\Services\Admin\Warehouse\WarehouseService;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class AlmacenController extends Controller
{
    public function __construct(
        private readonly WarehouseService $warehouseService
    ) {}

    public function index()
    {
        $data = $this->warehouseService->getIndexData();
        $data['logoUrl'] = ConfiguracionSitio::obtener('logo_url');
        return Inertia::render('Admin/Almacenes/Index', $data);
    }

    public function store(StoreWarehouseRequest $request)
    {
        $this->warehouseService->createWarehouse($request->validated());
        return redirect()->back()->with('success', 'Almacén registrado correctamente.');
    }

    public function destroy(int $id)
    {
        try {
            $this->warehouseService->deleteWarehouse($id);
            return redirect()->back()->with('success', 'Almacén eliminado.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function kardex(int $id)
    {
        $data = $this->warehouseService->getKardex($id);
        if (!$data) abort(404);

        $data['logoUrl'] = ConfiguracionSitio::obtener('logo_url');
        return Inertia::render('Admin/Almacenes/Kardex', $data);
    }

    public function transferir(WarehouseTransferRequest $request)
    {
        try {
            $this->warehouseService->transferStock($request->validated(), auth()->id() ?? 1);
            return redirect()->back()->with('success', 'Transferencia realizada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
