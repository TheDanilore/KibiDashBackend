<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    use HasFactory;

    protected $table = 'inventario';
    protected $fillable = [
        'producto_id',
        'color_id',
        'longitud_id',
        'tamano_id',
        'precio_unitario',
        'cantidad'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function longitud()
    {
        return $this->belongsTo(Longitud::class, 'longitud_id');
    }

    public function tamano()
    {
        return $this->belongsTo(Tamano::class, 'tamano_id');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class);
    }
}
