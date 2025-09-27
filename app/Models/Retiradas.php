<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retiradas extends Model
{
    protected $table = 'retiradas';

    protected $fillable = [
        'qtd',
        'data',
        'hms',
        'observacao',
        'ca',
        'preco',
        'biometria_ou_senha',
        'numero_ped',
        'biometria',
        'id_atribuicao',
        'id_comodato',
        'id_pessoa',
        'id_supervisor',
        'id_produto',
        'id_empresa',
        'id_setor',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'qtd' => 'decimal:5',
        'data' => 'date',
        'hms' => 'string',
        'observacao' => 'string',
        'ca' => 'string',
        'preco' => 'decimal:2',
        'biometria_ou_senha' => 'string', // enum 'B', 'S'
        'numero_ped' => 'integer',
        'biometria' => 'string',
        'id_atribuicao' => 'integer',
        'id_comodato' => 'integer',
        'id_pessoa' => 'integer',
        'id_supervisor' => 'integer',
        'id_produto' => 'integer',
        'id_empresa' => 'integer',
        'id_setor' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
