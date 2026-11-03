<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KelolaDataController;

// Route Dashboard Utama
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Route Khusus Halaman Kelola Data
Route::get('/kelola-data', [KelolaDataController::class, 'index'])->name('kelola-data');
Route::post('/kelola-data/store', [KelolaDataController::class, 'store'])->name('kelola-data.store');
Route::post('/kelola-data/update', [KelolaDataController::class, 'update'])->name('kelola-data.update');
Route::delete('/kelola-data/destroy/{id}', [KelolaDataController::class, 'destroy'])->name('kelola-data.destroy');
Route::post('/kelola-data/upload-excel', [KelolaDataController::class, 'uploadExcel'])->name('kelola-data.upload-excel');
Route::post('/kelola-data/reset', [KelolaDataController::class, 'reset'])->name('kelola-data.reset');