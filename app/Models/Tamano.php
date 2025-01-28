<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tamano extends Model
{
    use HasFactory;

    protected $table = 'tamanos';

    protected $fillable = [
        'descripcion',
    ];

    public function productos()
    {
        return $this->belongsToMany(Producto::class);
    }
}
