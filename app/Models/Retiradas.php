<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Atribuicoes;
use App\Models\Comodatos;
use App\Models\Pessoas;
use App\Models\Produtos;
use App\Models\Empresas;
use App\Models\Setores;

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

    public function atribuicao() {
        return $this->belongsTo(Atribuicoes::class, "id_atribuicao");
    }

    public function comodato() {
        return $this->belongsTo(Comodatos::class, "id_comodato");
    }

    public function pessoa() {
        return $this->belongsTo(Pessoas::class, "id_pessoa");
    }

    public function supervisor() {
        return $this->belongsTo(Pessoas::class, "id_supervisor");
    }

    public function produto() {
        return $this->belongsTo(Produtos::class, "id_produto");
    }

    public function empresa() {
        return $this->belongsTo(Empresas::class, "id_empresa");
    }

    public function setor() {
        return $this->belongsTo(Setores::class, "id_setor");
    }
}
