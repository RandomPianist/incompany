<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produtos extends Model
{
    protected $table = 'produtos';

    protected $fillable = [
        'descr',
        'referencia',
        'cod_fab',
        'ca',
        'validade_ca',
        'foto',
        'tamanho',
        'detalhes',
        'preco',
        'prmin',
        'validade',
        'consumo',
        'cod_externo',
        'lixeira',
        'id_categoria',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'descr' => 'string',
        'referencia' => 'string',
        'cod_fab' => 'string',
        'ca' => 'string',
        'validade_ca' => 'date',
        'foto' => 'string',
        'tamanho' => 'string',
        'detalhes' => 'string',
        'preco' => 'decimal:2',
        'prmin' => 'decimal:2',
        'validade' => 'integer',
        'consumo' => 'boolean',
        'cod_externo' => 'string',
        'lixeira' => 'boolean',
        'id_categoria' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
