<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Atribuicoes;
use App\Models\Comodatos;

class Maquinas extends Model
{
    protected $table = 'maquinas';

    protected $fillable = [
        'descr',
        'patrimonio',
        'id_ant',
        'lixeira',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'descr' => 'string',
        'patrimonio' => 'string',
        'id_ant' => 'integer',
        'lixeira' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function atribuicoes() {
        return $this->hasMany(Atribuicoes::class, "id_maquina");
    }

    public function comodatos() {
        return $this->hasMany(Comodatos::class, "id_maquina");
    }
}
