<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\AuthController;

// Rutas de Autenticación
Route::post('registro', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);
// Rutas del CRUD
Route::apiResource('incidencias', IncidenciaController::class);

// --- NUEVA RUTA PARA GOOGLE ---
Route::post('auth/google', [AuthController::class, 'googleSignIn']);