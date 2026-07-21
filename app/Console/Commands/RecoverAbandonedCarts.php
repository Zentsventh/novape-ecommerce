<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Carrito;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\AbandonedCartMail;
use Illuminate\Support\Facades\Log;

class RecoverAbandonedCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:recover-abandoned';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Encuentra carritos abandonados por más de 24 horas y envía un correo de recuperación.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando búsqueda de carritos abandonados...');

        // Buscar carritos que:
        // 1. Tengan un usuario logueado (para saber el correo).
        // 2. Tengan items.
        // 3. Su última actualización fue hace más de 24 horas pero menos de 72 horas (para no enviar a carritos antiquísimos).
        // 4. No hayan sido notificados previamente.
        $carritos = Carrito::whereNotNull('usuario_id')
            ->whereNull('notified_at')
            ->where('updated_at', '<', Carbon::now()->subHours(24))
            ->where('updated_at', '>', Carbon::now()->subHours(72))
            ->with(['usuario', 'items.variante.producto.imagenes'])
            ->get();

        $count = 0;

        foreach ($carritos as $carrito) {
            // Verificar que realmente tenga items
            if ($carrito->items->count() > 0 && $carrito->usuario) {
                try {
                    Mail::to($carrito->usuario->email)->send(new AbandonedCartMail($carrito->usuario, $carrito->items));
                    
                    // Marcar como notificado
                    $carrito->notified_at = Carbon::now();
                    $carrito->save();

                    $count++;
                    $this->info("Correo enviado a: {$carrito->usuario->email}");
                } catch (\Exception $e) {
                    Log::error("Error enviando correo de carrito abandonado a {$carrito->usuario->email}: " . $e->getMessage());
                    $this->error("Error enviando a {$carrito->usuario->email}");
                }
            }
        }

        $this->info("Proceso completado. Se enviaron {$count} correos de recuperación.");
    }
}
