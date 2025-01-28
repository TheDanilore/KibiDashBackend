<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalidaProducto extends Model
{
    use HasFactory;

    protected $table = 'salidas_productos';


    protected $fillable = [
        'guia_salida',
        'tipo_salida',
        'destino',
        'fecha',
        'observaciones'
    ];


    public function items()
    {
        return $this->hasMany(ItemSalida::class);
    }

    public static function getEnumValues($column)
    {
        $columnType = DB::select("SHOW COLUMNS FROM salidas_productos WHERE Field = ?", [$column]);

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
