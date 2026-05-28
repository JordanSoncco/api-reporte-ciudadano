<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidenciaController;
use App\Http\Controllers\AuthController;

// Rutas de Autenticación
Route::post('registro', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rutas del CRUD
Route::apiResource('incidencias', IncidenciaController::class);