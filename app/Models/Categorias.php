<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Produtos;

class Categorias extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'descr',
        'id_externo',
        'lixeira',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'descr' => 'string',
        'id_externo' => 'integer',
        'lixeira' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function produtos() {
        return $this->hasMany(Produtos::class, "id_categoria");
    }
}
