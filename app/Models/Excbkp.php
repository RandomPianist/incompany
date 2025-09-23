<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Excbkp extends Model
{
    protected $table = 'excbkp';

    protected $fillable = [
        'id_pessoa',
        'id_setor',
        'id_usuario',
        'id_excecao',
        'id_usuario_editando',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'id_pessoa' => 'integer',
        'id_setor' => 'integer',
        'id_usuario' => 'integer',
        'id_excecao' => 'integer',
        'id_usuario_editando' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
