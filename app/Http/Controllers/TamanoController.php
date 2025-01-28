<?php

namespace App\Http\Controllers;

use App\Models\Tamano;
use Illuminate\Http\Request;

class TamanoController extends Controller
{
    // Listar todos las tamanos
    public function index(Request $request)
    {
        // Establecer la cantidad de tamanos por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener los tamanos paginados
        $tamanos = Tamano::paginate($perPage);

        // Retornar los tamanos paginados en formato JSON
        return response()->json($tamanos->items());
    }

    // Crear un nuevo tamano
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'descripcion' => 'required|string|max:255',
            ]);

            $tamano = Tamano::create($validated);
            return response()->json($tamano, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear el tamaño: ' . $e->getMessage()], 500);
        }
    }

    // Mostrar un tamano específica
    public function show($id)
    {
        try {
            $tamano = Tamano::findOrFail($id);
            return response()->json($tamano);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el tamaño: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar un tamano
    public function update(Request $request, $id)
    {
        try {
            $tamano = Tamano::findOrFail($id);

            $validated = $request->validate([
                'descripcion' => 'sometimes|required|string|max:255',
            ]);

            $tamano->update($validated);
            return response()->json($tamano);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al editar el tamaño: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar un color
    public function destroy($id)
    {
        $tamano = Tamano::findOrFail($id);
        $tamano->delete();
        return response()->json(null, 204);
    }
}
