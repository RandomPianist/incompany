<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Atribuicoes;
use App\Models\ComodatosProdutos;
use App\Models\Categorias;

class Produtos extends Model
{
    protected $table = 'produtos';

    protected $fillable = [
        'descr',
        'referencia',
        'cod_fab',
        'ca',
        'validade_ca',
        'foto',
        'tamanho',
        'detalhes',
        'preco',
        'prmin',
        'validade',
        'consumo',
        'cod_externo',
        'lixeira',
        'id_categoria',
        'id_usuario_editando',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'descr' => 'string',
        'referencia' => 'string',
        'cod_fab' => 'string',
        'ca' => 'string',
        'validade_ca' => 'date',
        'foto' => 'string',
        'tamanho' => 'string',
        'detalhes' => 'string',
        'preco' => 'decimal:2',
        'prmin' => 'decimal:2',
        'validade' => 'integer',
        'consumo' => 'boolean',
        'cod_externo' => 'string',
        'lixeira' => 'boolean',
        'id_categoria' => 'integer',
        'id_usuario_editando' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function atribuicoes_por_codigo() {
        return $this->hasMany(Atribuicoes::class, "cod_produto", "cod_externo");
    }

    public function atribuicoes_por_referencia() {
        return $this->hasMany(Atribuicoes::class, "referencia", "referencia");
    }

    public function cp($id_comodato) {
        return $this->hasMany(ComodatosProdutos::class, "id_produto")->where("id_comodato", $id_comodato);
    }

    public function categoria() {
        return $this->belongsTo(Categorias::class, "id_categoria");
    }
}
