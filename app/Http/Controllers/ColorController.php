<?php

namespace App\Http\Controllers;

use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    // Listar todos los colores
    public function index(Request $request)
    {
        // Establecer la cantidad de colores por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener los colores paginados
        $colores = Color::paginate($perPage);

        // Retornar los colores paginados en formato JSON
        return response()->json($colores->items());
    }

    // Crear un nuevo color
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'descripcion' => 'required|string|max:255',
            ]);

            $color = Color::create($validated);
            return response()->json($color, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear el color: ' . $e->getMessage()], 500);
        }
    }

    // Mostrar una categoría específica
    public function show($id)
    {
        try {
            $color = Color::findOrFail($id);
            return response()->json($color);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el color: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar un color
    public function update(Request $request, $id)
    {
        try {
            $color = Color::findOrFail($id);

            $validated = $request->validate([
                'descripcion' => 'sometimes|required|string|max:255',
            ]);

            $color->update($validated);
            return response()->json($color);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al editar el color: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar un color
    public function destroy($id)
    {
        $color = Color::findOrFail($id);
        $color->delete();
        return response()->json(null, 204);
    }
}
