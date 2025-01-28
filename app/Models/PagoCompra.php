<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoCompra extends Model
{
    use HasFactory, Auditable;

    protected $table = 'pagos_compras';

    protected $fillable = [
        "compra_id",
        "monto_pagado",
        "metodo_pago",
        "fecha_pago",
    ];

    public function compra(){
        return $this->belongsTo(Compra::class);
    }
}
