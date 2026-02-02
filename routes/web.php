<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorPoController;
use App\Http\Controllers\PenawaranController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('vendor-pos/{id}/preview', [VendorPoController::class, 'preview'])
     ->name('vendor-pos.preview');
Route::get('/public/vendor-pos/{id}', [VendorPoController::class, 'publicShow'])
     ->name('public.vendor-pos.show');
     Route::get('/penawarans/{id}/preview', [PenawaranController::class, 'previewPdf'])->name('penawaran.preview');
     Route::get('/penawarans/{id}/preview-lubricant', [PenawaranController::class, 'previewPdflub'])->name('penawaranlub.preview');

Route::get('/{any}', function () {
    return view('index');
})->where('any', '.*');
