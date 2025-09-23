<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ComodatosProdutos;

class Estoque extends Model
{
    protected $table = 'estoque';

    protected $fillable = [
        'es',
        'data',
        'hms',
        'descr',
        'qtd',
        'preco',
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
        'preco' => 'decimal:2',
        'id_cp' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function cp() {
        return $this->belongsTo(ComodatosProdutos::class, "id_cp");
    }
}
