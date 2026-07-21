<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Pedido;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderCreated;
use Illuminate\Support\Facades\Log;
use App\Services\FacturacionService;

class SendOrderConfirmationJob implements ShouldQueue
{
    use Queueable;

    protected $pedidoId;
    protected $correoDestino;

    /**
     * Create a new job instance.
     */
    public function __construct($pedidoId, $correoDestino = null)
    {
        $this->pedidoId = $pedidoId;
        $this->correoDestino = $correoDestino;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $pedido = Pedido::with(['usuario', 'items.variante.producto', 'envio', 'pago'])->find($this->pedidoId);

            if (!$pedido) {
                Log::warning("Job SendOrderConfirmationJob abortado: Pedido {$this->pedidoId} no encontrado.");
                return;
            }

            // Emitir comprobante electrónico en SUNAT
            $facturacionService = new FacturacionService();
            $comprobante = $facturacionService->emitirComprobante($pedido);

            // Refrescar el pedido para que el correo reciba la relación 'comprobante'
            $pedido->load('comprobante');

            $email = $this->correoDestino ?: ($pedido->usuario ? $pedido->usuario->email : null);

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Mail::to($email)->send(new OrderCreated($pedido, null));
            } else {
                Log::warning("Job SendOrderConfirmationJob: No hay un correo válido para el pedido {$this->pedidoId}.");
            }
        } catch (\Throwable $e) {
            Log::error("Error en SendOrderConfirmationJob para pedido {$this->pedidoId}: " . $e->getMessage());
        }
    }
}
