<?php

namespace App\Http\Traits;

use DB;
use App\Models\Pessoas;

trait NomearTrait {
    public function nomear($id) {
        $pessoa = Pessoas::find($id);
        $titulo = "";
        if (!intval($pessoa->id_empresa)) $titulo = "administrador";
        elseif (DB::table("users")->where("id_pessoa", $id_pessoa)->exists()) $titulo = "usuário";
        elseif ($pessoa->supervisor) $titulo = "supervisor";
        else $titulo = "funcionário";
        return $titulo;
    }
}