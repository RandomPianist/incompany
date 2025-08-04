<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $created_at
 * @property int    $id_atribuicao
 * @property int    $id_comodato
 * @property int    $id_empresa
 * @property int    $id_pessoa
 * @property int    $id_setor
 * @property int    $id_produto
 * @property int    $id_supervisor
 * @property int    $numero_ped
 * @property int    $updated_at
 * @property string $biometria_ou_senha
 * @property string $ca
 * @property string $gerou_pedido
 * @property string $hora
 * @property string $observacao
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
        'biometria_ou_senha', 'ca', 'created_at', 'data', 'gerou_pedido', 'hora', 'id_atribuicao', 'id_comodato', 'id_empresa', 'id_pessoa', 'id_setor', 'id_produto', 'id_supervisor', 'numero_ped', 'observacao', 'preco', 'qtd', 'updated_at'
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
        'id' => 'int', 'biometria_ou_senha' => 'string', 'ca' => 'string', 'created_at' => 'timestamp', 'data' => 'date', 'gerou_pedido' => 'string', 'hora' => 'string', 'id_atribuicao' => 'int', 'id_comodato' => 'int', 'id_empresa' => 'int', 'id_pessoa' => 'int', 'id_produto' => 'int', 'id_supervisor' => 'int', 'id_setor' => 'int', 'numero_ped' => 'int', 'observacao' => 'string', 'updated_at' => 'timestamp'
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
