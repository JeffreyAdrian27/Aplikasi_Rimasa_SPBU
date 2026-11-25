<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashEntryController;
use App\Http\Controllers\CashProjectionController;
use App\Http\Controllers\CreditSaleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FuelRedemptionController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/tebus', [FuelRedemptionController::class, 'index'])->name('tebus.index');
    Route::post('/tebus', [FuelRedemptionController::class, 'store'])->name('tebus.store');
    Route::post('/tebus/batch', [FuelRedemptionController::class, 'storeBatch'])->name('tebus.store-batch');
    Route::patch('/tebus/harga-dasar', [FuelRedemptionController::class, 'updateBasePrices'])->name('tebus.base-prices.update');
    Route::put('/tebus/{fuelRedemption}', [FuelRedemptionController::class, 'update'])->name('tebus.update');
    Route::delete('/tebus/{fuelRedemption}', [FuelRedemptionController::class, 'destroy'])->name('tebus.destroy');

    Route::get('/penjualan', [CreditSaleController::class, 'index'])->name('penjualan.index');
    Route::post('/penjualan', [CreditSaleController::class, 'store'])->name('penjualan.store');
    Route::put('/penjualan/{creditSale}', [CreditSaleController::class, 'update'])->name('penjualan.update');
    Route::patch('/penjualan/{creditSale}/lunas', [CreditSaleController::class, 'markPaid'])->name('penjualan.mark-paid');
    Route::patch('/penjualan/{creditSale}/belum-lunas', [CreditSaleController::class, 'markUnpaid'])->name('penjualan.mark-unpaid');
    Route::delete('/penjualan/{creditSale}', [CreditSaleController::class, 'destroy'])->name('penjualan.destroy');

    Route::get('/kas', [CashEntryController::class, 'index'])->name('kas.index');
    Route::post('/kas', [CashEntryController::class, 'store'])->name('kas.store');
    Route::post('/kas/batch', [CashEntryController::class, 'storeBatch'])->name('kas.store-batch');
    Route::put('/kas/{cashEntry}', [CashEntryController::class, 'update'])->name('kas.update');
    Route::delete('/kas/{cashEntry}', [CashEntryController::class, 'destroy'])->name('kas.destroy');

    Route::get('/proyeksi', [CashProjectionController::class, 'index'])->name('proyeksi.index');
    Route::post('/proyeksi/settings', [CashProjectionController::class, 'updateSettings'])->name('proyeksi.settings');

    Route::get('/pengaturan', [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/pengaturan/cabang/{branch}', [SettingsController::class, 'updateBranch'])->name('settings.branch.update');

    Route::get('/akun', [AccountController::class, 'index'])->name('account.index');
    Route::put('/akun/password', [AccountController::class, 'updatePassword'])->name('account.password.update');
});
