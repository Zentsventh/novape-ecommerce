<?php

declare(strict_types=1);

namespace App\Services\Admin\Finance;

use App\Models\Gasto;
use Illuminate\Pagination\LengthAwarePaginator;

class ExpenseTrackingService
{
    public function getExpenses(array $filters): LengthAwarePaginator
    {
        $query = Gasto::query();

        if (!empty($filters['start_date'])) {
            $query->whereDate('fecha_gasto', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('fecha_gasto', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('concepto', 'like', "%{$search}%")
                  ->orWhere('monto', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['categoria']) && $filters['categoria'] !== 'Todos') {
            $query->where('categoria', $filters['categoria']);
        }

        return $query->orderBy('fecha_gasto', 'desc')->paginate(15);
    }

    public function getTotalExpenses(array $filters): float
    {
        $query = Gasto::query();

        if (!empty($filters['start_date'])) {
            $query->whereDate('fecha_gasto', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('fecha_gasto', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('concepto', 'like', "%{$search}%")
                  ->orWhere('monto', 'like', "%{$search}%");
            });
        }
        
        if (!empty($filters['categoria']) && $filters['categoria'] !== 'Todos') {
            $query->where('categoria', $filters['categoria']);
        }

        return (float) $query->sum('monto');
    }

    public function createExpense(array $data): Gasto
    {
        return Gasto::create($data);
    }

    public function updateExpense(Gasto $gasto, array $data): Gasto
    {
        $gasto->update($data);
        return $gasto;
    }

    public function deleteExpense(Gasto $gasto): void
    {
        $gasto->delete();
    }
}
