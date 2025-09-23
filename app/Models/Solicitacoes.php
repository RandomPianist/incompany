<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Comodatos;
use App\Models\SolicitacoesProdutos;

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
        'situacao' => 'string',
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

    public function comodato() {
        return $this->belongsTo(Comodatos::class, "id_comodato");
    }

    public function cp() {
        return $this->hasMany(SolicitacoesProdutos::class, "id_solicitacao");
    }
}
