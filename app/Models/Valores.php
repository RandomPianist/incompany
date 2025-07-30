<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $created_at
 * @property int    $id_externo
 * @property int    $lixeira
 * @property int    $seq
 * @property int    $updated_at
 * @property string $alias
 * @property string $descr
 */
class Valores extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'valores';

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
        'alias', 'created_at', 'descr', 'id_externo', 'lixeira', 'seq', 'updated_at'
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
        'id' => 'int', 'alias' => 'string', 'created_at' => 'timestamp', 'descr' => 'string', 'id_externo' => 'int', 'lixeira' => 'int', 'seq' => 'int', 'updated_at' => 'timestamp'
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
