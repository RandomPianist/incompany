<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Empresas;
use App\Models\Setores;

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    
    public function empresa() {
        return $this->belongsTo(Empresas::class, "id_empresa");
    }

    public function setor() {
        return $this->belongsTo(Setores::class, "id_setor");
    }
}
