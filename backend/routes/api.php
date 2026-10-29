<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SMARTSPACE AI — API Routes
|--------------------------------------------------------------------------
|
| Semua rute di sini mendapat prefix /api secara otomatis dari
| bootstrap/app.php.
|
*/

// ── Auth (tamu) ──────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('daftar', [AuthController::class, 'daftar'])
        ->middleware('throttle:auth');

    Route::post('masuk', [AuthController::class, 'masuk'])
        ->middleware('throttle:auth');

    // Butuh token
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('saya',   [AuthController::class, 'saya']);
        Route::post('keluar', [AuthController::class, 'keluar']);
    });
});
