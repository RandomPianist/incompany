<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int  $id
 * @property int  $created_at
 * @property int  $id_empresa
 * @property int  $id_maquina
 * @property int  $updated_at
 * @property Date $fim
 * @property Date $fim_orig
 * @property Date $inicio
 */
class Comodatos extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'comodatos';

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
        'created_at', 'fim', 'fim_orig', 'id_empresa', 'id_maquina', 'inicio', 'updated_at'
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
        'id' => 'int', 'created_at' => 'timestamp', 'fim' => 'date', 'fim_orig' => 'date', 'id_empresa' => 'int', 'id_maquina' => 'int', 'inicio' => 'date', 'updated_at' => 'timestamp'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'fim', 'fim_orig', 'inicio', 'updated_at'
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
