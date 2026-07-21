<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gasto;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ConfiguracionSitio;

class GastoController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());
        $search = $request->query('search', '');
        $categoria = $request->query('categoria', '');

        $query = Gasto::query();

        if ($startDate && $startDate !== '') {
            $query->whereDate('fecha_gasto', '>=', $startDate);
        }
        if ($endDate && $endDate !== '') {
            $query->whereDate('fecha_gasto', '<=', $endDate);
        }
        
        if ($search && $search !== '') {
            $query->where(function($q) use ($search) {
                $q->where('concepto', 'like', "%{$search}%")
                  ->orWhere('monto', 'like', "%{$search}%");
            });
        }
        
        if ($categoria && $categoria !== '' && $categoria !== 'Todos') {
            $query->where('categoria', $categoria);
        }

        $gastos = $query->orderBy('fecha_gasto', 'desc')->paginate(15);
        $totalGastos = $query->sum('monto');
        $logoUrl = ConfiguracionSitio::obtener('logo_url');

        return Inertia::render('Admin/Gastos/Index', [
            'gastos' => $gastos,
            'totalGastos' => $totalGastos,
            'logoUrl' => $logoUrl,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'search' => $search,
                'categoria' => $categoria
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'categoria' => 'required|string',
            'tipo' => 'required|in:fijo,variable',
            'fecha_gasto' => 'required|date',
        ]);

        Gasto::create($request->all());

        return redirect()->back()->with('success', 'Gasto registrado correctamente.');
    }

    public function destroy($id)
    {
        Gasto::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Gasto eliminado.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'concepto' => 'required|string|max:255',
            'monto' => 'required|numeric|min:0',
            'categoria' => 'required|string',
            'fecha_gasto' => 'required|date',
        ]);

        $gasto = Gasto::findOrFail($id);
        $gasto->update($request->all());

        return redirect()->back()->with('success', 'Gasto actualizado correctamente.');
    }
}
