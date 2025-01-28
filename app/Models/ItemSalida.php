<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemSalida extends Model
{
    use HasFactory;

    protected $table = 'item_salidas';

    protected $fillable = [
        'salida_producto_id',
        'inventario_id',
        'cantidad',
        'precio_unitario',
        'igv',
        'costo_total',
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }
    public function salidaProducto()
    {
        return $this->belongsTo(SalidaProducto::class, 'salida_producto_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
