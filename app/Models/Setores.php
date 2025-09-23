<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setores extends Model
{
    protected $table = 'setores';

    protected $fillable = [
        'descr',
        'cria_usuario',
        'lixeira',
        'id_empresa',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'descr' => 'string',
        'cria_usuario' => 'boolean',
        'lixeira' => 'boolean',
        'id_empresa' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
