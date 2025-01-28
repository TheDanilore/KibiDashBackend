<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use Illuminate\Http\Request;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;

//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /* function __construct()
    {
        $this->middleware('permission:lista-permiso|ver-permiso|crear-permiso|editar-permiso|borrar-permiso', ['only' => ['index']]);
        $this->middleware('permission:crear-permiso', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-permiso', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-permiso', ['only' => ['destroy']]);
        $this->middleware('permission:ver-permiso', ['only' => ['show']]);
    } */


    public function index(Request $request)
    {
        // Establecer la cantidad de permisos por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener los permisos paginados
        $permisos = Permiso::paginate($perPage);

        // Retornar los permisos paginados en formato JSON
        return response()->json($permisos->items());
    }


    public function store(Request $request)
    {
        try {
            //mensajes personalizados
            $messages = [
                'name.required' => 'El nombre es obligatorio.',
                'name.unique' => 'El nombre ya esta en uso.',
                'name.min' => 'El nombre debe tener min 5 caracteres.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required|unique:permissions,name|min:5',
            ], $messages);



            $permiso = Permission::create([
                'name' => $request->input('name'),
                'guard_name' => $request->input('guard_name'),
            ]);

            return response()->json($permiso, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear el permiso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $permiso = Permiso::with('audit.usercreated', 'audit.userupdated')->find($id);
            return response()->json($permiso);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el permiso: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            //mensajes personalizados
            $messages = [
                'name.required' => 'El nombre es obligatorio.',
                'name.min' => 'El nombre debe tener min 5 caracteres.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'nullable|min:5',
            ], $messages);


            $permiso = Permission::findOrFail($id);

            $permiso->name = $request->input('name');
            $permiso->guard_name = $request->input('guard_name');
            $permiso->save();

            return response()->json($permiso, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al actualizar el permiso: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {

        try {
            Permiso::findOrFail($id)->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al eliminar el permiso: ' . $e->getMessage()], 500);
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
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }


    public function exportToExcel()
    {
        try {
            $permisos = Permiso::with('audit.usercreated', 'audit.userupdated')->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Nombre');
            $sheet->setCellValue('C1', 'Guard Name');
            $sheet->setCellValue('D1', 'Usuario que Creó');
            $sheet->setCellValue('E1', 'Usuario que Actualizó');
            $sheet->setCellValue('F1', 'Fecha de Creación');
            $sheet->setCellValue('G1', 'Fecha de Actualización');

            $row = 2;
            foreach ($permisos as $permiso) {
                $sheet->setCellValue('A' . $row, $permiso->id);
                $sheet->setCellValue('B' . $row, $permiso->name);
                $sheet->setCellValue('C' . $row, $permiso->guard_name);
                $sheet->setCellValue('D' . $row, $permiso->audit ? ($permiso->audit->usercreated ? $permiso->audit->usercreated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('E' . $row, $permiso->audit ? ($permiso->audit->userupdated ? $permiso->audit->userupdated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('F' . $row, $permiso->created_at);
                $sheet->setCellValue('G' . $row, $permiso->updated_at);
                $row++;
            }

            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="permisos.xlsx"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function exportToPdf()
    {
        try {
            $permisos = Permiso::with('audit.usercreated', 'audit.userupdated')->get();

            // Cargar la vista 'personas.pdf' con los datos
            $pdf = PDF::loadView('permissions.pdf', compact('permisos'));

            // Personalizar la configuración del documento PDF
            $pdf->setPaper('A4', 'landscape'); // Configurar orientación horizontal y tamaño A4

            // Descargar el PDF con un nombre de archivo específico
            return $pdf->download('permisos.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }


    public function exportToWord()
    {
        try {
            $permisos = Permiso::with('audit.usercreated', 'audit.userupdated')->get();

            $phpWord = new PhpWord();

            // Ajustar la sección actual para orientación horizontal
            $section = $phpWord->addSection([
                'orientation' => 'landscape', // Orientación horizontal
            ]);

            // Estilo para encabezados en negrita
            $headerStyle = ['bold' => true];

            $section->addText('Lista de Permisos', ['bold' => true, 'size' => 16]);

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
            $table->addCell(2000)->addText('Guard Name', $headerStyle);
            $table->addCell(3000)->addText('Usuario que Creó', $headerStyle);
            $table->addCell(3000)->addText('Usuario que Actualizó', $headerStyle);
            $table->addCell(2000)->addText('Fecha de Creación', $headerStyle);
            $table->addCell(2000)->addText('Fecha de Actualización', $headerStyle);

            foreach ($permisos as $permiso) {
                $table->addRow();
                $table->addCell(1000)->addText($permiso->id);
                $table->addCell(2000)->addText($permiso->name);
                $table->addCell(2000)->addText($permiso->guard_name);
                $table->addCell(3000)->addText($permiso->audit ? ($permiso->audit->usercreated ? $permiso->audit->usercreated->email : 'N/A') : 'N/A');
                $table->addCell(3000)->addText($permiso->audit ? ($permiso->audit->userupdated ? $permiso->audit->userupdated->email : 'N/A') : 'N/A');
                $table->addCell(2000)->addText($permiso->created_at);
                $table->addCell(2000)->addText($permiso->updated_at);
            }

            // Crear el objeto de escritura para Word
            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

            // Configurar cabeceras para la descarga del archivo
            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="permisos.docx"');
            header('Cache-Control: max-age=0');

            // Guardar el documento en la salida
            $objWriter->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }
}
