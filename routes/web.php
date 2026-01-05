<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PdfController;
use App\Models\Document;

Route::middleware('auth')->group(function () {
    Route::get('/pdf/chofer', [PdfController::class, 'generarSolicitudChofer'])->name('pdf.chofer');
    Route::get('/pdf/unidad', [PdfController::class, 'generarSolicitudUnidad'])->name('pdf.unidad');
    //Historico
    Route::get('/document/{document}/pdf', [PdfController::class, 'downloadHistory'])->name('pdf.download');
});
