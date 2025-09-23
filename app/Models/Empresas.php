<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empresas extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'cod_externo',
        'lixeira',
        'id_matriz',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'razao_social' => 'string',
        'nome_fantasia' => 'string',
        'cnpj' => 'string',
        'cod_externo' => 'string',
        'lixeira' => 'boolean',
        'id_matriz' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
