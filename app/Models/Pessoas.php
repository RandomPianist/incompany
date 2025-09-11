<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $supervisor
 * @property int    $lixeira
 * @property int    $id_setor
 * @property int    $id_empresa
 * @property int    $id_usuario
 * @property int    $created_at
 * @property int    $updated_at
 * @property string $nome
 * @property string $cpf
 * @property string $funcao
 * @property string $foto
 * @property string $senha
 * @property string $foto64
 * @property string $biometria
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
        'nome', 'cpf', 'funcao', 'foto', 'senha', 'admissao', 'foto64', 'biometria', 'supervisor', 'lixeira', 'id_setor', 'id_empresa', 'id_usuario', 'created_at', 'updated_at'
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
        'id' => 'int', 'nome' => 'string', 'cpf' => 'string', 'funcao' => 'string', 'foto' => 'string', 'senha' => 'string', 'admissao' => 'date', 'foto64' => 'string', 'biometria' => 'string', 'supervisor' => 'int', 'lixeira' => 'int', 'id_setor' => 'int', 'id_empresa' => 'int', 'id_usuario' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
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
