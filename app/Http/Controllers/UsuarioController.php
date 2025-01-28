<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

//Exportar excel
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

//Exportar en pdf
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
//Exportar en Word
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class UsuarioController extends Controller
{

    /*  function __construct()
    {
        $this->middleware('permission:lista-usuario|ver-usuario|crear-usuario|editar-usuario|borrar-usuario', ['only' => ['index']]);
        $this->middleware('permission:crear-usuario', ['only' => ['create', 'store']]);
        $this->middleware('permission:editar-usuario', ['only' => ['edit', 'update']]);
        $this->middleware('permission:borrar-usuario', ['only' => ['destroy']]);
        $this->middleware('permission:ver-usuario', ['only' => ['show']]);
    } */
    public function index(Request $request)
    {
        // Establecer la cantidad --- por página, por defecto 15
        $perPage = $request->get('per_page', 15);

        // Obtener paginados
        $usuarios = User::with('roles')->paginate($perPage);

        // Retornar paginados en formato JSON
        return response()->json($usuarios->items());
    }



    public function store(Request $request)
    {
        try {
            Log::info('Datos recibidos:', $request->all());
            //mensajes personalizados
            $messages = [
                'name.required' => 'El apodo es obligatorio.',
                'name.unique' => 'El apodo ya está en uso.',
                'email.required' => 'El correo es obligatorio.',
                'email.unique' => 'El correo ya está en uso.',
                'password.required' => 'La contraseña es obligatoria.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|unique:users,email|min:10',
                'password' => 'required|same:password_confirmation|min:8',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'estado' => 'nullable|in:Activo,Desactivado',
                'roles' => 'nullable|array',
                'roles.*' => 'exists:roles,name'
            ], $messages);


            $input = $request->except('roles');
            $input['password'] = Hash::make($request->password);
            $input['estado'] = $input['estado'] ?? 'Activo';


            //Para manejar la imagen
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $input['avatar'] = $avatarPath;
            }

            // Crear un nuevo usuario
            $user = User::create($input);

            // Asignar roles si se proporcionaron
            if ($request->has('roles')) {
                $roles = $request->input('roles');
                if (!empty($roles)) {
                    $user->syncRoles($roles);
                }
            }


            return response()->json($user->load('roles'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al crear un usuario: ' . $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $usuario = User::findOrFail($id);
            return response()->json($usuario);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al mostrar el usuario: ' . $e->getMessage()], 500);
        }
    }

    /* public function edit($id)
    {
        try {
            $user = User::find($id);
            $roles = Role::where('estado_rol_id', 1)->pluck('name', 'name')->all();
            $userRole = $user->roles->pluck('name', 'name')->all();
            return view('usuarios.editar', compact('user', 'roles', 'userRole', 'personas', 'unidades'));
        } catch (\Exception $e) {
            return redirect()->route('usuarios.index')->with('error', 'Ocurrió un error al cargar la vista de edición: ' . $e->getMessage());
        }
    } */

    public function update(Request $request, string $id)
    {
        try {
            //mensajes personalizados
            $messages = [
                'name.required' => 'El apodo es obligatorio.',
                'email.required' => 'El correo es obligatorio.',
                'password.required' => 'La contraseña es obligatoria.',
                'roles.required' => 'Elegir el rol es obligatorio.',
            ];
            // Validar los datos del formulario
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|min:2',
                'email' => 'required|email|exists:users,email|min:10',
                'password' => 'nullable|same:password_confirmation|min:8',
                'roles' => '',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'estado' => 'required',
            ], $messages);

            $user = User::find($id);
            $input = $validatedData;

            if (!empty($input['password'])) {
                $input['password'] = Hash::make($input['password']);
            } else {
                $input = Arr::except($input, ['password']);
            }

            //Para manejar la imagen
            if ($request->hasFile('avatar')) {
                // Eliminar el avatar anterior si existe
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $avatarPath = $request->file('avatar')->store('avatars', 'public');
                $input['avatar'] = $avatarPath;
            }

            $user->update($input);

            DB::table('model_has_roles')->where('model_id', $id)->delete();

            // Asignar roles si se proporcionaron
            if ($request->has('roles')) {
                $roles = $request->input('roles');
                if (!empty($roles)) {
                    $user->syncRoles($roles);
                }
            }

            return response()->json($user->load('roles'), 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al actualizar un usuario: ' . $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error al eliminar el usuario: ' . $e->getMessage()], 500);
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
            $usuarios = User::with(['roles', 'unidad.dependencia', 'audit.usercreated', 'audit.userupdated', 'estadoUsuario', 'persona'])->get();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'ID');
            $sheet->setCellValue('B1', 'Apodo');
            $sheet->setCellValue('C1', 'Correo');
            $sheet->setCellValue('D1', 'Persona');
            $sheet->setCellValue('E1', 'Rol');
            $sheet->setCellValue('F1', 'Unidad');
            $sheet->setCellValue('G1', 'Dependencia');
            $sheet->setCellValue('H1', 'Avatar');
            $sheet->setCellValue('I1', 'Estado');
            $sheet->setCellValue('J1', 'Usuario que Creó');
            $sheet->setCellValue('K1', 'Usuario que Actualizó');
            $sheet->setCellValue('L1', 'Fecha de Creación');
            $sheet->setCellValue('M1', 'Fecha de Actualización');

            $row = 2;
            foreach ($usuarios as $usuario) {
                $sheet->setCellValue('A' . $row, $usuario->id);
                $sheet->setCellValue('B' . $row, $usuario->name);
                $sheet->setCellValue('C' . $row, $usuario->email);
                $sheet->setCellValue('D' . $row, $usuario->persona->primer_nombre . ' ' . $usuario->persona->segundo_nombre . ' ' . $usuario->persona->apellido_paterno . ' ' . $usuario->persona->apellido_materno);
                $sheet->setCellValue('E' . $row, $usuario->roles->pluck('name')->implode(', '));
                $sheet->setCellValue('F' . $row, $usuario->unidad->descripcion ?? 'N/A');
                $sheet->setCellValue('G' . $row, $usuario->unidad->dependencia->nombre ?? 'N/A');

                // Insertar imagen
                if (!empty($usuario->avatar)) {
                    $imagePath = public_path($usuario->avatar);
                    if (file_exists($imagePath)) {
                        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                        $drawing->setPath($imagePath);
                        $drawing->setCoordinates('H' . $row);
                        $drawing->setWidth(50); // Ajusta el tamaño según sea necesario
                        $drawing->setHeight(50);
                        $drawing->setWorksheet($sheet);
                    }
                }

                $sheet->setCellValue('I' . $row, $usuario->estadoUsuario->descripcion);
                $sheet->setCellValue('J' . $row, $usuario->audit ? ($usuario->audit->usercreated ? $usuario->audit->usercreated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('K' . $row, $usuario->audit ? ($usuario->audit->userupdated ? $usuario->audit->userupdated->email : 'N/A') : 'N/A');
                $sheet->setCellValue('L' . $row, $usuario->created_at);
                $sheet->setCellValue('M' . $row, $usuario->updated_at);

                $row++;
            }

            foreach (range('A', 'M') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $writer = new Xlsx($spreadsheet);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="usuarios.xlsx"');
            header('Cache-Control: max-age=0');

            $writer->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }


    public function exportToPdf()
    {
        try {
            $usuarios = User::with(['roles', 'unidad.dependencia', 'estadoUsuario', 'persona'])->get();
            $pdf = Pdf::loadView('usuarios.pdf', compact('usuarios'));

            // Personalizar la configuración del documento PDF
            $pdf->setPaper('A4', 'landscape'); // Configurar orientación horizontal y tamaño A4

            // Descargar el PDF con un nombre de archivo específico
            return $pdf->download('usuarios.pdf');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }

    public function exportToWord()
    {
        try {
            $usuarios = User::with(['roles', 'unidad.dependencia', 'estadoUsuario', 'persona'])->get();

            $phpWord = new PhpWord();
            // Ajustar la sección actual para orientación horizontal
            $section = $phpWord->addSection([
                'orientation' => 'landscape', // Orientación horizontal
            ]);

            // Estilo para encabezados en negrita
            $headerStyle = ['bold' => true];

            $section->addText('Lista de Usuarios', ['bold' => true, 'size' => 16]);

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
            $table->addCell(2000)->addText('Apodo', $headerStyle);
            $table->addCell(2000)->addText('Correo', $headerStyle);
            $table->addCell(4000)->addText('Persona', $headerStyle);
            $table->addCell(2000)->addText('Rol', $headerStyle);
            $table->addCell(2000)->addText('Unidad', $headerStyle);
            $table->addCell(2000)->addText('Dependencia', $headerStyle);
            $table->addCell(2000)->addText('Avatar', $headerStyle);
            $table->addCell(2000)->addText('Estado', $headerStyle);

            foreach ($usuarios as $usuario) {
                $table->addRow();
                $table->addCell(800)->addText($usuario->id);
                $table->addCell(2000)->addText($usuario->name);
                $table->addCell(2000)->addText($usuario->email);
                $table->addCell(4000)->addText($usuario->persona->primer_nombre . ' ' . $usuario->persona->segundo_nombre . ' ' . $usuario->persona->apellido_paterno . ' ' . $usuario->persona->apellido_materno);
                $table->addCell(2000)->addText($usuario->roles->pluck('name')->implode(', '));
                $table->addCell(2000)->addText($usuario->unidad->descripcion ?? 'N/A');
                $table->addCell(2000)->addText($usuario->unidad->dependencia->nombre ?? 'N/A');

                // Insertar imagen
                if (!empty($usuario->avatar)) {
                    $imagePath = public_path($usuario->avatar);
                    if (file_exists($imagePath)) {
                        $imageContent = file_get_contents($imagePath);
                        $table->addCell(2000)->addImage($imageContent, ['width' => 50, 'height' => 50]);
                    } else {
                        $table->addCell(2000)->addText('N/A');
                    }
                } else {
                    $table->addCell(2000)->addText('N/A');
                }

                $table->addCell(2000)->addText($usuario->estadoUsuario->descripcion);
            }

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

            header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            header('Content-Disposition: attachment;filename="usuarios.docx"');
            header('Cache-Control: max-age=0');

            $objWriter->save('php://output');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Ocurrió un error: ' . $e->getMessage()], 500);
        }
    }
}
