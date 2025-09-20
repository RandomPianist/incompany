<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int  $id
 * @property int  $validade
 * @property int  $obrigatorio
 * @property int  $gerado
 * @property int  $id_usuario
 * @property int  $id_atribuicao
 * @property int  $id_usuario_editando
 * @property int  $created_at
 * @property int  $updated_at
 * @property Date $data
 */
class Atbbkp extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'atbbkp';

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
        'qtd', 'data', 'validade', 'obrigatorio', 'gerado', 'id_usuario', 'id_atribuicao', 'id_usuario_editando', 'created_at', 'updated_at'
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
        'id' => 'int', 'data' => 'date', 'validade' => 'int', 'obrigatorio' => 'int', 'gerado' => 'int', 'id_usuario' => 'int', 'id_atribuicao' => 'int', 'id_usuario_editando' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'data', 'created_at', 'updated_at'
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
