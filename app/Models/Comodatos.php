<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int  $id
 * @property int  $travar_ret
 * @property int  $travar_estq
 * @property int  $atb_todos
 * @property int  $validade
 * @property int  $obrigatorio
 * @property int  $id_maquina
 * @property int  $id_empresa
 * @property int  $created_at
 * @property int  $updated_at
 * @property Date $inicio
 * @property Date $fim
 * @property Date $fim_orig
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
        'inicio', 'fim', 'fim_orig', 'travar_ret', 'travar_estq', 'atb_todos', 'qtd', 'validade', 'obrigatorio', 'id_maquina', 'id_empresa', 'created_at', 'updated_at'
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
        'id' => 'int', 'inicio' => 'date', 'fim' => 'date', 'fim_orig' => 'date', 'travar_ret' => 'int', 'travar_estq' => 'int', 'atb_todos' => 'int', 'validade' => 'int', 'obrigatorio' => 'int', 'id_maquina' => 'int', 'id_empresa' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'inicio', 'fim', 'fim_orig', 'created_at', 'updated_at'
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
