<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pessoas;

class Empresas extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'cnpj',
        'cod_externo',
        'lixeira',
        'id_matriz',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'razao_social' => 'string',
        'nome_fantasia' => 'string',
        'cnpj' => 'string',
        'cod_externo' => 'string',
        'lixeira' => 'boolean',
        'id_matriz' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function matriz() {
        return $this->belongsTo(Empresas::class, "id_matriz");
    }

    public function filiais() {
        return $this->hasMany(Empresas::class, "id_matriz")->where("lixeira", 0);
    }

    public function pessoas() {
        return $this->hasMany(Pessoas::class, "id_empresa")->where("lixeira", 0);
    }
}
