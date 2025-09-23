<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComodatosProdutos extends Model
{
    protected $table = 'comodatos_produtos';

    protected $fillable = [
        'minimo',
        'maximo',
        'preco',
        'lixeira',
        'id_comodato',
        'id_produto',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'minimo' => 'decimal:5',
        'maximo' => 'decimal:5',
        'preco' => 'decimal:2',
        'lixeira' => 'boolean',
        'id_comodato' => 'integer',
        'id_produto' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
