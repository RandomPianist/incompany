<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $avisou
 * @property int    $id_externo
 * @property int    $id_comodato
 * @property int    $created_at
 * @property int    $updated_at
 * @property Date   $data
 * @property string $usuario_erp
 * @property string $usuario_erp2
 * @property string $usuario_web
 */
class Solicitacoes extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'solicitacoes';

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
        'situacao', 'avisou', 'data', 'usuario_erp', 'usuario_erp2', 'usuario_web', 'id_externo', 'id_comodato', 'created_at', 'updated_at'
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
        'id' => 'int', 'avisou' => 'int', 'data' => 'date', 'usuario_erp' => 'string', 'usuario_erp2' => 'string', 'usuario_web' => 'string', 'id_externo' => 'int', 'id_comodato' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
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
