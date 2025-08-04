<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $confirmado
 * @property int $created_at
 * @property int $id_comodato
 * @property int $id_pessoa
 * @property int $id_produto
 * @property int $updated_at
 */
class Previas extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'previas';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */
    protected $fillable = [
        'confirmado', 'created_at', 'id_comodato', 'id_pessoa', 'id_produto', 'qtd', 'updated_at'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'int', 'confirmado' => 'int', 'created_at' => 'timestamp', 'id_comodato' => 'int', 'id_pessoa' => 'int', 'id_produto' => 'int', 'updated_at' => 'timestamp'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var boolean
     */
    public $timestamps = false;

    // Scopes...

    // Functions ...

    // Relations ...
}
