<?php

namespace App\Http\Controllers;

use App\Models\CategoriaProducto;
use App\Models\Imagen;
use App\Models\Inventario;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Models\Producto;
use App\Models\Movimiento;
use App\Models\MovimientoInventario;
use Illuminate\Validation\Rule;

//Imagen
use Illuminate\Support\Str;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class ProductoController extends Controller
{
    /*function __construct()
    {
        $this->middleware('permission:lista-producto|ver-producto|crear-producto|editar-producto|borrar-producto', ['only' => ['index']]);
        $this->middleware('permission:crear-producto', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-producto', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-producto', ['only' => ['destroy']]);
        $this->middleware('permission:ver-producto', ['only' => ['show']]);
    }*/


    //Mostrar todos los productos
    public function index(Request $request)
    {
        $perPage = min($request->get('per_page', 10), 100);

        $query = Producto::with(['imagenes', 'inventario'])
            ->select('productos.*')
            ->leftJoin('inventario', 'productos.id', '=', 'inventario.producto_id')
            ->groupBy('productos.id')
            ->selectRaw('SUM(inventario.cantidad) as total_stock')
            ->selectRaw('MAX(inventario.precio_unitario) as precio_unitario_maximo');

        // Filtrar por categoría
        if ($request->has('categoria')) {
            $query->where('categoria_producto_id', $request->get('categoria'));
        }

        // Filtrar por estado
        if ($request->has('estado')) {
            $query->where('estado', $request->get('estado'));
        }

        // Filtrar productos destacados
        if ($request->has('destacados') && $request->get('destacados') === 'true') {
            $query->where('destacado', true);
        }

        $productos = $query->paginate($perPage);

        return response()->json($productos);
    }



    /* public function promociones()
    {
        try {
            $promociones = Promocion::select('id', 'titulo', 'descripcion', 'buttonText', 'class')
            ->where('activa', true)
                ->get();
            return response()->json($promociones);
        } catch (\Exception $e) {
            return response()->json(['error' => 'No se pudieron cargar las promociones.'], 500);
        }
    } */

    // Mostrar un producto específico
    public function show($id)
    {
        try {
            $producto = Producto::with([
                'proveedor',
                'categoria',
                'unidadMedida',
                'imagenes',
                'ubicacion',
                'inventario.color',    // Incluir la relación color a través de inventario
                'inventario.tamano',   // Incluir la relación tamaño a través de inventario
                'inventario.longitud'  // Incluir la relación longitud a través de inventario
            ])->findOrFail($id);

            // Obtener colores, tamaños y longitudes únicos del inventario
            $colores = $producto->inventario->map(function ($inv) {
                return $inv->color ? [
                    'id' => $inv->color_id,
                    'descripcion' => $inv->color->descripcion
                ] : null;
            })->filter()->unique('id')->values();

            $tamanos = $producto->inventario->map(function ($inv) {
                return $inv->tamano ? [
                    'id' => $inv->tamano_id,
                    'descripcion' => $inv->tamano->descripcion
                ] : null;
            })->filter()->unique('id')->values();

            $longitudes = $producto->inventario->map(function ($inv) {
                return $inv->longitud ? [
                    'id' => $inv->longitud_id,
                    'descripcion' => $inv->longitud->descripcion
                ] : null;
            })->filter()->unique('id')->values();

            // Calcular el stock total y el precio
            $stock_total = $producto->inventario->sum('cantidad');
            $precio_base = $producto->inventario->min('precio_unitario');

            $productoTransformado = [
                'id' => $producto->id,
                'nombre' => $producto->nombre,
                'descripcion' => $producto->descripcion,
                'precio_unitario' => $precio_base,
                'stock' => $stock_total,
                'imagenes' => $producto->imagenes->map(function ($imagen) {
                    return [
                        'url' => $imagen->url,
                        'id' => $imagen->id
                    ];
                }),
                'colores' => $colores,
                'tamanos' => $tamanos,
                'longitudes' => $longitudes,
                // Incluir el inventario completo para validaciones posteriores
                'inventario' => $producto->inventario->map(function ($inv) {
                    return [
                        'id' => $inv->id,
                        'color_id' => $inv->color_id,
                        'tamano_id' => $inv->tamano_id,
                        'longitud_id' => $inv->longitud_id,
                        'cantidad' => $inv->cantidad,
                        'precio_unitario' => $inv->precio_unitario
                    ];
                })
            ];

            return response()->json($productoTransformado);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Producto no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el producto'], 500);
        }
    }

    public function searchByName(Request $request)
    {
        try {
            $query = $request->query('q');

            if (empty($query)) {
                return response()->json([]);
            }

            $products = Producto::where('nombre', 'like', '%' . $query . '%')
                ->with(['imagenes' => function ($query) {
                    $query->select('id', 'producto_id', 'url');
                }])
                ->select('id', 'nombre')
                ->whereHas('inventario', function ($query) {
                    $query->where('cantidad', '>', 0);
                })
                ->take(10)
                ->get();

            $transformedProducts = $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'nombre' => $product->nombre,
                    'imagen' => $product->imagenes->first() ? $product->imagenes->first()->url : null
                ];
            });

            return response()->json($transformedProducts);
        } catch (\Exception $e) {
            Log::error('Error en búsqueda: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /*     protected function storeImages($producto, $imagenes)
    {
        $inventarios = Inventario::where('producto_id', $producto->id)->get();
        Log::info('Inventarios: ' . $inventarios);

        foreach ($imagenes as $imagen) {
            $url = $imagen->store('public/productos');
            $url = str_replace('public/', 'storage/', $url);
            foreach ($inventarios as $inventario) {
                Imagen::create([
                    'producto_id' => $producto->id,
                    'inventario_id' => $inventario->id,
                    'url' => $url,
                    'alt_text' => $producto->nombre,
                ]);
            }
        }
    } */

    protected function storeImages($producto, $imagenes)
    {
        foreach ($imagenes as $imagen) {
            $url = $imagen->store('public/productos');
            $url = str_replace('public/', 'storage/', $url);

            Imagen::create([
                'producto_id' => $producto->id,
                'url' => $url,
                'alt_text' => $producto->nombre,
            ]);
        }
    }

    // Crear un nuevo producto
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            // Mensajes personalizados
            $messages = [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'El nombre ya está en uso.',
                'nombre.min' => 'El nombre debe tener al menos 4 caracteres.',
                'descripcion.required' => 'La descripción es obligatoria.',
                'descripcion.min' => 'La descripción debe tener al menos 5 caracteres.',
                'categoria_producto_id.required' => 'Elige una categoría.',
                'unidad_medida_id.required' => 'Elige una unidad de medida.',
                'proveedor_id.required' => 'Elegir El proveeodor es obligatorio',
                'ubicacion_id_id.required' => 'La ubicación es obligatoria.',
            ];

            // Validar los datos del formulario
            $request->validate([
                'nombre' => [
                    'required',
                    'string',
                    'max:100',
                    'min:4',
                    Rule::unique('productos'), // Validación de unicidad
                ],
                'descripcion' => 'required|string|max:255|min:5',
                'categoria_producto_id' => 'required|integer|exists:categoria_productos,id',
                'unidad_medida_id' => 'required|integer|exists:unidad_medidas,id',
                'proveedor_id' => 'required|integer|exists:proveedores,id',
                'ubicacion_id' => 'nullable|exists:ubicaciones,id',
                'imagenes.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validar cada imagen
                'variantes' => 'required|json',
            ], $messages);

            // Crear el producto
            $producto = Producto::create($request->only([
                'nombre',
                'descripcion',
                'proveedor_id',
                'categoria_producto_id',
                'unidad_medida_id',
                'ubicacion_id'
            ]));

            // Procesar y guardar las variantes
            $variantes = json_decode($request->variantes, true);
            foreach ($variantes as $variante) {
                Inventario::create([
                    'producto_id' => $producto->id,
                    'color_id' => $variante['color_id'],
                    'tamano_id' => $variante['tamano_id'],
                    'longitud_id' => $variante['longitud_id'],
                    'precio_unitario' => $variante['precio_unitario'],
                    'cantidad' => $variante['cantidad']
                ]);
            }

            if ($request->hasFile('imagenes')) {
                $this->storeImages($producto, $request->file('imagenes'));
            }

            DB::commit();
            return response()->json($producto, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Ocurrió un error al crear el producto: ' . $e->getMessage()
            ], 500);
        }
    }
    protected function deleteImages($imagenesAEliminar)
    {
        foreach ($imagenesAEliminar as $imagenId) {
            $imagen = Imagen::find($imagenId);
            if ($imagen) {
                // Eliminar el archivo de imagen del almacenamiento
                Log::info('Eliminando imagen de:', ['ruta' => $imagen->url]);

                Storage::delete(str_replace('storage/', 'public/', $imagen->url));
                // Elimina la imagen de la base de datos
                $imagen->delete();
            } else {
                // Log para imágenes no encontradas
                Log::warning('Imagen no encontrada:', ['id' => $imagenId]);
            }
        }
        Log::info('Imágenes a eliminar:', $imagenesAEliminar);
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // Revisa qué datos están llegando al servidor
            Log::info('Datos recibidos:', $request->all());
            $producto = Producto::findOrFail($id);

            if (!$producto) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }

            // Mensajes personalizados
            $messages = [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.unique' => 'El nombre ya está en uso.',
                'nombre.min' => 'El nombre debe tener al menos 4 caracteres.',
                'descripcion.required' => 'La descripción es obligatoria.',
                'descripcion.min' => 'La descripción debe tener al menos 5 caracteres.',
                'categoria_producto_id.required' => 'Elige una categoría.',
                'unidad_medida_id.required' => 'Elige una unidad de medida.',
                'proveedor_id.required' => 'El proveedor es obligatorio',
                'ubicacion_id.required' => 'La ubicación es obligatoria.',
                'imagenes.*.image' => 'Cada archivo debe ser una imagen.',
                'imagenes.*.mimes' => 'Cada imagen debe ser de tipo jpg, jpeg o png.',
                'imagenes.*.max' => 'Cada imagen no debe superar los 2MB.',
            ];

            // Validar los datos del formulario
            $validated = $request->validate([
                'nombre' => 'nullable|string|max:100|min:4',
                'descripcion' => 'nullable|string|max:255|min:5',
                'categoria_producto_id' => 'nullable|integer|exists:categoria_productos,id',
                'unidad_medida_id' => 'nullable|integer|exists:unidad_medidas,id',
                'proveedor_id' => 'nullable|integer|exists:proveedores,id',
                'ubicacion_id' => 'nullable|exists:ubicaciones,id',
                'estado' => 'required',
                'imagenes.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Valida cada imagen en el array
                'variantes' => 'required|json',
                'variantes.*.color_id' => 'required|integer',
                'variantes.*.tamano_id' => 'nullable|integer',
                'variantes.*.longitud_id' => 'nullable|integer',
                'variantes.*.precio_unitario' => 'required|numeric',
                'variantes.*.cantidad' => 'required|integer|min:0',
            ], $messages);

            $producto->update($request->except('imagenes', 'imagenes_a_eliminar', 'variantes'));

            // Procesar las variantes
            $variantes = json_decode($request->input('variantes'), true);

            foreach ($variantes as $varianteData) {
                // Validar que los campos requeridos de variante están presentes
                if (!isset($varianteData['color_id'], $varianteData['cantidad'], $varianteData['precio_unitario'])) {
                    throw new \InvalidArgumentException('Faltan datos en variante.');
                }

                // Obtener los valores, permitiendo que sean null
                $color_id = $varianteData['color_id'];
                $tamano_id = $varianteData['tamano_id'] ?? null; // Usar null si no existe
                $longitud_id = $varianteData['longitud_id'] ?? null; // Usar null si no existe
                $cantidad = $varianteData['cantidad'];
                $precio_unitario = $varianteData['precio_unitario'];

                $inventarioExistente = Inventario::where([
                    ['producto_id', '=', $producto->id],
                    ['color_id', '=', $varianteData['color_id']],
                    ['tamano_id', '=', $varianteData['tamano_id']],
                    ['longitud_id', '=', $varianteData['longitud_id']],
                ])->first();

                if ($inventarioExistente) {
                    // Revisar cambio en cantidad
                    $diferenciaCantidad = $varianteData['cantidad'] - $inventarioExistente->cantidad;
                    $inventarioExistente->precio_unitario = $precio_unitario;
                    Log::info("Precio unitario antes de guardar: " . $inventarioExistente->precio_unitario);
                    $inventarioExistente->save();
                    if ($diferenciaCantidad != 0) {
                        // Actualizar inventario y crear movimiento
                        $inventarioExistente->cantidad = $varianteData['cantidad'];
                        $inventarioExistente->save();

                        MovimientoInventario::create([
                            'inventario_id' => $inventarioExistente->id,
                            'tipo_movimiento' => 'ajuste',
                            'cantidad' => abs($diferenciaCantidad),
                            'stock_anterior' => $inventarioExistente->cantidad - $diferenciaCantidad,
                            'usuario_id' => 1, // Cambia a un ID de usuario dinámico
                            'fecha' => now(),
                            'observaciones' => "Ajuste de inventario para variante #{$inventarioExistente->id}"
                        ]);
                    }
                } else {
                    // Crear nuevo inventario
                    Inventario::create([
                        'producto_id' => $producto->id,
                        'color_id' => $varianteData['color_id'],
                        'tamano_id' => $varianteData['tamano_id'],
                        'longitud_id' => $varianteData['longitud_id'],
                        'precio_unitario' => $varianteData['precio_unitario'] ?? 0, // Asume 0 si no se proporciona
                        'cantidad' => $varianteData['cantidad'],
                    ]);
                }
            }

            // Eliminar imágenes si hay IDs
            if ($request->has('imagenes_a_eliminar')) {
                $this->deleteImages($request->input('imagenes_a_eliminar'));
            }

            // Si se han subido nuevas imágenes, manejarlas
            if ($request->hasFile('imagenes')) {
                // Guarda las nuevas imágenes
                foreach ($request->file('imagenes') as $imagen) {
                    $url = $imagen->store('public/productos'); // Almacena la imagen y obtiene la ruta
                    $url = str_replace('public/', 'storage/', $url); // Ajusta la URL para que sea accesible públicamente
                    // Crea un nuevo registro de imagen
                    $producto->imagenes()->create([
                        'url' => $url, // Aquí pasas el campo 'url'
                        'alt_text' => $producto->nombre // Opcional, solo si deseas añadir
                    ]);
                }
            }

            DB::commit();
            return response()->json($producto);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar el producto:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'No se pudo actualizar el producto', 'details' => $e->getMessage()], 500);
        }
    }
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            // Buscar el producto
            $producto = Producto::findOrFail($id);

            // Solo llamar a deleteImages si el producto tiene imágenes
            if ($producto->imagenes && $producto->imagenes->isNotEmpty()) {
                try {
                    // Eliminar imágenes asociadas en el almacenamiento y en la base de datos
                    $this->deleteImages($producto->imagenes->pluck('id')->toArray());
                } catch (\Exception $e) {
                    Log::error('Error al eliminar las imagenes asociadas al producto: ' . $e->getMessage());
                    throw new \Exception('Error al eliminar las imagenes asociadas al producto: ' . $e->getMessage());
                }
            } else {
                Log::info('El producto no tiene imágenes asociadas');
            }

            // Eliminar inventario relacionado al producto
            $inventario = Inventario::where('producto_id', $producto->id);

            if ($inventario) {
                $inventario->delete();
            }

            /*             // (Opcional) Eliminar movimientos de inventario relacionados al producto
            MovimientoInventario::whereHas('inventario', function ($query) use ($producto) {
                $query->where('producto_id', $producto->id);
            })->delete();
 */
            // Finalmente, eliminar el producto
            $producto->delete();

            DB::commit();
            return response()->json(
                null,
                204
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                ['error' => 'Ocurrió un error al eliminar el producto: ' . $e->getMessage()],
                500
            );
        }
    }

    public function export(Request $request)
    {
        try {
            $format = $request->query('format', 'excel'); // Default to excel if no format is specified

            switch ($format) {
                case 'pdf':
                    return $this->exportToPdf();
                case 'word':
                    return $this->exportToWord();
                default:
                    return $this->exportToExcel();
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al exportar los datos:' . $e->getMessage()], 500);
        }
    }

    public function exportToExcel()
    {
        try {
            $productos = Producto::with(['categoria', 'unidadMedida', 'audit.usercreated', 'audit.userupdated', 'estado', 'ubicacion'])->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Definir las cabeceras
            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Nombre');
            $sheet->setCellValue('C1', 'Descripción');
            $sheet->setCellValue('D1', 'Categoría');
            $sheet->setCellValue('E1', 'Unidad de Medida');
            $sheet->setCellValue('F1', 'Stock');
            $sheet->setCellValue('G1', 'Precio Unitario');
            $sheet->setCellValue('H1', 'Ubicación');
            $sheet->setCellValue('I1', 'Imagen');
            $sheet->setCellValue('J1', 'Estado');
            $sheet->setCellValue('K1', 'Usuario que Creó');
            $sheet->setCellValue('L1', 'Usuario que Actualizó');
            $sheet->setCellValue('M1', 'Fecha de Creación');
            $sheet->setCellValue('N1', 'Fecha de Actualización');

            // Agregar datos
            $row = 2;
            foreach ($productos as $producto) {
                $sheet->setCellValue('A' . $row, $producto->id);
                $sheet->setCellValue('B' . $row, $producto->nombre);
                $sheet->setCellValue('C' . $row, $producto->descripcion);
                $sheet->setCellValue('D' . $row, $producto->categoria->descripcion);
                $sheet->setCellValue('E' . $row, $producto->unidadMedida->abreviacion);
                $sheet->setCellValue('F' . $row, $producto->cantidad);
                $sheet->setCellValue('G' . $row, $producto->precio_actual);
                $sheet->setCellValue('H' . $row, $producto->ubicacion->descripcion);

                // Insertar imagen
                if (!empty($producto->imagen)) {
                    $imagePath = public_path($producto->imagen);
                    if (file_exists($imagePath)) {
                        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                        $drawing->setPath($imagePath);
                        $drawing->setCoordinates('I' . $row);
                        $drawing->setWidth(50); // Ajusta el tamaño según sea necesario
                        $drawing->setHeight(50);
                        $drawing->setWorksheet($sheet);
                    }
                }

                $sheet->setCellValue('J' . $row, $producto->estado->descripcion);
                $sheet->setCellValue('K' . $row, $producto->audit ? ($producto->audit->usercreated ? $producto->audit->usercreated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('L' . $row, $producto->audit ? ($producto->audit->userupdated ? $producto->audit->userupdated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('M' . $row, $producto->created_at);
                $sheet->setCellValue('N' . $row, $producto->updated_at);
                $row++;
            }

            // Autoajustar el ancho de las columnas
            foreach (range('A', 'N') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Crear el escritor y generar el archivo Excel
            $writer = new Xlsx($spreadsheet);

            // Configurar la respuesta HTTP para descargar el archivo
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="productos.xlsx"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al exportar los datos:' . $e->getMessage()], 500);
        }
    }

    public function exportToPdf()
    {
        try {
            $productos = Producto::with(['categoria', 'unidadMedida', 'audit.usercreated', 'audit.userupdated', 'estado', 'ubicacion'])->get();
            // Cargar la vista 'personas.pdf' con los datos
            $pdf = Pdf::loadView('productos.pdf', compact('productos'));

            // Personalizar la configuración del documento PDF
            $pdf->setPaper('A4', 'landscape'); // Configurar orientación horizontal y tamaño A4

            // Descargar el PDF con un nombre de archivo específico
            return $pdf->download('productos.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al exportar los datos:' . $e->getMessage()], 500);
        }
    }

    public function exportToWord()
    {
        try {
            $productos = Producto::with(['categoria', 'unidadMedida', 'audit.usercreated', 'audit.userupdated', 'estado', 'ubicacion'])->get();

            $phpWord = new PhpWord();

            // Ajustar la sección actual para orientación horizontal
            $section = $phpWord->addSection([
                'orientation' => 'landscape', // Orientación horizontal
            ]);

            // Estilo para encabezados en negrita
            $headerStyle = ['bold' => true];

            $section->addText('Lista de Productos', ['bold' => true, 'size' => 16]);

            // Configurar tabla con anchos relativos
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 80,
                'width' => 100 * 50, // Ancho de la tabla en puntos
            ]);


            // Encabezados de tabla en negrita y centrados
            $table->addRow();
            $table->addCell(1000)->addText('ID', $headerStyle);
            $table->addCell(2000)->addText('Nombre', $headerStyle);
            $table->addCell(2000)->addText('Descripción', $headerStyle);
            $table->addCell(2000)->addText('Categoría', $headerStyle);
            $table->addCell(2000)->addText('Unidad de Medida', $headerStyle);
            $table->addCell(2000)->addText('Stock', $headerStyle);
            $table->addCell(2000)->addText('Precio Unitario', $headerStyle);
            $table->addCell(2000)->addText('Ubicación', $headerStyle);
            $table->addCell(2000)->addText('Imagen', $headerStyle);
            $table->addCell(2000)->addText('Estado', $headerStyle);
            $table->addCell(3000)->addText('Usuario que Creó', $headerStyle);
            $table->addCell(3000)->addText('Usuario que Actualizó', $headerStyle);
            $table->addCell(2000)->addText('Fecha de Creación', $headerStyle);
            $table->addCell(2000)->addText('Fecha de Actualización', $headerStyle);

            foreach ($productos as $producto) {
                $table->addRow();
                $table->addCell(1000)->addText($producto->id);
                $table->addCell(2000)->addText($producto->nombre);
                $table->addCell(2000)->addText($producto->descripcion);
                $table->addCell(2000)->addText($producto->categoria->descripcion);
                $table->addCell(2000)->addText($producto->unidadMedida->abreviacion);
                $table->addCell(2000)->addText($producto->cantidad);
                $table->addCell(2000)->addText($producto->precio_actual);
                $table->addCell(2000)->addText($producto->ubicacion->descripcion);

                // Insertar imagen
                if (!empty($producto->imagen)) {
                    $imagePath = public_path($producto->imagen);
                    if (file_exists($imagePath)) {
                        $imageContent = file_get_contents($imagePath);
                        $table->addCell(2000)->addImage($imageContent, ['width' => 50, 'height' => 50]);
                    } else {
                        $table->addCell(2000)->addText('N/A');
                    }
                } else {
                    $table->addCell(2000)->addText('N/A');
                }

                $table->addCell(2000)->addText($producto->estado->descripcion);
                $table->addCell(3000)->addText($producto->audit ? ($producto->audit->usercreated ? $producto->audit->usercreated->email : 'N/A') : 'N/A');
                $table->addCell(3000)->addText($producto->audit ? ($producto->audit->userupdated ? $producto->audit->userupdated->email : 'N/A') : 'N/A');
                $table->addCell(2000)->addText($producto->created_at);
                $table->addCell(2000)->addText($producto->updated_at);
            }

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="productos.docx"');
            header('Cache-Control: max-age=0');

            $objWriter->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al exportar los datos:' . $e->getMessage()], 500);
        }
    }
}
