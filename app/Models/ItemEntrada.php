<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemEntrada extends Model
{
    use HasFactory;

    protected $table = 'item_entradas';

    protected $fillable = [
        'entrada_producto_id',
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
    public function entradaProducto()
    {
        return $this->belongsTo(EntradaProducto::class, 'entrada_producto_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
