<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Solicitacoes;
use App\Models\Produtos;

class SolicitacoesProdutos extends Model
{
    protected $table = 'solicitacoes_produtos';

    protected $fillable = [
        'id_produto_orig',
        'qtd_orig',
        'preco_orig',
        'id_produto',
        'qtd',
        'preco',
        'origem',
        'obs',
        'id_solicitacao',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'id_produto_orig' => 'integer',
        'qtd_orig' => 'decimal:5',
        'preco_orig' => 'decimal:2',
        'id_produto' => 'integer',
        'qtd' => 'decimal:5',
        'preco' => 'decimal:2',
        'origem' => 'string',
        'obs' => 'string',
        'id_solicitacao' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function solicitacao() {
        return $this->belongsTo(Solicitacoes::class, "id_solicitacao");
    }

    public function produto() {
        return $this->belongsTo(Produtos::class, "id_produto");
    }

    public function produto_orig() {
        return $this->belongsTo(Produtos::class, "id_produto_orig");
    }
}
