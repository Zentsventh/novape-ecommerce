<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentoController;
use App\Http\Controllers\JarvisAdminController;
use App\Http\Controllers\JarvisPublicController;
use App\Events\JarvisAlertEvent;

Route::post('/documento/consultar', [DocumentoController::class, 'consultar'])->name('api.documento.consultar');

// Jarvis Admin endpoint (internal copilot)
Route::post('/jarvis/admin/message', [JarvisAdminController::class, 'handle'])
    ->middleware(['throttle:30,1'])
    ->name('api.jarvis.admin.message');

// Jarvis Public endpoint (chatbot & voice call)
Route::post('/jarvis/public/message', [JarvisPublicController::class, 'handle'])
    ->middleware(['throttle:15,1'])
    ->name('api.jarvis.public.message');

// Test route to trigger a proactive alert
Route::get('/jarvis/test-alert', function () {
    event(new JarvisAlertEvent('Alerta del sistema, tienes un nuevo pedido urgente que revisar en el panel de control.'));
    return response()->json(['status' => 'Alerta enviada a Jarvis']);
});