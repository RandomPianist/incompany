<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property int    $pessoa_ou_setor_valor
 * @property int    $validade
 * @property int    $lixeira
 * @property int    $created_at
 * @property int    $updated_at
 * @property int    $obrigatorio
 * @property int    $id_empresa
 * @property string $produto_ou_referencia_valor
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
        'pessoa_ou_setor_chave', 'pessoa_ou_setor_valor', 'produto_ou_referencia_chave', 'produto_ou_referencia_valor', 'qtd', 'validade', 'lixeira', 'created_at', 'updated_at', 'obrigatorio', 'id_empresa'
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
        'id' => 'int', 'pessoa_ou_setor_valor' => 'int', 'produto_ou_referencia_valor' => 'string', 'validade' => 'int', 'lixeira' => 'int', 'created_at' => 'timestamp', 'updated_at' => 'timestamp', 'obrigatorio' => 'int', 'id_empresa' => 'int'
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
