<?php

namespace App\Http\Controllers;

use App\Models\CategoriaProducto;
use Illuminate\Http\Request;

class CategoriaProductoController extends Controller
{
    // Listar todas las categorías
    public function index(Request $request)
    {
        // Establecer la cantidad de categorias por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener las categorias paginados
        $categorias = CategoriaProducto::paginate($perPage);

        // Retornar las categorias paginados en formato JSON
        return response()->json($categorias->items());
    }

    // Crear una nueva categoría
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'descripcion' => 'required|string|max:255',
            ]);

            $categoria = CategoriaProducto::create($validated);
            return response()->json($categoria, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear la categoria: ' . $e->getMessage()], 500);
        }
    }

    // Mostrar una categoría específica
    public function show($id)
    {
        try {
            $categoria = CategoriaProducto::findOrFail($id);
            return response()->json($categoria);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar la categoira: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar una categoría
    public function update(Request $request, $id)
    {
        try {


            $categoria = CategoriaProducto::findOrFail($id);

            $validated = $request->validate([
                'descripcion' => 'sometimes|required|string|max:255',
            ]);

            $categoria->update($validated);
            return response()->json($categoria);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al editar la categoria: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar una categoría
    public function destroy($id)
    {
        $categoria = CategoriaProducto::findOrFail($id);
        $categoria->delete();
        return response()->json(null, 204);
    }
}
