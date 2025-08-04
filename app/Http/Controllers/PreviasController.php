<?php

namespace App\Http\Controllers;

use DB;
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
        DB::statement("DELETE FROM previas WHERE ".$where);
        $this->log_inserir_lote("D", "WEB", "previas", $where);
    }

    public function preencher(Request $request) {
        $qtd = DB::table("previas")
                ->where("id_comodato", $request->id_comodato)
                ->where("id_pessoa", Auth::user()->id_pessoa)
                ->where("id_produto", $request->id_produto)
                ->where("confirmado", 0)
                ->value("qtd");
        $resultado = new \stdClass;
        $resultado->qtd = $qtd !== null ? $qtd : 0;
        $resultado->existe = $qtd !== null ? 1 : 0;
        return json_encode($resultado);
    }
}