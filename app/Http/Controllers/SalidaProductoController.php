<?php

namespace App\Http\Controllers;

use App\Models\Inventario;
use App\Models\ItemSalida;
use App\Models\Movimiento;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\SalidaProducto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SalidaProductoController extends Controller
{
    // Listar todas las salidas de productos con paginación
    public function index()
    {
        $salidas = SalidaProducto::with('items.inventario.producto')->paginate(10);
        return response()->json($salidas);
    }

    public function obtenerTiposSalida()
    {
        $tiposSalida = SalidaProducto::getEnumValues('tipo_salida');
        return response()->json($tiposSalida);
    }

    public function buscar(Request $request)
    {
        try {
            // Log para debugging
            Log::info('Búsqueda de inventario con parámetros:', $request->all());

            $query = Inventario::where('producto_id', $request->producto_id);

            // Modificar la lógica para manejar los parámetros de manera más flexible
            if ($request->has('color_id')) {
                if ($request->color_id === null) {
                    $query->whereNull('color_id');
                } else {
                    $query->where('color_id', $request->color_id);
                }
            }

            if ($request->has('tamano_id')) {
                if ($request->tamano_id === null) {
                    $query->whereNull('tamano_id');
                } else {
                    $query->where('tamano_id', $request->tamano_id);
                }
            }

            if ($request->has('longitud_id')) {
                if ($request->longitud_id === null) {
                    $query->whereNull('longitud_id');
                } else {
                    $query->where('longitud_id', $request->longitud_id);
                }
            }

            // Log de la consulta SQL para debugging
            Log::info('SQL Query:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            $inventario = $query->first();

            if (!$inventario) {
                Log::warning('No se encontró inventario para los parámetros:', $request->all());
                return response()->json([
                    'error' => 'No se encontró un inventario para esta combinación'
                ], 404);
            }

            // Log del resultado
            Log::info('Inventario encontrado:', $inventario->toArray());

            return response()->json($inventario);
        } catch (\Exception $e) {
            Log::error('Error en buscar inventario: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Error al buscar inventario: ' . $e->getMessage()
            ], 500);
        }
    }

    // Registrar una nueva salida de producto
    public function store(Request $request)
    {
        Log::info('Datos recibidos para la salida:', $request->all());
        DB::beginTransaction();
        try {

            //Crear la salida
            try {
                $validatedData = $request->validate([
                    'guia_salida' => [
                        'required',
                        'string',
                        'max:20',
                        'min:4',
                        Rule::unique('salidas_productos'),
                    ],
                    'productos' => 'required|array|min:1',
                    'productos.*.producto_id' => 'required|integer|exists:productos,id',
                    'productos.*.color_id' => 'nullable|integer|exists:colores,id',
                    'productos.*.tamano_id' => 'nullable|integer|exists:tamanos,id',
                    'productos.*.longitud_id' => 'nullable|integer|exists:longitudes,id',
                    'productos.*.cantidad' => 'required|integer|min:1',
                    'productos.*.precio_unitario' => 'required|numeric|min:0.01',
                    'tipo_salida' => 'required|string|max:50', // Por ejemplo: venta, donación
                    'destino' => 'required|string|max:191',
                    'fecha' => 'required|date',
                    'observaciones' => 'nullable|string|max:250',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Error de validación: ' . json_encode($e->errors()));
                throw $e;
            }

            try {
                // Crear la salida
                $salida = SalidaProducto::create([
                    'guia_salida' => $validatedData['guia_salida'],
                    'tipo_salida' => $validatedData['tipo_salida'],
                    'destino' => $validatedData['destino'],
                    'fecha' => $validatedData['fecha'],
                    'observaciones' => $validatedData['observaciones'],
                ]);
            } catch (\Exception $e) {
                Log::error('Error al crear la salida: ' . $e->getMessage());
                throw new \Exception('Error al crear la salida: ' . $e->getMessage());
            }

            try {
                foreach ($validatedData['productos'] as $productoData) {
                    $query = Inventario::where('producto_id', $productoData['producto_id']);

                    // Aplicar condiciones solo para los campos que no son null
                    if (isset($productoData['color_id']) && $productoData['color_id'] !== null) {
                        $query->where('color_id', $productoData['color_id']);
                    } else {
                        $query->whereNull('color_id');
                    }

                    if (isset($productoData['tamano_id']) && $productoData['tamano_id'] !== null) {
                        $query->where('tamano_id', $productoData['tamano_id']);
                    } else {
                        $query->whereNull('tamano_id');
                    }

                    if (isset($productoData['longitud_id']) && $productoData['longitud_id'] !== null) {
                        $query->where('longitud_id', $productoData['longitud_id']);
                    } else {
                        $query->whereNull('longitud_id');
                    }

                    $inventario = $query->lockForUpdate()->first();


                    if (!$inventario) {
                        throw new \Exception("No se encontró inventario para el producto {$productoData['producto_id']} con las variantes especificadas.");
                    }

                    if ($inventario->cantidad < $productoData['cantidad']) {
                        throw new \Exception("Inventario insuficiente para el producto {$inventario->producto->nombre}. Stock disponible: {$inventario->cantidad}");
                    }

                    // Crear el item de salida y actualizar inventario
                    $itemSalida = ItemSalida::create([
                        'salida_producto_id' => $salida->id,
                        'inventario_id' => $inventario->id,
                        'cantidad' => $productoData['cantidad'],
                        'precio_unitario' => $productoData['precio_unitario'],
                        'igv' => 0.18,
                        'costo_total' => $productoData['cantidad'] * $productoData['precio_unitario'],
                    ]);

                    // Guardar el stock actual antes del incremento
                    $stockAnterior = $inventario->cantidad;

                    // Actualizar inventario
                    $inventario->cantidad -= $productoData['cantidad'];
                    $inventario->save();

                    // Crear registro de movimiento de inventario
                    MovimientoInventario::create([
                        'inventario_id' => $inventario->id,
                        'tipo_movimiento' => 'salida',
                        'cantidad' => $productoData['cantidad'],
                        'stock_anterior' => $stockAnterior,
                        //'usuario_id' => auth()->id(),
                        'usuario_id' => 1,
                        'fecha' => now(),
                    ]);

                    $salida->items()->save($itemSalida);
                }
            } catch (\Exception $e) {
                Log::error('Error al guardar los detalles: ' . $e->getMessage());
                throw new \Exception('Error al guardar los detalles de la entrada: ' . $e->getMessage());
            }

            DB::commit();
            return response()->json(['message' => 'Salida de producto registrada exitosamente', 'salida' => $salida], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completo al registrar la salida: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Devolver un mensaje de error más específico
            return response()->json([
                'error' => 'Error al registrar la salida: ' . $e->getMessage(),
                'details' => env('APP_DEBUG') ? $e->getTrace() : []
            ], 500);
        }
    }

    // Mostrar una salida específica
    public function show($id)
    {
        $salida = SalidaProducto::with([
            'items.inventario.producto',
            'items.inventario.color',
            'items.inventario.tamano',
            'items.inventario.longitud'
        ])->findOrFail($id);
        // Verifica el contenido de salida
        return response()->json($salida);
    }

    // Eliminar una salida de producto
    public function destroy($id)
    {
        // Iniciar transacción para asegurar la integridad de los datos
        DB::beginTransaction();
        try {
            // Encontrar la salida o fallar
            $salida = SalidaProducto::with('items')->findOrFail($id);

            // Obtener todos los items de la salida
            $items = $salida->items;

            foreach ($items as $item) {
                // Construir la consulta base para encontrar el inventario correcto
                $query = Inventario::where('producto_id', $item->producto_id);

                // Obtener las variantes del item desde MovimientoInventario
                $movimiento = MovimientoInventario::where([
                    'tipo_movimiento' => 'salida',
                    'inventario_id' => $item->inventario_id
                ])->first();

                if (!$movimiento) {
                    throw new \Exception("No se encontró el movimiento de inventario para el item {$item->id}");
                }

                // Encontrar el inventario específico
                $inventario = Inventario::findOrFail($movimiento->inventario_id);

                // Guardar el stock actual antes del incremento
                $stockAnterior = $inventario->cantidad;

                // Actualizar el inventario
                $inventario->cantidad += $item->cantidad;
                $inventario->save();

                // Registrar el movimiento de inventario de reversión
                MovimientoInventario::create([
                    'inventario_id' => $inventario->id,
                    'tipo_movimiento' => 'reversion salida',
                    'cantidad' => $item->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'usuario_id' => 1,
                    'fecha' => now(),
                    'observaciones' => "Reversión por eliminación de salida #{$salida->id}"
                ]);
            }

            // Eliminar la salida (esto eliminará también los items por la relación cascade)
            $salida->delete();

            // Confirmar la transacción
            DB::commit();

            return response()->json([
                'message' => 'Salida de producto eliminada y inventario restaurado correctamente',
                'salida_id' => $id
            ]);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            // Registrar el error
            Log::error('Error al eliminar salida de producto: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Error al eliminar la salida de producto: ' . $e->getMessage(),
                'details' => env('APP_DEBUG') ? $e->getTrace() : []
            ], 500);
        }
    }
}
