<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $mostrar_ret
 * @property int    $lixeira
 * @property int    $id_matriz
 * @property int    $created_at
 * @property int    $updated_at
 * @property string $razao_social
 * @property string $nome_fantasia
 * @property string $cnpj
 * @property string $cod_externo
 */
class Empresas extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'empresas';

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
        'razao_social', 'nome_fantasia', 'cnpj', 'cod_externo', 'mostrar_ret', 'lixeira', 'id_matriz', 'created_at', 'updated_at'
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
        'id' => 'int', 'razao_social' => 'string', 'nome_fantasia' => 'string', 'cnpj' => 'string', 'cod_externo' => 'string', 'mostrar_ret' => 'int', 'lixeira' => 'int', 'id_matriz' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at'
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
