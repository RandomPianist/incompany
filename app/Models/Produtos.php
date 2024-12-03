<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $consumo
 * @property int    $created_at
 * @property int    $id_categoria
 * @property int    $lixeira
 * @property int    $updated_at
 * @property int    $validade
 * @property string $ca
 * @property string $cod_externo
 * @property string $descr
 * @property string $detalhes
 * @property string $foto
 * @property string $referencia
 * @property string $tamanho
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
        'ca', 'cod_externo', 'consumo', 'created_at', 'descr', 'detalhes', 'foto', 'id_categoria', 'lixeira', 'preco', 'referencia', 'tamanho', 'updated_at', 'validade', 'validade_ca'
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
        'id' => 'int', 'ca' => 'string', 'cod_externo' => 'string', 'consumo' => 'int', 'created_at' => 'timestamp', 'descr' => 'string', 'detalhes' => 'string', 'foto' => 'string', 'id_categoria' => 'int', 'lixeira' => 'int', 'referencia' => 'string', 'tamanho' => 'string', 'updated_at' => 'timestamp', 'validade' => 'int', 'validade_ca' => 'date'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'validade_ca'
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
