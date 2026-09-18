<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Marketing\StoreCouponRequest;
use App\Http\Requests\Admin\Marketing\UpdateCouponRequest;
use App\Services\Admin\Marketing\MarketingService;
use App\Models\Cupon;
use Inertia\Inertia;

class CuponController extends Controller
{
    public function __construct(
        private readonly MarketingService $marketingService
    ) {}

    public function index()
    {
        return Inertia::render('Admin/Cupones/Index', [
            'cupones' => $this->marketingService->getCoupons()
        ]);
    }

    public function store(StoreCouponRequest $request)
    {
        $this->marketingService->createCoupon($request->validated());
        return redirect()->back()->with('success', 'Cupón creado correctamente.');
    }

    public function update(UpdateCouponRequest $request, int $id) 
    {
        $this->marketingService->updateCoupon(Cupon::findOrFail($id), $request->validated());
        return redirect()->back()->with('success', 'Cupón actualizado correctamente.');
    }

    public function destroy(int $id)
    {
        $this->marketingService->deleteCoupon(Cupon::findOrFail($id));
        return redirect()->back()->with('success', 'Cupón eliminado correctamente.');
    }
}
