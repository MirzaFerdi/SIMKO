<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\MetodePembayaranController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\TransaksiController;
use App\Http\Controllers\Api\LaporanController;

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

    Route::get('metode-pembayaran/kategori/{kategori_id}', [MetodePembayaranController::class, 'showByKategori']);
    Route::apiResource('metode-pembayaran', MetodePembayaranController::class);

    Route::get('produk/brand/{brand_id}', [ProdukController::class, 'showBrand']);
    Route::get('produk/search/{keyword}', [ProdukController::class, 'search']);
    Route::get('produk/paginate', [ProdukController::class, 'showPaginate']);
    Route::get('produk/lowstock', [ProdukController::class, 'showLowStock']);
    Route::post('produk/{id}/tambah-stok', [ProdukController::class, 'tambahStok']);
    Route::get('produk/{id}/riwayat', [ProdukController::class, 'riwayatStok']);
    Route::apiResource('produk', ProdukController::class);

    Route::get('transaksi/pending', [TransaksiController::class, 'pending']);
    Route::get('transaksi/paginate', [TransaksiController::class, 'showPaginate']);
    Route::get('transaksi/produkterlaris', [TransaksiController::class, 'getProdukTerlaris']);
    Route::get('transaksi/produkterjual/{kategori_id}', [TransaksiController::class, 'getProdukTerjualPerBulanByKategori']);
    Route::post('transaksi/update-status', [TransaksiController::class, 'updateStatus']);
    Route::post('laporan/rekap', [LaporanController::class, 'rekap']);
    Route::apiResource('transaksi', TransaksiController::class);
});
