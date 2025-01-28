<?php

namespace App\Http\Controllers;

use App\Models\EntradaProducto;
use App\Models\Inventario;
use App\Models\ItemEntrada;
use App\Models\Movimiento;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class EntradaProductoController extends Controller
{
    // Listar todas las entradas de productos con paginación
    public function index()
    {
        $entradas = EntradaProducto::with(['items.inventario.producto', 'proveedor'])->paginate(10);
        return response()->json($entradas);
    }

    public function obtenerTiposEntrada()
    {
        $tiposEntrada = EntradaProducto::getEnumValues('tipo_entrada');
        return response()->json($tiposEntrada);
    }

    // Registrar una nueva entrada de producto
    public function store(Request $request)
    {
        try {
            // Primero verificamos que estamos recibiendo los datos esperados
            Log::info('Datos recibidos:', $request->all());
            DB::beginTransaction();

            // Validación con manejo de errores más detallado
            try {
                $validatedData = $request->validate([
                    'guia_remision' => [
                        'required',
                        'string',
                        'max:20',
                        'min:4',
                        Rule::unique('entradas_productos'),
                    ],
                    'proveedor_id' => 'required|integer|exists:proveedores,id',
                    'productos' => 'required|array|min:1',
                    'productos.*.producto_id' => 'required|integer|exists:productos,id',
                    'productos.*.cantidad' => 'required|integer|min:1',
                    'productos.*.precio_unitario' => 'required|numeric|min:0.01',
                    'productos.*.color_id' => 'nullable|integer|exists:colores,id',
                    'productos.*.tamano_id' => 'nullable|integer|exists:tamanos,id',
                    'productos.*.longitud_id' => 'nullable|integer|exists:longitudes,id',
                    'tipo_entrada' => 'required|string|max:50',
                    'procedencia' => 'required|string|max:100|min:4',
                    'fecha' => 'required|date',
                    'observaciones' => 'nullable|string|max:250',
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Error de validación: ' . json_encode($e->errors()));
                throw $e;
            }

            try {
                // Crear la entrada de producto
                $entrada = EntradaProducto::create([
                    'guia_remision' => $validatedData['guia_remision'],
                    'proveedor_id' => $validatedData['proveedor_id'],
                    'tipo_entrada' => $validatedData['tipo_entrada'],
                    'procedencia' => $validatedData['procedencia'],
                    'fecha' => $validatedData['fecha'],
                    'observaciones' => $validatedData['observaciones'],
                ]);
            } catch (\Exception $e) {
                Log::error('Error al crear la entrada: ' . $e->getMessage());
                throw new \Exception('Error al crear la entrada: ' . $e->getMessage());
            }

            try {
                foreach ($validatedData['productos'] as $productoData) {
                    // Buscar el inventario existente por atributos sin incluir el precio_unitario
                    $inventario = Inventario::firstOrCreate(
                        [
                            'producto_id' => $productoData['producto_id'],
                            'color_id' => $productoData['color_id'],
                            'tamano_id' => $productoData['tamano_id'],
                            'longitud_id' => $productoData['longitud_id'],
                        ],
                        [
                            'cantidad' => 0,
                            'precio_unitario' => $productoData['precio_unitario'] // Añadir precio_unitario aquí
                        ]
                    );

                    $itemEntrada = ItemEntrada::create([
                        'entrada_producto_id' => $entrada->id,
                        'inventario_id' => $inventario->id,
                        'cantidad' => $productoData['cantidad'],
                        'precio_unitario' => $productoData['precio_unitario'],
                        'igv' => 0.18,
                        'costo_total' => $productoData['cantidad'] * $productoData['precio_unitario'],
                    ]);

                    $producto = Producto::findOrFail($productoData['producto_id']);

                    if ($producto->proveedor_id != null) {
                        $producto->proveedor_id = $validatedData['proveedor_id'];
                    }

                    // Guardar el stock actual antes del incremento
                    $stockAnterior = $inventario->cantidad;

                    // Actualizar la cantidad en el inventario
                    $inventario->increment('cantidad', $productoData['cantidad']);
                    $inventario->precio_unitario = $productoData['precio_unitario'];
                    $inventario->save();

                    // Crear registro de movimiento de inventario
                    MovimientoInventario::create([
                        'inventario_id' => $inventario->id, // El inventario se crea automáticamente
                        'tipo_movimiento' => 'entrada',
                        'cantidad' => $productoData['cantidad'],
                        'stock_anterior' => $stockAnterior,
                        'usuario_id' => 1,
                        'fecha' => now(),
                    ]);

                    $producto->save();
                    $entrada->items()->save($itemEntrada);
                }
            } catch (\Exception $e) {
                Log::error('Error al guardar los detalles: ' . $e->getMessage());
                throw new \Exception('Error al guardar los detalles de la entrada: ' . $e->getMessage());
            }

            DB::commit();
            return response()->json(['message' => 'Entrada de producto registrada exitosamente', 'entrada' => $entrada], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error completo al registrar la entrada: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Devolver un mensaje de error más específico
            return response()->json([
                'error' => 'Error al registrar la entrada: ' . $e->getMessage(),
                'details' => env('APP_DEBUG') ? $e->getTrace() : []
            ], 500);
        }
    }

    // Mostrar una entrada específica
    public function show($id)
    {
        $entrada = EntradaProducto::with([
            'proveedor',
            'items.inventario.producto',
            'items.inventario.color',
            'items.inventario.tamano',
            'items.inventario.longitud'
        ])->findOrFail($id);
        // Verifica el contenido de salida
        return response()->json($entrada);
    }

    // Eliminar una entrada de producto
    public function destroy($id)
    {
        // Iniciar transacción para asegurar la integridad de los datos
        DB::beginTransaction();
        try {
            // Encontrar la entrada con sus items relacionados
            $entrada = EntradaProducto::with('items')->findOrFail($id);

            // Obtener todos los items de la entrada
            $items = $entrada->items;

            foreach ($items as $item) {
                // Buscar el movimiento de inventario correspondiente
                $movimiento = MovimientoInventario::where([
                    'tipo_movimiento' => 'entrada',
                    'inventario_id' => $item->inventario_id
                ])->first();

                if (!$movimiento) {
                    throw new \Exception("No se encontró el movimiento de inventario para el item {$item->id}");
                }

                // Encontrar el inventario específico
                $inventario = Inventario::findOrFail($movimiento->inventario_id);

                // Verificar que haya suficiente stock para revertir
                if ($inventario->cantidad < $item->cantidad) {
                    throw new \Exception("No hay suficiente stock para revertir la entrada del producto {$item->producto_id}. Stock actual: {$inventario->cantidad}, Cantidad a revertir: {$item->cantidad}");
                }

                // Guardar el stock actual antes del decremento
                $stockAnterior = $inventario->cantidad;

                // Actualizar el inventario
                $inventario->cantidad -= $item->cantidad;
                //$inventario->precio_unitario = $item->precio_unitario;
                $inventario->save();

                // Registrar el movimiento de inventario de reversión
                MovimientoInventario::create([
                    'inventario_id' => $inventario->id,
                    'tipo_movimiento' => 'reversion entrada',
                    'cantidad' => $item->cantidad,
                    'stock_anterior' => $stockAnterior,
                    'usuario_id' => 1,
                    'fecha' => now(),
                    'observaciones' => "Reversión por eliminación de entrada #{$entrada->id}"
                ]);
            }

            // Eliminar la entrada (esto eliminará también los items por la relación cascade)
            $entrada->delete();

            // Confirmar la transacción
            DB::commit();

            return response()->json([
                'message' => 'Entrada de producto eliminada y inventario actualizado correctamente',
                'entrada_id' => $id
            ]);
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            // Registrar el error
            Log::error('Error al eliminar entrada de producto: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Error al eliminar la entrada de producto: ' . $e->getMessage(),
                'details' => env('APP_DEBUG') ? $e->getTrace() : []
            ], 500);
        }
    }
}
