<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    // Obtener todos los pagos
    public function index()
    {
        $pagos = Pago::with('venta')->get();
        return response()->json($pagos);
    }

    // Crear un nuevo pago
    public function store(Request $request)
    {
        $validated = $request->validate([
            'venta_id' => 'required|exists:ventas,id',
            'metodo_pago' => 'required|in:efectivo,tarjeta',
            'monto_pagado' => 'required|numeric',
            'fecha_pago' => 'required|date',
        ]);

        $pago = Pago::create($validated);
        return response()->json($pago, 201);
    }

    // Obtener detalles de un pago específico
    public function show($id)
    {
        $pago = Pago::with('venta')->findOrFail($id);
        return response()->json($pago);
    }

    // Actualizar un pago
    public function update(Request $request, $id)
    {
        $pago = Pago::findOrFail($id);
        $validated = $request->validate([
            'metodo_pago' => 'in:efectivo,tarjeta',
            'monto_pagado' => 'numeric',
            'fecha_pago' => 'date',
        ]);

        $pago->update($validated);
        return response()->json($pago);
    }

    // Eliminar un pago
    public function destroy($id)
    {
        Pago::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}
