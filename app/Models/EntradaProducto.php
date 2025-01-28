<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EntradaProducto extends Model
{
    use HasFactory;

    protected $table = 'entradas_productos';

    protected $fillable = [
        'guia_remision',
        'proveedor_id',
        'tipo_entrada',
        'procedencia',
        'fecha',
        'observaciones'
    ];

    public function items()
    {
        return $this->hasMany(ItemEntrada::class);
    }
    public function inventario()
    {
        return $this->belongsTo(Inventario::class);
    }
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }



    public static function getEnumValues($column)
    {
        $columnType = DB::select("SHOW COLUMNS FROM entradas_productos WHERE Field = ?", [$column]);

        if (count($columnType) > 0) {
            $columnType = $columnType[0]->Type; // Asegurar de que hay un resultado
            preg_match("/^enum\('(.*)'\)$/", $columnType, $matches);
            if (isset($matches[1])) {
                return explode("','", $matches[1]);
            }
        }

        return []; // Devuelve un array vacío si no hay resultados
    }
}
