<?php

use App\Http\Controllers\InvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/invoices', [InvoiceController::class, 'store']);
Route::get('/invoices/{invoice_id}', [InvoiceController::class, 'show']);
