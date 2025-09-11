<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $validade
 * @property int    $consumo
 * @property int    $lixeira
 * @property int    $id_categoria
 * @property int    $created_at
 * @property int    $updated_at
 * @property string $descr
 * @property string $referencia
 * @property string $cod_fab
 * @property string $ca
 * @property string $foto
 * @property string $tamanho
 * @property string $detalhes
 * @property string $cod_externo
 * @property Date   $validade_ca
 */
class Produtos extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'produtos';

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
        'descr', 'referencia', 'cod_fab', 'ca', 'validade_ca', 'foto', 'tamanho', 'detalhes', 'preco', 'prmin', 'validade', 'consumo', 'cod_externo', 'lixeira', 'id_categoria', 'created_at', 'updated_at'
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
        'id' => 'int', 'descr' => 'string', 'referencia' => 'string', 'cod_fab' => 'string', 'ca' => 'string', 'validade_ca' => 'date', 'foto' => 'string', 'tamanho' => 'string', 'detalhes' => 'string', 'validade' => 'int', 'consumo' => 'int', 'cod_externo' => 'string', 'lixeira' => 'int', 'id_categoria' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'validade_ca', 'created_at', 'updated_at'
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
