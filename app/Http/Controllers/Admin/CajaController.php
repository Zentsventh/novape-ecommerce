<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CajaController extends Controller
{
    public function aperturar(Request $request)
    {
        $request->validate([
            'monto_inicial' => 'required|numeric|min:0'
        ]);

        $abierta = DB::table('cajas_sesiones')
            ->where('cajero_id', auth()->id())
            ->where('estado', 'abierta')
            ->exists();

        if ($abierta) {
            return redirect()->back()->with('error', 'Ya tienes una caja abierta.');
        }

        DB::table('cajas_sesiones')->insert([
            'cajero_id' => auth()->id(),
            'monto_inicial' => $request->monto_inicial,
            'fecha_apertura' => now(),
            'estado' => 'abierta',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Turno iniciado y caja aperturada exitosamente.');
    }

    public function cerrar(Request $request)
    {
        $request->validate([
            'monto_final_declarado' => 'required|numeric|min:0'
        ]);

        try {
            DB::beginTransaction();

            $caja = DB::table('cajas_sesiones')
                ->where('cajero_id', auth()->id())
                ->where('estado', 'abierta')
                ->lockForUpdate()
                ->first();

            if (!$caja) {
                DB::rollBack();
                return redirect()->back()->with('error', 'No tienes una caja abierta para cerrar.');
            }

            // Sumar ventas en efectivo estrictamente (buscando 'Efectivo')
            $ventasEfectivo = DB::table('venta_pos_pagos')
                ->join('ventas_pos', 'venta_pos_pagos.venta_pos_id', '=', 'ventas_pos.id')
                ->join('metodos_pago', 'venta_pos_pagos.metodo_pago_id', '=', 'metodos_pago.id')
                ->where('ventas_pos.caja_sesion_id', $caja->id)
                ->where('metodos_pago.nombre', 'LIKE', '%Efectivo%')
                ->sum('venta_pos_pagos.monto');

            // Sumar ingresos y restar egresos de caja_movimientos
            $ingresos = DB::table('caja_movimientos')
                ->where('caja_sesion_id', $caja->id)
                ->where('tipo', 'ingreso')
                ->sum('monto');
                
            $egresos = DB::table('caja_movimientos')
                ->where('caja_sesion_id', $caja->id)
                ->where('tipo', 'egreso')
                ->sum('monto');

            $ventasTotal = DB::table('ventas_pos')
                ->where('caja_sesion_id', $caja->id)
                ->sum('total');

            $monto_final_esperado = $caja->monto_inicial + $ventasEfectivo + $ingresos - $egresos;
            $descuadre = $request->monto_final_declarado - $monto_final_esperado;

            DB::table('cajas_sesiones')->where('id', $caja->id)->update([
                'fecha_cierre' => now(),
                'monto_final_esperado' => $monto_final_esperado,
                'monto_final_declarado' => $request->monto_final_declarado,
                'descuadre' => $descuadre,
                'estado' => 'cerrada',
                'updated_at' => now()
            ]);

            DB::commit();

            return redirect()->back()->with('success', "Caja cerrada. Ventas totales: S/ {$ventasTotal}. Ingresos Extras: S/ {$ingresos}. Egresos: S/ {$egresos}. Descuadre Efectivo: S/ {$descuadre}");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Ocurrió un error al cerrar la caja: ' . $e->getMessage());
        }
    }

    public function movimiento(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:ingreso,egreso',
            'monto' => 'required|numeric|min:0.1',
            'concepto' => 'required|string|max:255'
        ]);

        $caja = DB::table('cajas_sesiones')
            ->where('cajero_id', auth()->id())
            ->where('estado', 'abierta')
            ->first();

        if (!$caja) {
            return redirect()->back()->with('error', 'Debes abrir caja antes de registrar movimientos.');
        }

        DB::table('caja_movimientos')->insert([
            'caja_sesion_id' => $caja->id,
            'usuario_id' => auth()->id(),
            'tipo' => $request->tipo,
            'monto' => $request->monto,
            'concepto' => $request->concepto,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Movimiento registrado correctamente.');
    }
}
