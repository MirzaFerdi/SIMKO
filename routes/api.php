<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\MetodePembayaranController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\TransaksiController;

// Group Auth
// Jika mau akses auth ini harus pakai /auth setelah /api cikk
Route::group(['middleware' => 'api', 'prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('me', [AuthController::class, 'me']);
});

// ini akses seperti biasanya, harus setelah login dengan ada bearer token
Route::middleware('auth:api')->group(function () {
    Route::apiResource('role', RoleController::class);
    Route::apiResource('brand', BrandController::class);
    Route::apiResource('kategori', KategoriController::class);
    Route::apiResource('metode-pembayaran', MetodePembayaranController::class);
    Route::apiResource('produk', ProdukController::class);
    Route::apiResource('transaksi', TransaksiController::class);
});
