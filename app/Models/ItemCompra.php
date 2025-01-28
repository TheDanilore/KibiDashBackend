<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCompra extends Model
{
    use HasFactory,Auditable;

    protected $table = 'item_compras';
    protected $fillable = [
        'compra_id',
        'producto_id',
        'cantiad',
        'precio_unitario',
        'costo_total'
    ];

    public function compra(){
        return $this->belongsTo(Compra::class);
    }
    public function producto(){
        return $this->belongsTo(Producto::class);
    }
}
