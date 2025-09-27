<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Previas extends Model
{
    protected $table = 'previas';

    protected $fillable = [
        'qtd',
        'confirmado',
        'id_comodato',
        'id_produto',
        'id_usuario',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'qtd' => 'decimal:5',
        'confirmado' => 'boolean',
        'id_comodato' => 'integer',
        'id_produto' => 'integer',
        'id_usuario' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
