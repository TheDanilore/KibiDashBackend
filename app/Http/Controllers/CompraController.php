<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\ItemCompra;
use App\Models\Producto;
use Illuminate\Http\Request;

class CompraController extends Controller
{
    // Listar todas las compras con paginación
    public function index()
    {
        $compras = Compra::with('items', 'pago')->paginate(10);
        return response()->json($compras);
    }

    // Registrar una nueva compra
    public function store(Request $request)
    {
        $request->validate([
            'proveedor_id' => 'required|exists:proveedores,id',
            'items' => 'required|array',
            'items.*.producto_id' => 'required|exists:productos,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'pago.monto' => 'required|numeric|min:0',
        ]);

        // Crear la compra
        $compra = Compra::create([
            'proveedor_id' => $request->proveedor_id,
            'fecha' => now(),
            'estado' => 'Pendiente', // Estado inicial de la compra
        ]);

        // Añadir los ítems comprados (sin actualizar el inventario)
        foreach ($request->items as $item) {
            $producto = Producto::find($item['producto_id']);
            ItemCompra::create([
                'compra_id' => $compra->id,
                'producto_id' => $producto->id,
                'cantidad' => $item['cantidad'],
                'precio' => $producto->precio, // Precio del producto
            ]);
        }

        // Registrar el pago asociado
        $compra->pago()->create([
            'monto' => $request->pago['monto'],
            'fecha' => now(),
        ]);

        return response()->json(['message' => 'Compra registrada exitosamente', 'compra' => $compra], 201);
    }

    // Actualizar el estado de la compra
    public function actualizarEstadoCompra($id, Request $request)
    {
        $request->validate([
            'estado' => 'required|in:Pendiente,Completado,Cancelado',
        ]);

        $compra = Compra::find($id);

        if (!$compra) {
            return response()->json(['error' => 'Compra no encontrada'], 404);
        }

        $compra->estado = $request->estado;
        $compra->save();

        // Si el estado es "completada", agregar los productos al inventario
        if ($request->estado === 'Completado') {
            $this->agregarAlInventario($compra->id);
        }

        return response()->json(['message' => 'Estado de compra actualizado exitosamente']);
    }

    // Método para agregar productos al inventario una vez la compra esté completada
    public function agregarAlInventario($compraId)
    {
        $compra = Compra::find($compraId);

        if ($compra && $compra->estado === 'Completado') {
            foreach ($compra->items as $item) {
                // Actualizar el inventario
                $producto = Producto::find($item->producto_id);
                $producto->increment('stock', $item->cantidad);
            }

            return response()->json(['message' => 'Productos añadidos al inventario']);
        }

        return response()->json(['error' => 'La compra no está completada o no existe'], 400);
    }

    // Mostrar una compra específica
    public function show($id)
    {
        $compra = Compra::with('items.producto', 'pago')->findOrFail($id);
        return response()->json($compra);
    }

    // Actualizar una compra (parcial o total)
    public function update(Request $request, $id)
    {
        $compra = Compra::findOrFail($id);
        // Lógica de actualización según necesidad
        // ...
        return response()->json(['message' => 'Compra actualizada exitosamente']);
    }

    // Eliminar una compra
    public function destroy($id)
    {
        $compra = Compra::findOrFail($id);
        $compra->delete();
        return response()->json(['message' => 'Compra eliminada']);
    }
}
