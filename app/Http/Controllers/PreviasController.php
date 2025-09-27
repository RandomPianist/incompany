<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Previas;
use Illuminate\Http\Request;

class PreviasController extends Controller {
    public function salvar(Request $request) {
        Previas::where("confirmado", 0)
                ->where("id_usuario", Auth::user()->id)
                ->where("id_comodato", $request->id_comodato)
                ->where("id_produto", $request->id_produto)
                ->value("id");
        $id_previa = $id === null ? 0 : $id;
        $linha = Previas::firstOrNew(["id" => $id_previa]);
        $linha->id_usuario = Auth::user()->id;
        $linha->id_comodato = $request->id_comodato;
        $linha->id_produto = $request->id_produto;
        $linha->qtd = $request->qtd;
        $linha->save();
        $this->log_inserir($id === null ? "C" : "E", "previas", $linha->id); // App\Http\Controllers\Controller.php
    }

    public function excluir(Request $request) {
        $where = "id_comodato = ".$request->id_comodato." AND id_usuario = ".Auth::user()->id;
        $this->log_inserir_lote("D", "previas", $where); // App\Http\Controllers\Controller.php
        Previas::whereRaw($where)->delete();
    }

    public function preencher(Request $request) {
        return json_encode(
            DB::table("previas")
                ->select(
                    "id_produto",
                    "qtd"
                )
                ->where("id_comodato", $request->id_comodato)
                ->where("id_usuario", Auth::user()->id)
                ->whereIn("id_produto", explode(",", $request->produtos))
                ->where("confirmado", 0)
                ->get()
        );
    }
}