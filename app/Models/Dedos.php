<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Pessoas;

class Dedos extends Model
{
    protected $table = 'dedos';

    protected $fillable = [
        'dedo',
        'hash',
        'imagem',
        'id_pessoa',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'id' => 'integer',
        'dedo' => 'integer',
        'hash' => 'string',
        'imagem' => 'string',
        'id_pessoa' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function pessoa() {
        return $this->belongsTo(Pessoas::class, "id_pessoa");
    }
}
