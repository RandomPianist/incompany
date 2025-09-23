<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $financeiro
 * @property int $atribuicoes
 * @property int $retiradas
 * @property int $pessoas
 * @property int $usuarios
 * @property int $supervisor
 * @property int $id_usuario
 * @property int $id_setor
 * @property int $created_at
 * @property int $updated_at
 */
class Permissoes extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'permissoes';

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
        'financeiro', 'atribuicoes', 'retiradas', 'pessoas', 'usuarios', 'supervisor', 'id_usuario', 'id_setor', 'created_at', 'updated_at'
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
        'id' => 'int', 'financeiro' => 'int', 'atribuicoes' => 'int', 'retiradas' => 'int', 'pessoas' => 'int', 'usuarios' => 'int', 'supervisor' => 'int', 'id_usuario' => 'int', 'id_setor' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
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
