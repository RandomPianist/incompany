<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $validade
 * @property int    $obrigatorio
 * @property int    $gerado
 * @property int    $lixeira
 * @property int    $id_pessoa
 * @property int    $id_setor
 * @property int    $id_maquina
 * @property int    $id_empresa
 * @property int    $id_empresa_autor
 * @property int    $created_at
 * @property int    $updated_at
 * @property Date   $data
 * @property string $cod_produto
 * @property string $referencia
 */
class Atribuicoes extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'atribuicoes';

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
        'qtd', 'data', 'validade', 'obrigatorio', 'gerado', 'lixeira', 'id_pessoa', 'id_setor', 'id_maquina', 'cod_produto', 'referencia', 'id_empresa', 'id_empresa_autor', 'created_at', 'updated_at'
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
        'id' => 'int', 'data' => 'date', 'validade' => 'int', 'obrigatorio' => 'int', 'gerado' => 'int', 'lixeira' => 'int', 'id_pessoa' => 'int', 'id_setor' => 'int', 'id_maquina' => 'int', 'cod_produto' => 'string', 'referencia' => 'string', 'id_empresa' => 'int', 'id_empresa_autor' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
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
