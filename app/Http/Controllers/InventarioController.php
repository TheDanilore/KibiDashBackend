<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class InventarioController extends Controller
{

    public function index()
    {
        $inventario = Inventario::with(['producto', 'color', 'tamano', 'longitud'])->get();
        return response()->json($inventario);
    }

    public function mostrarCatalogo(Request $request)
    {
        $perPage = min($request->get('per_page', 15), 100);

        $query = Producto::with(['imagenes', 'inventario'])
            ->select('productos.*')
            ->leftJoin('inventario', 'productos.id', '=', 'inventario.producto_id')
            ->groupBy('productos.id')
            ->selectRaw('SUM(inventario.cantidad) as total_stock')
            ->selectRaw('MAX(inventario.precio_unitario) as precio_unitario_maximo');

        if ($request->has('categoria')) {
            $query->where('categoria_producto_id', $request->get('categoria'));
        }
        if ($request->has('estado')) {
            $query->where('estado', $request->get('estado'));
        }

        $productos = $query->paginate($perPage);

        return response()->json($productos);
    }

    // Mostrar un producto específico
    public function show($id)
    {
        try {
            $producto = Producto::with(['proveedor', 'categoria', 'inventario', 'unidadMedida', 'imagenes', 'ubicacion'])->findOrFail($id);
            return response()->json($producto);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Producto no encontrado quiero probar algo'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el producto'], 500);
        }
    }

    public function searchByName(Request $request)
    {
        Log::info('Iniciando búsqueda');
        Log::info('Query recibido: ' . $request->query('q'));

        try {
            $query = $request->query('q');
            if (!$query) {
                Log::info('Query vacío');
                return response()->json(['message' => 'No se ingresó un término de búsqueda'], 200);
            }

            Log::info('Ejecutando consulta SQL');
            $products = Producto::where('nombre', 'like', '%' . $query . '%')
                ->with([
                    'imagenes' => function ($query) {
                        $query->select('id', 'producto_id', 'url');  // Obtiene solo la primera imagen
                    },
                    'colores',  // Relación de colores
                    'tamanos',  // Relación de tamaños
                    'longitudes' // Relación de longitudes
                ])
                ->select('id', 'nombre', 'descripcion', 'precio_unitario')
                ->take(10)
                ->get();

            Log::info('Resultados encontrados: ' . $products->count());

            if ($products->isEmpty()) {
                Log::info('No se encontraron productos');
                return response()->json(['message' => 'No se encontraron productos'], 200);
            }

            Log::info('Enviando respuesta con productos');
            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Error en búsqueda: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al buscar el producto: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene el inventario específico para un producto
     * 
     * @param int $productoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInventarioByProducto($productoId)
    {
        try {
            $inventario = Inventario::where('producto_id', $productoId)
                ->with(['color:id,descripcion', 'tamano:id,descripcion', 'longitud:id,descripcion'])
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'producto_id' => $item->producto_id,
                        'color_id' => $item->color_id,
                        'tamano_id' => $item->tamano_id,
                        'longitud_id' => $item->longitud_id,
                        'cantidad' => $item->cantidad,
                        'precio_unitario' => $item->precio_unitario,
                        'color' => $item->color ? $item->color->descripcion : null,
                        'tamano' => $item->tamano ? $item->tamano->descripcion : null,
                        'longitud' => $item->longitud ? $item->longitud->descripcion : null
                    ];
                });

            return response()->json($inventario);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el inventario del producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene un resumen agrupado del inventario por producto
     * 
     * @param int $productoId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getInventarioResumenByProducto($productoId)
    {
        try {
            $inventario = Inventario::where('producto_id', $productoId)
                ->with(['color:id,descripcion', 'tamano:id,descripcion', 'longitud:id,descripcion'])
                ->get();

            $resumen = [
                'colores' => $inventario->whereNotNull('color_id')
                    ->unique('color_id')
                    ->map(function ($item) {
                        return [
                            'id' => $item->color_id,
                            'descripcion' => $item->color->descripcion
                        ];
                    })->values(),
                'tamanos' => $inventario->whereNotNull('tamano_id')
                    ->unique('tamano_id')
                    ->map(function ($item) {
                        return [
                            'id' => $item->tamano_id,
                            'descripcion' => $item->tamano->descripcion
                        ];
                    })->values(),
                'longitudes' => $inventario->whereNotNull('longitud_id')
                    ->unique('longitud_id')
                    ->map(function ($item) {
                        return [
                            'id' => $item->longitud_id,
                            'descripcion' => $item->longitud->descripcion
                        ];
                    })->values(),
                'total_stock' => $inventario->sum('stock')
            ];

            return response()->json($resumen);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener el resumen del inventario del producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
