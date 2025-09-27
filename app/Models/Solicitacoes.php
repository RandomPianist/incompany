<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitacoes extends Model
{
    protected $table = 'solicitacoes';

    protected $fillable = [
        'situacao',
        'avisou',
        'data',
        'usuario_erp',
        'usuario_erp2',
        'usuario_web',
        'id_externo',
        'id_comodato',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'situacao' => 'string', // enum 'A', 'C', 'E', 'R', 'F'
        'avisou' => 'boolean',
        'data' => 'date',
        'usuario_erp' => 'string',
        'usuario_erp2' => 'string',
        'usuario_web' => 'string',
        'id_externo' => 'integer',
        'id_comodato' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
