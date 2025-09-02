<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $created_at
 * @property int    $id_matriz
 * @property int    $lixeira
 * @property int    $mostrar_ret
 * @property int    $travar_ret
 * @property int    $updated_at
 * @property string $cnpj
 * @property string $cod_externo
 * @property string $nome_fantasia
 * @property string $razao_social
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
        'cnpj', 'cod_externo', 'created_at', 'id_matriz', 'lixeira', 'mostrar_ret', 'nome_fantasia', 'razao_social', 'travar_ret', 'updated_at'
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
        'id' => 'int', 'cnpj' => 'string', 'cod_externo' => 'string', 'created_at' => 'timestamp', 'id_matriz' => 'int', 'lixeira' => 'int', 'mostrar_ret' => 'int', 'nome_fantasia' => 'string', 'razao_social' => 'string', 'travar_ret' => 'int', 'updated_at' => 'timestamp'
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
