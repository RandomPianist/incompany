<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Maquinas;
use App\Models\Empresas;
use App\Models\ComodatosProdutos;
use App\Models\Previas;
use App\Models\Retiradas;
use App\Models\Solicitacoes;

class Comodatos extends Model
{
    protected $table = 'comodatos';

    protected $fillable = [
        'inicio',
        'fim',
        'fim_orig',
        'travar_ret',
        'travar_estq',
        'atb_todos',
        'qtd',
        'validade',
        'obrigatorio',
        'id_maquina',
        'id_empresa',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'inicio' => 'date',
        'fim' => 'date',
        'fim_orig' => 'date',
        'travar_ret' => 'boolean',
        'travar_estq' => 'boolean',
        'atb_todos' => 'boolean',
        'qtd' => 'decimal:5',
        'validade' => 'integer',
        'obrigatorio' => 'boolean',
        'id_maquina' => 'integer',
        'id_empresa' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function maquina() {
        return $this->belongsTo(Maquinas::class, "id_maquina");
    }

    public function empresa() {
        return $this->belongsTo(Empresas::class, "id_empresa");
    }

    public function cp() {
        return $this->hasMany(ComodatosProdutos::class, "id_comodato");
    }

    public function previas() {
        return $this->hasMany(Previas::class, "id_comodato");
    }

    public function retiradas() {
        return $this->hasMany(Retiradas::class, "id_comodato");
    }

    public function solicitacoes() {
        return $this->hasMany(Solicitacoes::class, "id_comodato");
    }
}
