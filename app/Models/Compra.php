<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $table = 'compras';

    protected $fillable = [
        "proveedor_id",
        "total",
        "fecha_compra",
        "estado"
    ];

    public function proveedor(){
        return $this->belongsTo(Proveedor::class);
    }
    public function items(){
        return $this->hasMany(ItemCompra::class);
    }
    public function pagos(){
        return $this->hasMany(PagoCompra::class);
    }
}
