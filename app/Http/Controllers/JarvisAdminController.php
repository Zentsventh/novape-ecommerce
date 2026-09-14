<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\JarvisAuditLog;
use App\Services\JarvisAgentService;

/**
 * JarvisAdminController — Punto de entrada HTTP.
 *
 * Toda la lógica de NLU, tools y sesión está delegada al JarvisAgentService.
 * Este controlador solo se encarga de recibir el request y devolver JSON.
 */
class JarvisAdminController extends Controller
{
    private JarvisAgentService $agent;

    public function __construct(JarvisAgentService $agent)
    {
        $this->agent = $agent;
    }

    /**
     * Handle an admin Jarvis message (text or voice transcript).
     */
    public function handle(Request $request)
    {
        $msg = $request->input('message', '');

        // Delegar todo al servicio agente
        $response = $this->agent->process($msg, $request->user()?->id);

        // Audit log
        try {
            JarvisAuditLog::create([
                'user_id'          => $request->user()?->id,
                'role'             => 'admin',
                'intent'           => $msg,
                'payload'          => $response,
                'action_taken'     => $response['action'] ?? null,
                'response_time_ms' => $response['response_time_ms'] ?? 0,
            ]);
        } catch (\Exception $e) {
            \Log::warning('JarvisAuditLog failed: ' . $e->getMessage());
        }

        return response()->json($response);
    }
}
