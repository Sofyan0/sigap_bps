<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardSettingsController;
use App\Http\Controllers\KelolaDataController;
use App\Http\Controllers\MonitoringHargaImportController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/monitoring-harga/upload', [MonitoringHargaImportController::class, 'store'])->name('monitoring-harga.upload');
    Route::post('/sekarkijang/harga/upload', [MonitoringHargaImportController::class, 'storeRegion'])->name('sekarkijang-prices.upload');
    Route::post('/pengaturan', [DashboardSettingsController::class, 'update'])->name('dashboard.settings.update');

    Route::get('/kelola-data', [KelolaDataController::class, 'index'])->name('kelola-data');
    Route::post('/kelola-data/store', [KelolaDataController::class, 'store'])->name('kelola-data.store');
    Route::post('/kelola-data/update', [KelolaDataController::class, 'update'])->name('kelola-data.update');
    Route::delete('/kelola-data/destroy/{id}', [KelolaDataController::class, 'destroy'])->name('kelola-data.destroy');
    Route::post('/kelola-data/upload-excel', [KelolaDataController::class, 'uploadExcel'])->name('kelola-data.upload-excel');
    Route::post('/kelola-data/reset', [KelolaDataController::class, 'reset'])->name('kelola-data.reset');
});