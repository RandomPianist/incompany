<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreRetiradas extends Model
{
    protected $table = 'pre_retiradas';

    protected $fillable = [
        'seq',
        'id_produto',
        'id_pessoa',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'seq' => 'integer',
        'id_produto' => 'integer',
        'id_pessoa' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
