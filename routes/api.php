<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Belajar;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaxiTripController;
use App\Http\Controllers\MarketplaceController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('tes_api', [Belajar::class, 'coba']);
Route::post('cek_hari', [Belajar::class, 'cek_tgl']);
Route::get('set_rahasia/{jenis}/{teks}', [Belajar::class, 'enkripsi_dekripsi']);
Route::post('/get-recap', [TaxiTripController::class, 'getRecap']);

// tugas route
Route::post('/produk-classic-cars', [MarketplaceController::class, 'produkClassicCars']);
Route::post('/produk-era-60an', [MarketplaceController::class, 'produkEra60an']);
Route::post('/order-dalam-1-bulan', [MarketplaceController::class, 'orderDalam1Bulan']);
Route::post('/order-tanpa-pengiriman', [MarketplaceController::class, 'orderTanpaPengiriman']);
Route::post('/pembayaran-2004-di-atas-5000', [MarketplaceController::class, 'pembayaran2004DiAtas5000']);
Route::post('/pembayaran-2004-bulan-tertentu', [MarketplaceController::class, 'pembayaran2004BulanTertentu']);
Route::post('/7-pembayaran-terendah-2003', [MarketplaceController::class, 'tujuhPembayaranTerendah2003']);
Route::post('/pelanggan-tanpa-state', [MarketplaceController::class, 'pelangganTanpaState']);
Route::post('/pelanggan-credit-limit-tertinggi', [MarketplaceController::class, 'pelangganCreditLimitTertinggi']);
Route::post('/pelanggan-alamat-kedua-saja', [MarketplaceController::class, 'pelangganAlamatKeduaSaja']);
