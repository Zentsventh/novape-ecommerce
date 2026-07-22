<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Pedido;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderStatusUpdated;
use Stripe\Stripe;
use Stripe\Refund;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Pedido::with('usuario');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo', 'like', "%{$search}%")
                  ->orWhereHas('usuario', function($u) use ($search) {
                      $u->where('nombres', 'like', "%{$search}%")
                        ->orWhere('apellidos', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }
        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        $sort = $request->input('sort', 'desc');
        if (in_array($sort, ['asc', 'desc'])) {
            $query->orderBy('created_at', $sort);
        } else {
            $query->orderBy('id', 'desc');
        }

        $pedidos = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Pedidos/Index', [
            'pedidos' => $pedidos,
            'filtros' => [
                'search' => $request->search,
                'date_start' => $request->date_start,
                'date_end' => $request->date_end,
                'sort' => $sort,
            ]
        ]);
    }

    public function show($id)
    {
        $pedido = Pedido::with(['usuario', 'items.variante.producto', 'envio', 'pago'])->findOrFail($id);

        return Inertia::render('Admin/Pedidos/Show', [
            'pedido' => $pedido
        ]);
    }

    public function updateEstado(\App\Http\Requests\UpdateOrderStateRequest $request, $id)
    {
        $pedido = Pedido::with(['envio', 'items'])->findOrFail($id);
        
        $validated = $request->validated();
        $estadoAnterior = $pedido->estado;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $pedido->update([
                'estado' => $validated['estado'],
                'tracking_number' => $validated['tracking_number'] ?? $pedido->tracking_number,
                'courier_name' => $validated['courier_name'] ?? $pedido->courier_name,
            ]);

            // Fuga de Inventario Fix: Si se cancela manualmente desde el desplegable, devolver stock.
            if ($validated['estado'] === 'cancelado' && $estadoAnterior !== 'cancelado') {
                foreach ($pedido->items as $item) {
                    $almacenEcommerceId = \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
                    
                    $stockAlmacen = \Illuminate\Support\Facades\DB::table('stock_almacen')
                        ->where('variante_id', $item->variante_id)
                        ->where('almacen_id', $almacenEcommerceId)
                        ->first();
    
                    if ($stockAlmacen) {
                        \Illuminate\Support\Facades\DB::table('stock_almacen')->where('id', $stockAlmacen->id)->increment('cantidad', $item->cantidad);
                    } else {
                        \Illuminate\Support\Facades\DB::table('stock_almacen')->insert([
                            'almacen_id' => $almacenEcommerceId,
                            'variante_id' => $item->variante_id,
                            'cantidad' => $item->cantidad,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
    
                    \Illuminate\Support\Facades\DB::statement("UPDATE variante SET stock = (SELECT COALESCE(SUM(cantidad), 0) FROM stock_almacen WHERE variante_id = ?) WHERE id = ?", [$item->variante_id, $item->variante_id]);
    
                    \Illuminate\Support\Facades\DB::table('movimientos_almacen')->insert([
                        'almacen_id' => $almacenEcommerceId,
                        'variante_id' => $item->variante_id,
                        'tipo' => 'entrada',
                        'cantidad' => $item->cantidad,
                        'referencia' => 'Cancelación Administrativa Pedido ' . $pedido->codigo,
                        'usuario_id' => auth()->id() ?? 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            \Illuminate\Support\Facades\DB::commit();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Error al actualizar estado y stock del pedido ' . $pedido->id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar el estado del pedido.');
        }

        try {
            if ($pedido->usuario && filter_var($pedido->usuario->email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($pedido->usuario->email)->send(new OrderStatusUpdated($pedido));
            }

            // WhatsApp Notification
            if ($pedido->usuario && !empty($pedido->usuario->telefono)) {
                $mensajeWa = "¡Hola {$pedido->usuario->nombres}! El estado de tu pedido {$pedido->codigo} se ha actualizado a: {$validated['estado']}.";
                if ($request->has('tracking_number') && !empty($validated['tracking_number'])) {
                    $mensajeWa .= " Tu código de rastreo por {$pedido->courier_name} es: {$validated['tracking_number']}.";
                }
                \App\Jobs\SendWhatsAppNotification::dispatch($pedido->usuario->telefono, $mensajeWa);
            }
        } catch (\Exception $e) {
            \Log::error('No se pudo enviar notificaciones (Email/WhatsApp) de actualización de estado: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Estado del pedido y envío actualizado.');
    }

    public function reembolsar($id)
    {
        $pedido = Pedido::with(['pago', 'items'])->findOrFail($id);

        if ($pedido->estado === 'cancelado') {
            return redirect()->back()->with('error', 'El pedido ya está cancelado.');
        }

        if (!$pedido->pago || $pedido->pago->estado !== 'completado') {
            return redirect()->back()->with('error', 'El pedido no tiene un pago completado que se pueda reembolsar.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if ($pedido->pago->metodo_pago === 'Stripe' && $pedido->pago->transaccion_id) {
                Stripe::setApiKey(env('STRIPE_SECRET'));
                Refund::create([
                    'payment_intent' => $pedido->pago->transaccion_id,
                ]);
            } else if ($pedido->pago->metodo_pago === 'Niubiz') {
                // Lógica de anulación/reembolso para Niubiz si aplica
                Log::info("Reembolso Niubiz solicitado para pedido {$pedido->id}. La API requiere anulación manual en el portal por ahora.");
                $pedido->pago->update(['estado' => 'reembolso_pendiente']);
                \Illuminate\Support\Facades\DB::commit();
                return redirect()->back()->with('success', 'El pago por Niubiz requiere anulación manual en su portal. Estado cambiado a Reembolso Pendiente.');
            }

            // Actualizar estado
            $pedido->update(['estado' => 'cancelado']);
            $pedido->pago->update(['estado' => 'reembolsado']);

            // RESTAURAR STOCK Y KARDEX
            foreach ($pedido->items as $item) {
                // Devolver stock al almacén principal (dinámico)
                $almacenEcommerceId = \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
                
                $stockAlmacen = \Illuminate\Support\Facades\DB::table('stock_almacen')
                    ->where('variante_id', $item->variante_id)
                    ->where('almacen_id', $almacenEcommerceId)
                    ->first();

                if ($stockAlmacen) {
                    \Illuminate\Support\Facades\DB::table('stock_almacen')->where('id', $stockAlmacen->id)->increment('cantidad', $item->cantidad);
                } else {
                    \Illuminate\Support\Facades\DB::table('stock_almacen')->insert([
                        'almacen_id' => $almacenEcommerceId,
                        'variante_id' => $item->variante_id,
                        'cantidad' => $item->cantidad,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Recalcular variante.stock global
                \Illuminate\Support\Facades\DB::statement("UPDATE variante SET stock = (SELECT COALESCE(SUM(cantidad), 0) FROM stock_almacen WHERE variante_id = ?) WHERE id = ?", [$item->variante_id, $item->variante_id]);

                // Registrar en Kardex
                \Illuminate\Support\Facades\DB::table('movimientos_almacen')->insert([
                    'almacen_id' => $almacenEcommerceId,
                    'variante_id' => $item->variante_id,
                    'tipo' => 'entrada',
                    'cantidad' => $item->cantidad,
                    'referencia' => 'Reembolso Pedido ' . $pedido->codigo,
                    'usuario_id' => auth()->id() ?? 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->back()->with('success', 'El pedido ha sido reembolsado, cancelado y el stock restaurado exitosamente.');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Error reembolsando pedido ' . $pedido->id . ': ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al procesar el reembolso.');
        }
    }

    public function facturaVista($id)
    {
        $pedido = Pedido::with(['usuario', 'items.variante.producto', 'envio', 'pago'])->findOrFail($id);
        
        $logoPath = public_path('images/logofactura.png');
        $logoBase64 = null;
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }

        $empresa = [
            'razon_social' => \App\Models\ConfiguracionSitio::obtener('nombre_sitio', 'NOVAPE S.A.C.'),
            'ruc' => '20123456789',
            'direccion' => 'Av. Principal 123',
            'telefono' => \App\Models\ConfiguracionSitio::obtener('telefono_contacto', '+51 999 999 999'),
            'email' => \App\Models\ConfiguracionSitio::obtener('email_contacto', 'ventas@novape.com'),
            'horario' => 'Lunes a Viernes 9am - 6pm'
        ];

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.factura', compact('pedido', 'logoBase64', 'empresa'))
            ->setPaper('A4', 'portrait')
            ->download('factura-'.$pedido->codigo.'.pdf');
    }

    public function export()
    {
        $pedidos = Pedido::with('usuario')->get();

        $csv = "ID,Código,Cliente,Email,Total,Estado,Fecha\n";
        foreach ($pedidos as $p) {
            $csv .= implode(',', [
                $p->id,
                $p->codigo,
                '"' . ($p->usuario ? $p->usuario->nombres . ' ' . $p->usuario->apellidos : 'N/A') . '"',
                $p->usuario ? $p->usuario->email : '',
                $p->total,
                $p->estado,
                $p->created_at,
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pedidos_' . date('Y-m-d') . '.csv"',
        ]);
    }
}
