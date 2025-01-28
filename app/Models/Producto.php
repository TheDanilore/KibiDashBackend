<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';
    protected $fillable = [
        'id',
        'nombre',
        'descripcion',
        'categoria_producto_id',
        'unidad_medida_id',
        'proveedor_id',
        'estado',
        'ubicacion_id'
    ];

    public function inventario()
    {
        return $this->hasMany(Inventario::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function categoria()
    {
        return $this->belongsTo(CategoriaProducto::class, 'categoria_productos_id');
    }

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class, 'unidad_medida_id');
    }
    public function ubicacion()
    {
        return $this->belongsTo(Ubicacion::class, 'ubicacion_id');
    }
    public function imagenes()
    {
        return $this->hasMany(Imagen::class, 'producto_id');
    }
}
