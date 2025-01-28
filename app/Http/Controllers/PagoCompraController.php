<?php

namespace App\Http\Controllers;

use App\Models\PagoCompra;
use Illuminate\Http\Request;

class PagoCompraController extends Controller
{
    // Listar pagos
    public function index()
    {
        $pagos = PagoCompra::with('compra')->paginate(10);
        return response()->json($pagos);
    }

    // Registrar un pago para una compra
    public function store(Request $request)
    {
        $request->validate([
            'compra_id' => 'required|exists:compras,id',
            'monto' => 'required|numeric|min:0',
        ]);

        $pago = PagoCompra::create([
            'compra_id' => $request->compra_id,
            'monto' => $request->monto,
            'fecha' => now(),
        ]);

        return response()->json(['message' => 'Pago registrado exitosamente', 'pago' => $pago], 201);
    }

    // Mostrar un pago específico
    public function show($id)
    {
        $pago = PagoCompra::with('compra')->findOrFail($id);
        return response()->json($pago);
    }

    // Actualizar un pago
    public function update(Request $request, $id)
    {
        $pago = PagoCompra::findOrFail($id);
        $pago->update($request->all());

        return response()->json(['message' => 'Pago actualizado exitosamente']);
    }

    // Eliminar un pago
    public function destroy($id)
    {
        $pago = PagoCompra::findOrFail($id);
        $pago->delete();

        return response()->json(['message' => 'Pago eliminado']);
    }
}
