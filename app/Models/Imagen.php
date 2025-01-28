<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imagen extends Model
{
    use HasFactory;

    protected $table = 'imagenes';
    protected $fillable = [
        'url',
        'alt_text',
        'producto_id',
        'inventario_id'
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
    public function inventario()
    {
        return $this->belongsTo(Inventario::class, 'inventario_id');
    }
}
