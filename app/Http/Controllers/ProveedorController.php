<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProveedorController extends Controller
{
    // Listar proveedores

    public function index(Request $request)
    {
        // Establecer la cantidad de productos por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener los productos paginados
        $proveedores = Proveedor::paginate($perPage);

        // Retornar los productos paginados en formato JSON
        return response()->json($proveedores->items());
    }

    // Crear un nuevo proveedor
    public function store(Request $request)
    {
        try {

            // Validar los datos del formulario
            $validated = $request->validate([
                'ruc' => [
                    'required',
                    'numeric',
                    'digits:11',
                    Rule::unique('proveedores'), // Validación de unicidad
                ],
                'razon_social' => [
                    'required',
                    'string',
                    'max:190',
                    'min:5',
                    Rule::unique('proveedores'), // Validación de unicidad
                ],
                'direccion' => 'required|string|max:190|min:10',
                'telefono' => 'required|string|min:9|max:12',
            ],);

            $proveedor = Proveedor::create($validated);

            return response()->json(['message' => 'Proveedor creado exitosamente', 'proveedor' => $proveedor], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear el proveedor: ' . $e->getMessage()], 500);
        }
    }

    // Mostrar un proveedor específico
    public function show($id)
    {
        try {
            $proveedor = Proveedor::with('audit.usercreated', 'audit.userupdated')->find($id);
            $proveedor = Proveedor::findOrFail($id);
            return response()->json($proveedor);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el proveedor: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar un proveedor
    public function update(Request $request, $id)
    {
        try {
            // Validar los datos del formulario
            $validated = $request->validate([
                'ruc' => [
                    'required',
                    'numeric',
                    'digits:11',
                ],
                'razon_social' => [
                    'required',
                    'string',
                    'max:190',
                    'min:5',
                ],
                'direccion' => 'required|string|max:255|min:10',
                'telefono' => 'required|string|min:9|max:12',
                'estado' => 'required',
            ],);

            $proveedor = Proveedor::findOrFail($id);
            $proveedor->update($validated);

            return response()->json(['message' => 'Proveedor actualizado exitosamente', 'proveedor' => $proveedor], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al actualizar el proveedor: ' . $e->getMessage()], 500);
        }
    }

    // Eliminar un proveedor
    public function destroy($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->delete();

        return response()->json(['message' => 'Proveedor eliminado']);
    }
}
