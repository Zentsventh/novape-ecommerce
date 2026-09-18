<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentoController;

Route::post('/documento/consultar', [DocumentoController::class, 'consultar'])->name('api.documento.consultar');

Route::post('/log-frontend-error', function(Request $request) {
    \Illuminate\Support\Facades\Log::error('Frontend Error: ' . $request->input('message'), [
        'stack' => $request->input('stack')
    ]);
    return response()->json(['success' => true]);
});