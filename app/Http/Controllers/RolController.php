<?php

namespace App\Http\Controllers;

use App\Models\EstadoRol;
use App\Models\Rol;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class RolController extends Controller
{

    /* function __construct()
    {
        $this->middleware('permission:lista-rol|ver-rol|crear-rol|editar-rol|borrar-rol', ['only' => ['index']]);
        $this->middleware('permission:crear-rol', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-rol', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-rol', ['only' => ['destroy']]);
        $this->middleware('permission:ver-rol', ['only' => ['show']]);
    } */


    public function index(Request $request)
    {
        // Establecer la cantidad de productos por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener los productos paginados
        $roles = Role::with('permissions')->paginate($perPage);

        // Retornar los productos paginados en formato JSON
        return response()->json($roles->items());
    }



    public function store(Request $request)
    {
        try {
            //mensajes personalizados
            $messages = [
                'name.required' => 'El nombre es obligatorio.',
                'permissions.required' => 'Elegir un permiso al menos es obligatorio.',
                'name.unique' => 'El nombre ya esta en uso.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required|unique:roles,name',
                'permissions' => 'required|array',
            ], $messages);


            $role = Role::create([
                'name' => $request->input('name'),
                'guard_name' => $request->input('guard_name'),
                'estado' => $request->input('estado', 'Activo'), // Asigna 'Activo' si no se proporciona estado
            ]);

            // Asigna los permisos al rol
            $role->syncPermissions($request->input('permissions'));

            return response()->json(['message' => 'Rol creado exitosamente.', 'rol' => $role], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el rol.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $rol = Rol::with('estadorol', 'audit.usercreated', 'audit.userupdated')->find($id);
            return response()->json($rol);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el rol: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        try {
            ///mensajes personalizados
            $messages = [
                'name.required' => 'El nombre es obligatorio.',
                'permissions.required' => 'Elegir un permiso al menos es obligatorio.',
                'estado.required' => 'Elegir estado es obligatorio.',
            ];
            // Validar los datos del formulario
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required',
                'permissions' => 'required|array', // Cambiado de 'permission' a 'permissions'
                'estado' => 'required',
            ], $messages);

            $role = Role::findOrFail($id);

            $role->name = $request->input('name');
            $role->guard_name = $request->input('guard_name');
            $role->estado = $request->input('estado');
            $role->save();

            // Actualizado para usar 'permissions' en lugar de 'permission'
            $role->syncPermissions($request->input('permissions'));

            return response()->json($role);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al actualizar el rol: ' . $e->getMessage()], 500);
        }
    }


    public function destroy(string $id)
    {
        try {
            DB::table('roles')->where('id', $id)->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al eliminar el rol: ' . $e->getMessage()], 500);
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
            $roles = Rol::with('estadoRol', 'audit.usercreated', 'audit.userupdated')->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Rol');
            $sheet->setCellValue('C1', 'Name');
            $sheet->setCellValue('D1', 'Estado');
            $sheet->setCellValue('E1', 'Usuario que Creó');
            $sheet->setCellValue('F1', 'Usuario que Actualizó');
            $sheet->setCellValue('G1', 'Fecha de Creación');
            $sheet->setCellValue('H1', 'Fecha de Actualización');

            $row = 2;
            foreach ($roles as $role) {
                $sheet->setCellValue('A' . $row, $role->id);
                $sheet->setCellValue('B' . $row, $role->name);
                $sheet->setCellValue('C' . $row, $role->guard_name);
                $sheet->setCellValue('D' . $row, $role->estadoRol->descripcion);
                $sheet->setCellValue('E' . $row, $role->audit ? ($role->audit->usercreated ? $role->audit->usercreated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('F' . $row, $role->audit ? ($role->audit->userupdated ? $role->audit->userupdated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('G' . $row, $role->created_at);
                $sheet->setCellValue('H' . $row, $role->updated_at);
                $row++;
            }

            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="roles.xlsx"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function exportToPdf()
    {
        try {
            $roles = Rol::with('estadoRol', 'audit.usercreated', 'audit.userupdated')->get();
            $pdf = Pdf::loadView('roles.pdf', compact('roles'));

            // Personalizar la configuración del documento PDF
            $pdf->setPaper('A4', 'landscape'); // Configurar orientación horizontal y tamaño A4

            // Descargar el PDF con un nombre de archivo específico
            return $pdf->download('roles.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function exportToWord()
    {
        try {
            $roles = Rol::with('estadoRol', 'audit.usercreated', 'audit.userupdated')->get();

            $phpWord = new PhpWord();
            // Ajustar la sección actual para orientación horizontal
            $section = $phpWord->addSection([
                'orientation' => 'landscape', // Orientación horizontal
            ]);

            // Estilo para encabezados en negrita
            $headerStyle = ['bold' => true];

            $section->addText('Lista de Roles', ['bold' => true, 'size' => 16]);

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
            $table->addCell(2000)->addText('Rol', $headerStyle);
            $table->addCell(2000)->addText('Name', $headerStyle);
            $table->addCell(2000)->addText('Estado', $headerStyle);
            $table->addCell(2000)->addText('Usuario que Creó', $headerStyle);
            $table->addCell(2000)->addText('Usuario que Actualizó', $headerStyle);
            $table->addCell(2000)->addText('Fecha de Creación', $headerStyle);
            $table->addCell(2000)->addText('Fecha de Actualización', $headerStyle);

            foreach ($roles as $rol) {
                $table->addRow();
                $table->addCell(800)->addText($rol->id);
                $table->addCell(2000)->addText($rol->name);
                $table->addCell(2000)->addText($rol->guard_name);
                $table->addCell(2000)->addText($rol->estadoRol->descripcion);
                $table->addCell(2000)->addText($rol->audit ? ($rol->audit->usercreated ? $rol->audit->usercreated->email : 'N/A') : 'N/A');
                $table->addCell(2000)->addText($rol->audit ? ($rol->audit->userupdated ? $rol->audit->userupdated->email : 'N/A') : 'N/A');
                $table->addCell(2000)->addText($rol->created_at);
                $table->addCell(2000)->addText($rol->updated_at);
            }

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="roles.docx"');
            header('Cache-Control: max-age=0');

            $objWriter->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }
}
