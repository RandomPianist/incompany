<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $avisou
 * @property int    $created_at
 * @property int    $id_comodato
 * @property int    $id_externo
 * @property int    $updated_at
 * @property Date   $data
 * @property string $status
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
        'avisou', 'created_at', 'id_comodato', 'id_externo', 'data', 'status', 'updated_at', 'usuario_erp', 'usuario_erp2', 'usuario_web'
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
        'id' => 'int', 'avisou' => 'int', 'created_at' => 'timestamp', 'id_comodato' => 'int', 'id_externo' => 'int', 'data' => 'date', 'status' => 'string', 'updated_at' => 'timestamp', 'usuario_erp' => 'string', 'usuario_erp2' => 'string', 'usuario_web' => 'string'
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
