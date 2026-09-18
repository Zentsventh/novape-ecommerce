<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\Warehouse\InventoryAuditService;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class InventarioController extends Controller
{
    public function __construct(
        private readonly InventoryAuditService $inventoryService
    ) {}

    public function dashboard()
    {
        $data = $this->inventoryService->getDashboardData();
        $data['logoUrl'] = ConfiguracionSitio::obtener('logo_url');
        $data['usuario_nombre'] = auth()->user()->nombre ?? 'Administrador';
        
        return Inertia::render('Admin/Inventario/Dashboard', $data);
    }
}
