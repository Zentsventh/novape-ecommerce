<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Finance\OpenCashRegisterRequest;
use App\Http\Requests\Admin\Finance\CloseCashRegisterRequest;
use App\Http\Requests\Admin\Finance\CashMovementRequest;
use App\Services\Admin\Finance\CashFlowService;
use Illuminate\Http\Request;

class CajaController extends Controller
{
    public function __construct(
        private readonly CashFlowService $cashFlowService
    ) {}

    public function aperturar(OpenCashRegisterRequest $request)
    {
        try {
            $this->cashFlowService->openRegister($request->validated(), auth()->id() ?? 1);
            return redirect()->back()->with('success', 'Turno iniciado y caja aperturada exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cerrar(CloseCashRegisterRequest $request)
    {
        try {
            $message = $this->cashFlowService->closeRegister($request->validated(), auth()->id() ?? 1);
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Ocurrió un error al cerrar la caja: ' . $e->getMessage());
        }
    }

    public function movimiento(CashMovementRequest $request)
    {
        try {
            $this->cashFlowService->recordMovement($request->validated(), auth()->id() ?? 1);
            return redirect()->back()->with('success', 'Movimiento registrado correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
