<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_pessoa
 * @property int $id_setor
 * @property int $id_usuario
 * @property int $id_excecao
 * @property int $id_usuario_editando
 * @property int $created_at
 * @property int $updated_at
 */
class Excbkp extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'excbkp';

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
        'id_pessoa', 'id_setor', 'id_usuario', 'id_excecao', 'id_usuario_editando', 'created_at', 'updated_at'
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
        'id' => 'int', 'id_pessoa' => 'int', 'id_setor' => 'int', 'id_usuario' => 'int', 'id_excecao' => 'int', 'id_usuario_editando' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp'
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
