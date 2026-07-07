<?php

use App\Http\Controllers\Api\TransferStockController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/transfer-stocks/products', [TransferStockController::class, 'products']);
    Route::get('/transfer-stocks', [TransferStockController::class, 'index']);
    Route::post('/transfer-stocks', [TransferStockController::class, 'store']);
    Route::get('/transfer-stocks/{transferStock}', [TransferStockController::class, 'show']);
});
