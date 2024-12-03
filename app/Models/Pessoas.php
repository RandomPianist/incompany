<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $lixeira
 * @property int    $id_setor
 * @property int    $id_empresa
 * @property int    $created_at
 * @property int    $updated_at
 * @property int    $senha
 * @property int    $supervisor
 * @property string $nome
 * @property string $cpf
 * @property string $funcao
 * @property string $foto
 * @property string $foto64
 * @property Date   $admissao
 */
class Pessoas extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'pessoas';

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
        'nome', 'cpf', 'lixeira', 'id_setor', 'id_empresa', 'created_at', 'updated_at', 'funcao', 'admissao', 'senha', 'foto', 'foto64', 'supervisor'
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
        'id' => 'int', 'nome' => 'string', 'cpf' => 'string', 'lixeira' => 'int', 'id_setor' => 'int', 'id_empresa' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'funcao' => 'string', 'admissao' => 'date', 'senha' => 'int', 'foto' => 'string', 'foto64' => 'string', 'supervisor' => 'int'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at', 'updated_at', 'admissao'
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
