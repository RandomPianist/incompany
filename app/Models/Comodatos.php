<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ComodatosProdutos;

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

    public function cp($id_produto) {
        return $this->hasMany(ComodatosProdutos::class, "id_comodato")->where("id_produto", $id_produto);
    }
}
