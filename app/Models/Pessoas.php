<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $admin
 * @property int    $created_at
 * @property int    $id_empresa
 * @property int    $id_setor
 * @property int    $lixeira
 * @property int    $senha
 * @property int    $supervisor
 * @property int    $updated_at
 * @property Date   $admissao
 * @property string $biometria
 * @property string $cpf
 * @property string $foto
 * @property string $foto64
 * @property string $funcao
 * @property string $nome
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
        'admin', 'admissao', 'biometria', 'cpf', 'created_at', 'foto', 'foto64', 'funcao', 'id_empresa', 'id_setor', 'lixeira', 'nome', 'senha', 'supervisor', 'updated_at'
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
        'id' => 'int', 'admin' => 'int', 'admissao' => 'date', 'biometria' => 'string', 'cpf' => 'string', 'created_at' => 'timestamp', 'foto' => 'string', 'foto64' => 'string', 'funcao' => 'string', 'id_empresa' => 'int', 'id_setor' => 'int', 'lixeira' => 'int', 'nome' => 'string', 'senha' => 'int', 'supervisor' => 'int', 'updated_at' => 'timestamp'
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'admissao', 'created_at', 'updated_at'
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
