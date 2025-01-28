<?php

namespace App\Http\Controllers;

use App\Models\ItemSalida;
use Illuminate\Http\Request;

class ItemSalidaController extends Controller
{
     // Listar todos los ítems de una salida
     public function index($salidaProductoId)
     {
         $items = ItemSalida::where('salida_producto_id', $salidaProductoId)->get();
         return response()->json($items);
     }
 
     // Crear un nuevo ítem en una salida de producto
     public function store(Request $request, $salidaProductoId)
     {
         $validated = $request->validate([
             'producto_id' => 'required|exists:productos,id',
             'cantidad' => 'required|integer|min:1',
             'p_unitario' => 'required|numeric',
         ]);
 
         $validated['salida_producto_id'] = $salidaProductoId;
         $item = ItemSalida::create($validated);
         return response()->json($item, 201);
     }
 
     // Mostrar un ítem específico de una salida de producto
     public function show($salidaProductoId, $itemId)
     {
         $item = ItemSalida::where('salida_producto_id', $salidaProductoId)->findOrFail($itemId);
         return response()->json($item);
     }
 
     // Actualizar un ítem de una salida de producto
     public function update(Request $request, $salidaProductoId, $itemId)
     {
         $item = ItemSalida::where('salida_producto_id', $salidaProductoId)->findOrFail($itemId);
 
         $validated = $request->validate([
             'producto_id' => 'sometimes|required|exists:productos,id',
             'cantidad' => 'sometimes|required|integer|min:1',
             'p_unitario' => 'sometimes|required|numeric',
         ]);
 
         $item->update($validated);
         return response()->json($item);
     }
 
     // Eliminar un ítem de una salida de producto
     public function destroy($salidaProductoId, $itemId)
     {
         $item = ItemSalida::where('salida_producto_id', $salidaProductoId)->findOrFail($itemId);
         $item->delete();
         return response()->json(null, 204);
     }
}
