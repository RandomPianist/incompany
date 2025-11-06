<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Setores;

class Permissoes extends Model
{
    protected $table = 'permissoes';

    protected $fillable = [
        'financeiro',
        'atribuicoes',
        'retiradas',
        'pessoas',
        'usuarios',
        'solicitacoes',
        'id_usuario',
        'id_setor',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'financeiro' => 'boolean',
        'atribuicoes' => 'boolean',
        'retiradas' => 'boolean',
        'pessoas' => 'boolean',
        'usuarios' => 'boolean',
        'solicitacoes' => 'boolean',
        'id_usuario' => 'integer',
        'id_setor' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function setor() {
        return $this->belongsTo(Setores::class, "id_setor");
    }
}
