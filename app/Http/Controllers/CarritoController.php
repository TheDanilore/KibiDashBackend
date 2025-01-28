<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Carrito; // Asegúrate de tener este modelo
use App\Models\Producto;
use DB;

class CarritoController extends Controller
{
    // Mostrar el carrito
    public function index()
    {
        // Suponiendo que el carrito está asociado al usuario autenticado
        $carrito = Carrito::where('user_id', auth()->id())->with('producto')->get();
        
        if ($carrito->isEmpty()) {
            return response()->json(['message' => 'Carrito vacío'], 200);
        }

        return response()->json($carrito, 200);
    }

    // Agregar producto al carrito
    public function agregar(Request $request)
    {
        $request->validate([
            'producto' => 'required|array',
            'producto.id' => 'required|exists:productos,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $producto = Producto::find($request->input('producto.id'));
        if (!$producto) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        }

        // Verificar si hay suficiente stock
        if ($producto->stock < $request->input('cantidad')) {
            return response()->json(['error' => 'No hay suficiente stock disponible'], 400);
        }

        // Actualizar o agregar producto al carrito
        $carrito = Carrito::updateOrCreate(
            ['user_id' => auth()->id(), 'producto_id' => $producto->id],
            ['cantidad' => \DB::raw('cantidad + ' . $request->input('cantidad'))]
        );

        return response()->json(['message' => 'Producto agregado/actualizado en el carrito', 'carrito' => $carrito], 201);
    }

    // Eliminar producto del carrito
    public function eliminar(Request $request)
    {
        $request->validate([
            'productoId' => 'required|exists:carritos,producto_id',
        ]);

        $carrito = Carrito::where('user_id', auth()->id())
                          ->where('producto_id', $request->input('productoId'))
                          ->first();

        if ($carrito) {
            $carrito->delete();
            return response()->json(['message' => 'Producto eliminado del carrito'], 200);
        }

        return response()->json(['error' => 'Producto no encontrado en el carrito'], 404);
    }

    // Vaciar el carrito
    public function vaciar()
    {
        Carrito::where('user_id', auth()->id())->delete();
        return response()->json(['message' => 'Carrito vaciado'], 200);
    }
}
