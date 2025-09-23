<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pessoas extends Model
{
    protected $table = 'pessoas';

    protected $fillable = [
        'nome',
        'cpf',
        'funcao',
        'foto',
        'senha',
        'admissao',
        'foto64',
        'biometria',
        'supervisor',
        'lixeira',
        'id_setor',
        'id_empresa',
        'id_usuario',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'nome' => 'string',
        'cpf' => 'string',
        'funcao' => 'string',
        'foto' => 'string',
        'senha' => 'string',
        'admissao' => 'date',
        'foto64' => 'string',
        'biometria' => 'string',
        'supervisor' => 'boolean',
        'lixeira' => 'boolean',
        'id_setor' => 'integer',
        'id_empresa' => 'integer',
        'id_usuario' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
