<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personalizacion extends Model
{
    use HasFactory;

    protected $table = 'personalizaciones';

    protected $fillable = [
        'imagen_frontal',
        'texto_frontal',
        'imagen_trasera',
        'texto_trasera',
        'precio_adicional'
    ];
}
