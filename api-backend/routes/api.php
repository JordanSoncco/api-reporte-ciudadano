<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidenciaController;

// Esta línea crea automáticamente las rutas GET, POST, PUT y DELETE
Route::apiResource('incidencias', IncidenciaController::class);