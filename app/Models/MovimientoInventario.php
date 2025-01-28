<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';
    protected $fillable = [
        'inventario_id',
        'tipo_movimiento',
        'cantidad',
        'stock_anterior',
        'usuario_id',
        'fecha',
        'observaciones'
    ];

    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
