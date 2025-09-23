<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Atribuicoes;

class Atbbkp extends Model
{
    protected $table = 'atbbkp';

    protected $fillable = [
        'qtd',
        'data',
        'validade',
        'obrigatorio',
        'gerado',
        'id_usuario',
        'id_atribuicao',
        'id_usuario_editando',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'qtd' => 'decimal:5',
        'data' => 'date',
        'validade' => 'integer',
        'obrigatorio' => 'boolean',
        'gerado' => 'boolean',
        'id_usuario' => 'integer',
        'id_atribuicao' => 'integer',
        'id_usuario_editando' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function atribuicao() {
        return $this->belongsTo(Atribuicoes::class, "id_atribuicao");
    }
}
