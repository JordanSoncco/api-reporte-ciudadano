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
        // 1. Buscamos el reporte en la base de datos
        $incidencia = \App\Models\Incidencia::find($id);

        if (!$incidencia) {
            return response()->json(['mensaje' => 'Incidencia no encontrada'], 404);
        }

        // 2. Actualizamos los textos si vienen en la petición
        if ($request->has('titulo')) {
            $incidencia->titulo = $request->titulo;
        }
        if ($request->has('descripcion')) {
            $incidencia->descripcion = $request->descripcion;
        }
        if ($request->has('estado')) {
            $incidencia->estado = $request->estado;
        }

        // 3. NUEVO: Actualizamos las coordenadas GPS si vienen en la petición
        if ($request->has('latitud')) {
            $incidencia->latitud = $request->latitud;
        }
        if ($request->has('longitud')) {
            $incidencia->longitud = $request->longitud;
        }

        // 4. NUEVO: Si el usuario envió una imagen nueva, la procesamos
        if ($request->hasFile('imagen')) {
            // Generamos un nombre único para la nueva foto
            $imageName = time() . '.' . $request->imagen->extension();
            
            // Movemos la foto a la carpeta pública 'uploads'
            $request->imagen->move(public_path('uploads'), $imageName);
            
            // Actualizamos la ruta en la base de datos
            $incidencia->imagen_ruta = 'uploads/' . $imageName;
        }

        // 5. Guardamos los cambios
        $incidencia->save();

        return response()->json([
            'mensaje' => 'Incidencia actualizada correctamente',
            'incidencia' => $incidencia
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