<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DocumentoController;

Route::post('/documento/consultar', [DocumentoController::class, 'consultar'])->name('api.documento.consultar');
