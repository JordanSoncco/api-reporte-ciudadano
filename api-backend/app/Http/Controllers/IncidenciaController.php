<?php

namespace App\Http\Controllers;

use App\Models\Incidencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class IncidenciaController extends Controller
{
    // 1. LISTAR (GET) - Muestra todas las incidencias
    public function index()
    {
        $incidencias = Incidencia::all();
        return response()->json($incidencias, 200);
    }

    // 2. CREAR (POST) - Guarda una nueva incidencia con imagen
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'titulo' => 'required|string|max:100',
            'descripcion' => 'required|string',
            'latitud' => 'required|string',
            'longitud' => 'required|string',
            'imagen' => 'nullable|image' // Valida que sea un archivo de imagen
        ]);

        $data = $request->all();

        // Si viene una imagen desde Flutter, la guardamos en el servidor
        if ($request->hasFile('imagen')) {
            $ruta = $request->file('imagen')->store('incidencias', 'public');
            $data['imagen_ruta'] = 'storage/' . $ruta;
        }

        $incidencia = Incidencia::create($data);
        return response()->json([
            'mensaje' => 'Incidencia reportada con exito',
            'data' => $incidencia
        ], 201);
    }

    // 3. EDITAR ESTADO (PUT) - Cambia de Pendiente a Resuelto
    public function update(Request $request, $id)
    {
        $incidencia = Incidencia::find($id);
        if (!$incidencia) {
            return response()->json(['mensaje' => 'No encontrada'], 404);
        }

        $incidencia->estado = $request->estado ?? $incidencia->estado;
        $incidencia->save();

        return response()->json(['mensaje' => 'Estado actualizado', 'data' => $incidencia], 200);
    }

    // 4. ELIMINAR (DELETE) - Borra el reporte
    public function destroy($id)
    {
        $incidencia = Incidencia::find($id);
        if ($incidencia) {
            $incidencia->delete();
            return response()->json(['mensaje' => 'Incidencia eliminada'], 200);
        }
        return response()->json(['mensaje' => 'No encontrada'], 404);
    }
}