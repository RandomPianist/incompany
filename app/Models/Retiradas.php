<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $id_atribuicao
 * @property int    $id_comodato
 * @property int    $id_pessoa
 * @property int    $id_supervisor
 * @property int    $id_produto
 * @property int    $created_at
 * @property int    $updated_at
 * @property string $observacao
 * @property string $gerou_pedido
 * @property int    $numero_ped
 * @property Date   $data
 */
class Retiradas extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'retiradas';

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
        'qtd', 'id_atribuicao', 'id_comodato', 'id_pessoa', 'id_supervisor', 'id_produto', 'observacao', 'gerou_pedido', 'numero_ped', 'data', 'created_at', 'updated_at'
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
        'id' => 'int', 'id_atribuicao' => 'int', 'id_comodato' => 'int', 'id_pessoa' => 'int', 'id_supervisor' => 'int', 'id_produto' => 'int', 'observacao' => 'string', 'gerou_pedido' => 'string', 'numero_ped' => 'int', 'data' => 'date', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
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
