<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{
    use HasFactory;

    protected $table = 'audits';

    protected $fillable = [
        'user_id_created',
        'user_id_updated',
        'model_type',
        'model_id',
    ];

    public  function userCreated()
    {
        return $this->belongsTo(User::class, 'user_id_created');
    }

    public  function userUpdated()
    {
        return $this->belongsTo(User::class, 'user_id_updated');
    }
}
