<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use App\Models\Pedido;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class StripePaymentController extends Controller
{
    private $stripeSecretKey;

    public function __construct()
    {
        // Usando la clave que proporcionaste temporalmente
        $this->stripeSecretKey = config('services.stripe.secret', 'sk_test_123');
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Muestra la vista del Checkout.
     */
    public function checkout()
    {
        $cart = session('cart', []);
        $monto = array_reduce($cart, function ($carry, $item) {
            return $carry + ($item['precio'] * $item['cantidad']);
        }, 0);

        if ($monto <= 0) {
            return redirect('/')->with('error', 'El carrito está vacío.');
        }

        return Inertia::render('Checkout', [
            'cart' => array_values($cart),
            'montoTotal' => $monto
        ]);
    }

    /**
     * Genera un PaymentIntent de Stripe y retorna el client_secret.
     */
    public function createIntent(Request $request)
    {
        try {
            $cart = session('cart', []);
            $total = 0;
            foreach ($cart as $key => &$item) {
                // Re-validar el precio directamente desde la base de datos por seguridad
                if (isset($item['variante_id'])) {
                    $variante = \App\Models\Variante::find($item['variante_id']);
                    if ($variante) {
                        $item['precio'] = (float) $variante->precio;
                    }
                }
                $total += ($item['precio'] * $item['cantidad']);
            }
            unset($item); // romper referencia
            
            // Guardar carrito actualizado en caso haya correcciones de precio
            session(['cart' => $cart]);

            if ($total <= 0) {
                return response()->json(['error' => 'Carrito vacío'], 400);
            }

            $couponCode = $request->input('coupon');
            $shippingCost = $request->input('shippingCost', 0);
            
            // Validar cupones
            $descuentoMonto = 0;
            $couponId = null;



            $deliveryType = $request->input('deliveryType', '');
            $distrito = $request->input('distrito', '');
            $costoEnvio = 0;
            if ($deliveryType === 'domicilio' && $distrito) {
                $costoEnvio = (float) \App\Models\ConfiguracionSitio::obtener('envio_tarifa_plana', 15);
            }

            $totalConDescuento = max(0, $total - $descuentoMonto) + $shippingCost;

            // Prevenir montos menores a $0.50 USD (o S/ 2.00 PEN) en Stripe
            if ($totalConDescuento < 2.00) {
                return response()->json(['error' => 'El monto mínimo es de S/ 2.00'], 400);
            }

            // Stripe procesa montos en centavos
            $amountInCents = intval(round($totalConDescuento * 100));

            // Bloquear/Reservar el stock temporalmente (15 minutos)
            $sessionId = session()->getId();
            
            // Validación de stock sin ReservaStock (modelo no implementado)
            $stockError = \Illuminate\Support\Facades\DB::transaction(function () use ($cart) {
                foreach ($cart as $item) {
                    $varianteId = $item['variante_id'] ?? null;
                    if ($varianteId) {
                        // BLOQUEO PESIMISTA: lockForUpdate() bloquea la fila hasta que termine la transacción
                        $variante = \App\Models\Variante::lockForUpdate()->find($varianteId);
                        $stockActual = $variante ? $variante->stock : 0;
                        
                        if ($item['cantidad'] > $stockActual) {
                            return "Stock insuficiente para el producto: {$item['nombre']}. Solo quedan {$stockActual} unidades disponibles.";
                        }
                    }
                }
                return null; // Todo OK
            });

            if ($stockError) {
                return response()->json(['error' => $stockError], 400);
            }

            $codigoPedido = session('checkout_pedido');
            if (!$codigoPedido) {
                $codigoPedido = 'PED-' . rand(10000000, 99999999);
            }
            
            // Crear o actualizar Pedido en estado Pendiente
            $pedido = Pedido::where('codigo', $codigoPedido)->first();
            if (!$pedido) {
                $pedido = Pedido::create([
                    'usuario_id' => auth()->id(),
                    'codigo' => $codigoPedido,
                    'subtotal' => $total,
                    'descuento' => $descuentoMonto,
                    'costo_envio' => $shippingCost,
                    'total' => $totalConDescuento,
                    'estado' => 'Pendiente',
                    'tipo_comprobante' => $request->input('facturacion.comprobante', 'Boleta'),
                    'documento_cliente' => $request->input('facturacion.dni') ?: $request->input('facturacion.ruc'),
                    'nombre_facturacion' => $request->input('facturacion.comprobante') === 'Factura' ? $request->input('facturacion.razonSocial') : $request->input('facturacion.nombres'),
                    'direccion_facturacion' => $request->input('facturacion.direccionFiscal'),
                    'direccion_envio_snapshot' => $request->input('shippingAddress', [])
                ]);
                foreach ($cart as $item) {
                    $pedido->items()->create([
                        'variante_id' => $item['variante_id'] ?? null,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio']
                    ]);
                }
            } elseif ($pedido->estado === 'Pendiente') {
                $pedido->update([
                    'subtotal' => $total,
                    'descuento' => $descuentoMonto,
                    'costo_envio' => $shippingCost,
                    'total' => $totalConDescuento,
                    'usuario_id' => auth()->id(),
                    'tipo_comprobante' => $request->input('facturacion.comprobante', 'Boleta'),
                    'documento_cliente' => $request->input('facturacion.dni') ?: $request->input('facturacion.ruc'),
                    'nombre_facturacion' => $request->input('facturacion.comprobante') === 'Factura' ? $request->input('facturacion.razonSocial') : $request->input('facturacion.nombres'),
                    'direccion_facturacion' => $request->input('facturacion.direccionFiscal'),
                    'direccion_envio_snapshot' => $request->input('shippingAddress', [])
                ]);
                $pedido->items()->delete();
                foreach ($cart as $item) {
                    $pedido->items()->create([
                        'variante_id' => $item['variante_id'] ?? null,
                        'cantidad' => $item['cantidad'],
                        'precio_unitario' => $item['precio']
                    ]);
                }
            }

            session([
                'checkout_pedido' => $codigoPedido, 
                'checkout_monto' => $totalConDescuento,
                'checkout_cupon_id' => $couponId,
                'checkout_email' => $request->input('email'),
                'checkout_facturacion' => $request->input('facturacion')
            ]);

            // Crear el PaymentIntent
            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInCents,
                'currency' => 'pen', // Soles peruanos
                'metadata' => [
                    'codigo_pedido' => $codigoPedido,
                    'user_id' => auth()->id() ?? 'guest',
                    'email' => $request->input('email') ?? (auth()->user() ? auth()->user()->email : ''),
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'codigoPedido' => $codigoPedido,
                'monto' => $totalConDescuento
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error creando Stripe PaymentIntent: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Webhook oficial para recibir notificaciones asíncronas de Stripe
     */
    public function webhook(Request $request)
    {
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        if (!$endpoint_secret) {
            Log::error('Webhook Stripe: Falta STRIPE_WEBHOOK_SECRET en .env');
            return response('Server Configuration Error', 500);
        }

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $event = null;

        try {
            // Validación estricta obligatoria
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch(\UnexpectedValueException $e) {
            Log::error('Webhook Stripe: Payload inválido');
            return response('Invalid payload', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Webhook Stripe: Firma inválida');
            return response('Invalid signature', 400);
        }

        // Manejar el evento de pago exitoso
        if ($event->type == 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object; 
            
            $codigoPedido = $paymentIntent->metadata->codigo_pedido ?? null;
            $montoSoles = $paymentIntent->amount / 100;
            $userId = $paymentIntent->metadata->user_id ?? null;

            if ($codigoPedido) {
                $errorResponse = \Illuminate\Support\Facades\DB::transaction(function () use ($codigoPedido, $montoSoles, $paymentIntent) {
                    $pedido = Pedido::with('items')->where('codigo', $codigoPedido)->lockForUpdate()->first();
                    
                    if ($pedido && $pedido->estado === 'Pendiente') {
                        // Validar monto para evitar manipulaciones
                        if (abs($montoSoles - $pedido->total) > 0.01) {
                            Log::warning("Webhook Stripe: Monto pagado ($montoSoles) no coincide con total del pedido {$pedido->codigo} ({$pedido->total}).");
                            return response('Monto inválido', 400);
                        }

                        $pedido->update(['estado' => 'Pagado']);
                        
                        foreach ($pedido->items as $item) {
                            if ($item->variante_id) {
                                $variante = \App\Models\Variante::lockForUpdate()->find($item->variante_id);
                                if ($variante) {
                                    $almacenEcommerceId = (int) \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
                                    
                                    $stockAlmacen = \Illuminate\Support\Facades\DB::table('stock_almacen')
                                        ->where('variante_id', $item->variante_id)
                                        ->where('almacen_id', $almacenEcommerceId)
                                        ->first();
                    
                                    if ($stockAlmacen) {
                                        \Illuminate\Support\Facades\DB::table('stock_almacen')->where('id', $stockAlmacen->id)->decrement('cantidad', $item->cantidad);
                                    } else {
                                        \Illuminate\Support\Facades\DB::table('stock_almacen')->insert([
                                            'almacen_id' => $almacenEcommerceId,
                                            'variante_id' => $item->variante_id,
                                            'cantidad' => 0 - $item->cantidad,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                    }
                    
                                    // Recalcular variante.stock global
                                    \Illuminate\Support\Facades\DB::statement("UPDATE variante SET stock = (SELECT COALESCE(SUM(cantidad), 0) FROM stock_almacen WHERE variante_id = ?) WHERE id = ?", [$item->variante_id, $item->variante_id]);

                                    // Registrar Kardex
                                    \Illuminate\Support\Facades\DB::table('movimientos_almacen')->insert([
                                        'almacen_id' => $almacenEcommerceId,
                                        'variante_id' => $item->variante_id,
                                        'tipo' => 'salida',
                                        'cantidad' => -$item->cantidad,
                                        'referencia' => 'Venta Ecommerce Stripe - ' . $pedido->codigo,
                                        'usuario_id' => $pedido->usuario_id ?? 1,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }

                        // Emitir Comprobante (Boleta/Factura) SUNAT automáticamente
                        try {
                            $sunatService = new \App\Services\SunatService();
                            $sunatService->emitirComprobante($pedido);
                        } catch (\Exception $e) {
                            Log::error("Error emitiendo comprobante SUNAT para pedido {$pedido->codigo}: " . $e->getMessage());
                        }
                        
                        // Enviar correo de confirmación (Asíncrono para no bloquear Webhook)
                        $emailTo = $paymentIntent->metadata->email ?? ($pedido->usuario ? $pedido->usuario->email : null);
                        if ($emailTo) {
                            \App\Jobs\SendOrderConfirmationJob::dispatch($pedido->id, $emailTo);
                        }

                        Log::info("Pedido $codigoPedido procesado exitosamente vía Webhook de Stripe.");
                        return null; // Exito
                    } else {
                        Log::info("Pedido $codigoPedido ya estaba pagado o no fue encontrado.");
                        return null;
                    }
                });
                
                if ($errorResponse) {
                    return $errorResponse;
                }
            }
        } else {
            Log::info('Webhook Stripe: Evento recibido no procesado: ' . $event->type);
        }

        return response('Webhook Handled', 200);
    }
    
    public function success(Request $request)
    {
        $paymentIntentId = $request->query('payment_intent');
        $codigoPedido = session('checkout_pedido');
        
        $pedido = Pedido::where('codigo', $codigoPedido)->first();
        $usuario = auth()->user();

        if ($pedido) {
            $errorResponse = null;
            if ($pedido->estado === 'Pendiente') {
                $errorResponse = \Illuminate\Support\Facades\DB::transaction(function () use ($pedido, $paymentIntentId, $codigoPedido) {
                    $pedido = Pedido::where('codigo', $codigoPedido)->lockForUpdate()->first();
                    if ($pedido && $pedido->estado === 'Pendiente') {
                        // Verificar con Stripe el estado real del PaymentIntent para evitar vulnerabilidad de "Fake Success"
                        try {
                            $intent = \Stripe\PaymentIntent::retrieve($paymentIntentId);
                            if ($intent->status !== 'succeeded') {
                                return redirect('/checkout')->with('error', 'El pago no ha sido completado exitosamente según Stripe.');
                            }
                            if (abs(($intent->amount / 100) - $pedido->total) > 0.01) {
                                return redirect('/checkout')->with('error', 'El monto pagado no coincide con el total del pedido.');
                            }
                        } catch (\Exception $e) {
                            Log::error("Error validando success: " . $e->getMessage());
                            return redirect('/checkout')->with('error', 'Error al verificar el estado del pago con la pasarela.');
                        }

                        $pedido->update(['estado' => 'Pagado']);
                        
                        $items = \DB::table('pedido_item')->where('pedido_id', $pedido->id)->get();
                        foreach ($items as $item) {
                            if ($item->variante_id) {
                                $variante = \App\Models\Variante::lockForUpdate()->find($item->variante_id);
                                if ($variante) {
                                    $almacenEcommerceId = (int) \App\Models\ConfiguracionSitio::obtener('almacen_ecommerce_id', 1);
                                    
                                    $stockAlmacen = \Illuminate\Support\Facades\DB::table('stock_almacen')
                                        ->where('variante_id', $item->variante_id)
                                        ->where('almacen_id', $almacenEcommerceId)
                                        ->first();
                    
                                    if ($stockAlmacen) {
                                        \Illuminate\Support\Facades\DB::table('stock_almacen')->where('id', $stockAlmacen->id)->decrement('cantidad', $item->cantidad);
                                    } else {
                                        \Illuminate\Support\Facades\DB::table('stock_almacen')->insert([
                                            'almacen_id' => $almacenEcommerceId,
                                            'variante_id' => $item->variante_id,
                                            'cantidad' => 0 - $item->cantidad,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);
                                    }
                    
                                    \Illuminate\Support\Facades\DB::statement("UPDATE variante SET stock = (SELECT COALESCE(SUM(cantidad), 0) FROM stock_almacen WHERE variante_id = ?) WHERE id = ?", [$item->variante_id, $item->variante_id]);

                                    \Illuminate\Support\Facades\DB::table('movimientos_almacen')->insert([
                                        'almacen_id' => $almacenEcommerceId,
                                        'variante_id' => $item->variante_id,
                                        'tipo' => 'salida',
                                        'cantidad' => -$item->cantidad,
                                        'referencia' => 'Venta Ecommerce Stripe (Success) - ' . $pedido->codigo,
                                        'usuario_id' => $pedido->usuario_id ?? 1,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
                            }
                        }
                        
                        \App\Models\ReservaStock::where('session_id', session()->getId())->delete();
                        return null; // Todo OK
                    }
                    return null; // Ya estaba pagado
                });
                
                if ($errorResponse) {
                    return $errorResponse;
                }
            }


            // Despachar el Job para envío de correos asíncrono
            $correoDestino = session('checkout_email') ?: ($usuario ? $usuario->email : null);
            \App\Jobs\SendOrderConfirmationJob::dispatch($pedido->id, $correoDestino);
            
            // Despachar notificación por WhatsApp
            if ($usuario && !empty($usuario->telefono)) {
                $mensaje = "¡Hola {$usuario->nombres}! Tu pedido {$pedido->codigo} ha sido confirmado por un total de S/ {$pedido->total}. ¡Gracias por comprar en NOVAPE!";
                \App\Jobs\SendWhatsAppNotification::dispatch($usuario->telefono, $mensaje);
            }
        }
        
        session()->forget('cart');
        session()->forget(['checkout_monto', 'checkout_pedido', 'checkout_cupon_id', 'checkout_email']);
        
        if ($usuario && $usuario->carrito) {
            $usuario->carrito->items()->delete();
        }
        
        return Inertia::render('CheckoutSuccess', [
            'pedido' => $codigoPedido,
            'paymentIntentId' => $paymentIntentId
        ]);
    }
}
