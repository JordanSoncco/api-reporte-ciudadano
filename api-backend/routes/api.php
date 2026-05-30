<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\AuthController;

// --- RUTAS PÚBLICAS (No requieren token) ---
Route::post('registro', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
Route::post('auth/google', [AuthController::class, 'googleSignIn']);

// --- RUTAS PRIVADAS (Solo para usuarios logueados con token) ---
Route::middleware('auth:sanctum')->group(function () {
    
    // Rutas del CRUD protegidas
    Route::apiResource('incidencias', IncidenciaController::class);
    
});