<?php

namespace App\Http\Traits;

use DB;
use App\Models\Pessoas;

trait NomearTrait {
    public function nomear($id) {
        $pessoa = Pessoas::find($id);
        if (!intval($pessoa->id_empresa)) return "administrador";
        if (DB::table("users")->where("id_pessoa", $id)->exists()) return "usuário";
        if ($pessoa->supervisor) return "supervisor";
        return "funcionário";
    }
}