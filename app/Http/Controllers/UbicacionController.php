<?php

namespace App\Http\Controllers;

use App\Models\Ubicacion;
use Illuminate\Http\Request;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;

//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class UbicacionController extends Controller
{

    public function index(Request $request)
    {
        // Establecer la cantidad --- por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener paginados
        $ubicaciones = Ubicacion::paginate($perPage);

        // Retornar paginados en formato JSON
        return response()->json($ubicaciones->items());  // Solo devuelve los items
    }


    public function store(Request $request)
    {
        try {
            ///mensajes personalizados
            $messages = [
                'codigo.required' => 'El código es obligatorio.',
                'codigo.min' => 'El código debe contener min 5 caracteres.',
                'descripcion.min' => 'El nombre debe contener min 8 caracteres.',
                'descripcion.required' => 'El nombre es obligatorio.',
            ];
            $validatedData = $request->validate([
                'codigo' => 'required|string|max:100|min:5',
                'descripcion' => 'required|string|max:100|min:8',
            ], $messages);

            $ubicaciones = Ubicacion::create($validatedData);
            return response()->json($ubicaciones, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear la ubicacion: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $ubicacion = Ubicacion::with('audit.usercreated', 'audit.userupdated')->find($id);
            return response()->json($ubicacion);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar la Ubicación: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $ubicacion = Ubicacion::findOrFail($id);
            //mensajes personalizados
            $messages = [
                'codigo.required' => 'El código es obligatorio.',
                'codigo.min' => 'El código debe contener min 5 caracteres.',
                'descripcion.min' => 'El nombre debe contener min 8 caracteres.',
                'descripcion.required' => 'El nombre es obligatorio.',
            ];
            $validatedData = $request->validate(
                [
                    'codigo' => 'required|string|max:100|min:5',
                    'descripcion' => 'required|string|max:100|min:8',
                ],
                $messages
            );

            $ubicacion->update($validatedData);
            return response()->json($ubicacion);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al editar la ubicacion: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $ubicacion = Ubicacion::findOrFail($id);
            $ubicacion->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al eliminar la ubicacion: ' . $e->getMessage()], 500);
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
            $ubicaciones = Ubicacion::with(['audit.usercreated', 'audit.userupdated'])->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Código');
            $sheet->setCellValue('C1', 'Descripción');
            $sheet->setCellValue('D1', 'Usuario que Creó');
            $sheet->setCellValue('E1', 'Usuario que Actualizó');
            $sheet->setCellValue('F1', 'Fecha de Creación');
            $sheet->setCellValue('G1', 'Fecha de Actualización');

            $row = 2;
            foreach ($ubicaciones as $ubicacion) {
                $sheet->setCellValue('A' . $row, $ubicacion->id);
                $sheet->setCellValue('B' . $row, $ubicacion->code);
                $sheet->setCellValue('C' . $row, $ubicacion->descripcion);
                $sheet->setCellValue('D' . $row, $ubicacion->audit ? ($ubicacion->audit->usercreated ? $ubicacion->audit->usercreated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('E' . $row, $ubicacion->audit ? ($ubicacion->audit->userupdated ? $ubicacion->audit->userupdated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('F' . $row, $ubicacion->created_at);
                $sheet->setCellValue('G' . $row, $ubicacion->updated_at);
                $row++;
            }

            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="ubicaciones.xlsx"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function exportToPdf()
    {
        try {
            $ubicaciones = Ubicacion::with(['audit.usercreated', 'audit.userupdated'])->get();
            $pdf = Pdf::loadView('ubicacion.pdf', compact('ubicaciones'));

            // Personalizar la configuración del documento PDF
            $pdf->setPaper('A4', 'landscape'); // Configurar orientación horizontal y tamaño A4

            // Descargar el PDF con un nombre de archivo específico
            return $pdf->download('ubicacion.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function exportToWord()
    {
        try {
            $ubicaciones = Ubicacion::with(['audit.usercreated', 'audit.userupdated'])->get();

            $phpWord = new PhpWord();
            // Ajustar la sección actual para orientación horizontal
            $section = $phpWord->addSection([
                'orientation' => 'landscape', // Orientación horizontal
            ]);

            // Estilo para encabezados en negrita
            $headerStyle = ['bold' => true];

            $section->addText('Lista de Ubicaciones', ['bold' => true, 'size' => 16]);

            // Configurar tabla con anchos relativos
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 80,
                'width' => 100 * 50, // Ancho de la tabla en puntos
            ]);

            // Encabezados de tabla en negrita y centrados
            $table->addRow();
            $table->addCell(800)->addText('ID', $headerStyle);
            $table->addCell(2000)->addText('Código', $headerStyle);
            $table->addCell(2000)->addText('Descripción', $headerStyle);
            $table->addCell(2000)->addText('Usuario que Creó', $headerStyle);
            $table->addCell(2000)->addText('Usuario que Actualizó', $headerStyle);
            $table->addCell(2000)->addText('Fecha de Creación', $headerStyle);
            $table->addCell(2000)->addText('Fecha de Actualización', $headerStyle);

            foreach ($ubicaciones as $ubicacion) {
                $table->addRow();
                $table->addCell(800)->addText($ubicacion->id);
                $table->addCell(2000)->addText($ubicacion->code);
                $table->addCell(2000)->addText($ubicacion->descripcion);
                $table->addCell(2000)->addText($ubicacion->audit ? ($ubicacion->audit->usercreated ? $ubicacion->audit->usercreated->email : 'N/A') : 'N/A');
                $table->addCell(2000)->addText($ubicacion->audit ? ($ubicacion->audit->userupdated ? $ubicacion->audit->userupdated->email : 'N/A') : 'N/A');
                $table->addCell(2000)->addText($ubicacion->created_at);
                $table->addCell(2000)->addText($ubicacion->updated_at);
            }

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="ubicaciones.docx"');
            header('Cache-Control: max-age=0');

            $objWriter->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }
}
