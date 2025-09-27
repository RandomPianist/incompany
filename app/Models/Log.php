<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'log';

    protected $fillable = [
        'id_pessoa',
        'nome',
        'origem',
        'data',
        'hms',
        'acao',
        'tabela',
        'fk',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'id_pessoa' => 'integer',
        'nome' => 'string',
        'origem' => 'string',
        'data' => 'date',
        'hms' => 'string',
        'acao' => 'string', // enum 'C', 'E', 'D'
        'tabela' => 'string',
        'fk' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
