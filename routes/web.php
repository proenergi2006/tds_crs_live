<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VendorPoController;

if (app()->environment('local')) {
    Route::get('/public/vendor-pos/{id}', [VendorPoController::class, 'publicShow'])
         ->name('public.vendor-pos.show');
}

Route::get('/{any}', function () {
    return view('index');
})->where('any', '^(?!api/).*$');
