<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JadwalController;
use App\Http\Controllers\Api\PelangganController;
use App\Http\Controllers\Api\PengajuanController;
use App\Http\Controllers\Api\RekananController;
use App\Http\Controllers\Api\SimulasiController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;

// Endpoint Publik
Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login');

Route::get('/units', [UnitController::class, 'index']);
Route::get('/units/{id}', [UnitController::class, 'show']);
Route::post('/simulasi', [SimulasiController::class, 'store'])
    ->middleware('throttle:api-public-write');
Route::post('/pengajuan', [PengajuanController::class, 'store'])
    ->middleware('throttle:api-public-write');
Route::get('/pengajuan/status/{kode}', [PengajuanController::class, 'status']);

// Endpoint Terautentikasi (Admin & Owner)
Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/pengajuan', [PengajuanController::class, 'index']);
    Route::get('/pengajuan/{id}', [PengajuanController::class, 'show']);

    // Khusus Admin
    Route::middleware('role:admin')->group(function (): void {
        Route::post('/units', [UnitController::class, 'store']);
        Route::put('/units/{id}', [UnitController::class, 'update']);
        Route::delete('/units/{id}', [UnitController::class, 'destroy']);

        Route::apiResource('/pelanggan', PelangganController::class)
            ->parameters(['pelanggan' => 'id']);

        Route::apiResource('/rekanan', RekananController::class)
            ->parameters(['rekanan' => 'id']);

        Route::apiResource('/jadwal', JadwalController::class)
            ->parameters(['jadwal' => 'id']);

        Route::patch('/pengajuan/{id}/status', [PengajuanController::class, 'updateStatus']);
    });

    // Khusus Owner
    Route::middleware('role:owner')->group(function (): void {
        Route::get('/pengajuan/{id}/pemeriksaan', [PengajuanController::class, 'pemeriksaan']);
        Route::post('/pengajuan/{id}/approval', [PengajuanController::class, 'approval']);
    });
});
