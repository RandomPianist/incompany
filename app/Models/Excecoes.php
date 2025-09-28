<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Atribuicoes;

class Excecoes extends Model
{
    protected $table = 'excecoes';

    protected $fillable = [
        'id_atribuicao',
        'id_pessoa',
        'id_setor',
        'id_usuario',
        'rascunho',
        'lixeira',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'id_atribuicao' => 'integer',
        'id_pessoa' => 'integer',
        'id_setor' => 'integer',
        'id_usuario' => 'integer',
        'rascunho' => 'string', // enum 'C', 'E', 'R', 'S', 'T'
        'lixeira' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function atribuicao() {
        return $this->belongsTo(Atribuicoes::class, "id_excecao");
    }
}
