<?php

namespace App\Http\Controllers;

use App\Models\ItemVenta;
use App\Models\Venta;
use Illuminate\Http\Request;

class VentaController extends Controller
{
    // Obtener todas las ventas
    public function index()
    {
        $ventas = Venta::with('cliente', 'items')->get();
        return response()->json($ventas);
    }

    // Crear una nueva venta
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'usuario_id' => 'required|exists:users,id',
            'total' => 'required|numeric',
            'tipo_venta' => 'required|in:normal,personalizado',
        ]);

        $venta = Venta::create($validated);

        foreach ($request->items as $item) {
            ItemVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $item['producto_id'],
                'cantidad' => $item['cantidad'],
                'precio_unitario' => $item['precio_unitario'],
                'subtotal' => $item['subtotal'],
            ]);
        }

        return response()->json($venta, 201);
    }

    // Obtener los detalles de una venta específica
    public function show($id)
    {
        $venta = Venta::with('cliente', 'items.producto')->findOrFail($id);
        return response()->json($venta);
    }

    // Actualizar una venta
    public function update(Request $request, $id)
    {
        $venta = Venta::findOrFail($id);
        $validated = $request->validate([
            'cliente_id' => 'exists:clientes,id',
            'total' => 'numeric',
            'tipo_venta' => 'in:normal,personalizado',
        ]);

        $venta->update($validated);
        return response()->json($venta);
    }

    // Eliminar una venta
    public function destroy($id)
    {
        Venta::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
