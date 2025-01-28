<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasFactory, Auditable;

    protected $table = 'permissions';

    protected $fillable = ['name', 'guard_name'];
}
