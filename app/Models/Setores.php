<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Permissoes;
use App\Models\Pessoas;

class Setores extends Model
{
    protected $table = 'setores';

    protected $fillable = [
        'descr',
        'cria_usuario',
        'lixeira',
        'id_empresa',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'descr' => 'string',
        'cria_usuario' => 'boolean',
        'lixeira' => 'boolean',
        'id_empresa' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function permissao() {
        return $this->hasOne(Permissoes::class, "id_setor");
    }

    public function pessoas() {
        return $this->hasMany(Pessoas::class, "id_setor")->where("lixeira", 0);
    }
}
