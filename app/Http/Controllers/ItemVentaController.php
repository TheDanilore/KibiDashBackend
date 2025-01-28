<?php

namespace App\Http\Controllers;

use App\Models\ItemVenta;
use Illuminate\Http\Request;

class ItemVentaController extends Controller
{
    // Obtener los items de una venta específica
    public function index($ventaId)
    {
        $items = ItemVenta::where('venta_id', $ventaId)->with('producto')->get();
        return response()->json($items);
    }

    // Crear un nuevo item en una venta
    public function store(Request $request)
    {
        $validated = $request->validate([
            'venta_id' => 'required|exists:ventas,id',
            'producto_id' => 'required|exists:productos,id',
            'cantidad' => 'required|numeric',
            'precio_unitario' => 'required|numeric',
            'subtotal' => 'required|numeric',
        ]);

        $item = ItemVenta::create($validated);
        return response()->json($item, 201);
    }

    // Actualizar un item de venta
    public function update(Request $request, $id)
    {
        $item = ItemVenta::findOrFail($id);
        $validated = $request->validate([
            'cantidad' => 'numeric',
            'precio_unitario' => 'numeric',
            'subtotal' => 'numeric',
        ]);

        $item->update($validated);
        return response()->json($item);
    }

    // Eliminar un item de venta
    public function destroy($id)
    {
        ItemVenta::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
