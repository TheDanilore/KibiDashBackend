<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Longitud extends Model
{
    use HasFactory;

    protected $table = 'longitudes';

    protected $fillable = [
        'descripcion',
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class);
    }
}
