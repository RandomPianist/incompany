<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Atribuicoes extends Model
{
    protected $table = 'atribuicoes';

    protected $fillable = [
        'qtd',
        'data',
        'validade',
        'obrigatorio',
        'gerado',
        'rascunho',
        'lixeira',
        'id_pessoa',
        'id_setor',
        'id_maquina',
        'cod_produto',
        'referencia',
        'id_empresa',
        'id_empresa_autor',
        'id_usuario',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'qtd' => 'decimal:5',
        'data' => 'date',
        'validade' => 'integer',
        'obrigatorio' => 'boolean',
        'gerado' => 'boolean',
        'rascunho' => 'string',
        'lixeira' => 'boolean',
        'id_pessoa' => 'integer',
        'id_setor' => 'integer',
        'id_maquina' => 'integer',
        'cod_produto' => 'string',
        'referencia' => 'string',
        'id_empresa' => 'integer',
        'id_empresa_autor' => 'integer',
        'id_usuario' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
