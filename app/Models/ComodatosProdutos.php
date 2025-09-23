<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Comodatos;
use App\Models\Produtos;
use App\Models\Estoque;

class ComodatosProdutos extends Model
{
    protected $table = 'comodatos_produtos';

    protected $fillable = [
        'minimo',
        'maximo',
        'preco',
        'lixeira',
        'id_comodato',
        'id_produto',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'minimo' => 'decimal:5',
        'maximo' => 'decimal:5',
        'preco' => 'decimal:2',
        'lixeira' => 'boolean',
        'id_comodato' => 'integer',
        'id_produto' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function comodato() {
        return $this->belongsTo(Comodatos::class, "id_comodato");
    }

    public function produto() {
        return $this->belongsTo(Produtos::class, "id_produto");
    }

    public function estoque() {
        return $this->hasMany(Estoque::class, "id_cp");
    }
}
