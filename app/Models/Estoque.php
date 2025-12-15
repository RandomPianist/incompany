<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    protected $table = 'estoque';

    protected $fillable = [
        'es',
        'data',
        'hms',
        'descr',
        'qtd',
        'id_cp',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'es' => 'string', // enum 'E', 'S'
        'data' => 'date',
        'hms' => 'string',
        'descr' => 'string',
        'qtd' => 'decimal:5',
        'id_cp' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
