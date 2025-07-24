<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $created_at
 * @property int    $fk
 * @property int    $id_pessoa
 * @property int    $updated_at
 * @property Date   $data
 * @property string $hms
 * @property string $nome
 * @property string $origem
 * @property string $tabela
 */
class Log extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'log';

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
        'acao', 'created_at', 'data', 'fk', 'hms', 'id_pessoa', 'nome', 'origem', 'tabela', 'updated_at'
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
        'id' => 'int', 'created_at' => 'timestamp', 'data' => 'date', 'fk' => 'int', 'hms' => 'string', 'id_pessoa' => 'int', 'nome' => 'string', 'origem' => 'string', 'tabela' => 'string', 'updated_at' => 'timestamp'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'data', 'updated_at'
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
