<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Previas;
use Illuminate\Http\Request;

class PreviasController extends ControllerKX {
    public function salvar(Request $request) {
        $id = DB::table("previas")
                ->where("id_comodato", $request->id_comodato)
                ->where("id_pessoa", Auth::user()->id_pessoa)
                ->where("id_produto", $request->id_produto)
                ->where("confirmado", 0)
                ->value("id");
        $id_previa = $id === null ? 0 : $id;
        $linha = Previas::firstOrNew(["id" => $id_previa]);
        $linha->id_comodato = $request->id_comodato;
        $linha->id_pessoa = Auth::user()->id_pessoa;
        $linha->id_produto = $request->id_produto;
        $linha->qtd = $request->qtd;
        $linha->save();
        $this->log_inserir($id === null ? "C" : "E", "previas", $linha->id);
    }

    public function excluir(Request $request) {
        $where = "id_comodato = ".$request->id_comodato." AND id_pessoa = ".Auth::user()->id_pessoa;
        $this->log_inserir_lote("D", "WEB", "previas", $where);
        DB::statement("DELETE FROM previas WHERE ".$where);
    }

    public function preencher(Request $request) {
        return json_encode(
            DB::table("previas")
                ->select(
                    "id_produto",
                    "qtd"
                )
                ->where("id_comodato", $request->id_comodato)
                ->where("id_pessoa", Auth::user()->id_pessoa)
                ->whereIn("id_produto", explode(",", $request->produtos))
                ->where("confirmado", 0)
                ->get()
        );
    }
}