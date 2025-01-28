<?php

namespace App\Http\Controllers;

use App\Models\Longitud;
use Illuminate\Http\Request;

class LongitudController extends Controller
{
    // Listar todos las longitudes
    public function index(Request $request)
    {
        // Establecer la cantidad de longitudes por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener las longitudes paginados
        $longitudes = Longitud::paginate($perPage);

        // Retornar las longitudes paginados en formato JSON
        return response()->json($longitudes->items());
    }

    // Crear un nuevo longitud
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'descripcion' => 'required|string|max:255',
            ]);

            $longitud = Longitud::create($validated);
            return response()->json($longitud, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear la longitud: ' . $e->getMessage()], 500);
        }
    }

    // Mostrar una categoría específica
    public function show($id)
    {
        try {
            $longitud = Longitud::findOrFail($id);
            return response()->json($longitud);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar la longitud: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar un color
    public function update(Request $request, $id)
    {
        try {
            $longitud = Longitud::findOrFail($id);

            $validated = $request->validate([
                'descripcion' => 'sometimes|required|string|max:255',
            ]);

            $longitud->update($validated);
            return response()->json($longitud);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al editar la longitud: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar un color
    public function destroy($id)
    {
        $longitud = Longitud::findOrFail($id);
        $longitud->delete();
        return response()->json(null, 204);
    }
}
