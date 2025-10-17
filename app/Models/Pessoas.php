<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Empresas;
use App\Models\Setores;
use App\Models\Dedos;

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
        'id_usuario_editando',
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
        'telefone' => 'string',
        'email' => 'string',
        'supervisor' => 'boolean',
        'lixeira' => 'boolean',
        'id_setor' => 'integer',
        'id_empresa' => 'integer',
        'id_usuario_editando' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public function empresa() {
        return $this->belongsTo(Empresas::class, "id_empresa");
    }

    public function setor() {
        return $this->belongsTo(Setores::class, "id_setor");
    }

    public function dedos() {
        return $this->hasMany(Dedos::class, "id_pessoa");
    }
}
