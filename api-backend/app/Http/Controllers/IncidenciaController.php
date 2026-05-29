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

    // 3. EDITAR (PUT) - Actualiza título, descripción o estado
    public function update(Request $request, $id)
    {
        $incidencia = Incidencia::find($id);
        if (!$incidencia) {
            return response()->json(['mensaje' => 'No encontrada'], 404);
        }

        // Usamos fill() para actualizar solo los campos que vengan en la petición (titulo, descripcion, o estado)
        $incidencia->fill($request->only(['titulo', 'descripcion', 'estado']));
        $incidencia->save();

        return response()->json([
            'mensaje' => 'Incidencia actualizada correctamente', 
            'data' => $incidencia
        ], 200);
    }

    // 4. ELIMINAR (DELETE) - Borra el reporte y la imagen del servidor
    public function destroy($id)
    {
        $incidencia = Incidencia::find($id);
        
        if (!$incidencia) {
            return response()->json(['mensaje' => 'No encontrada'], 404);
        }

        // Buena práctica: Borrar la imagen física del disco duro de AWS para liberar espacio
        if ($incidencia->imagen_ruta) {
            // El texto viene como "storage/incidencias/foto.jpg". 
            // Necesitamos quitarle la palabra "storage/" para que AWS lo encuentre
            $rutaRelativa = str_replace('storage/', '', $incidencia->imagen_ruta);
            Storage::disk('public')->delete($rutaRelativa);
        }

        $incidencia->delete();
        return response()->json(['mensaje' => 'Incidencia y archivos eliminados con éxito'], 200);
    }
}