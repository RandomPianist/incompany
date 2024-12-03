<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Atribuicoes;

class RetiradasController extends ControllerKX {
    public function consultar(Request $request) {
        return $this->retirada_consultar($request->atribuicao, $request->qtd);
    }

    public function salvar(Request $request) {
        $json = array(
            "id_pessoa" => $request->pessoa,
            "id_atribuicao" => $request->atribuicao,
            "id_produto" => $request->produto,
            "id_comodato" => 0,
            "qtd" => $request->quantidade,
            "data" => Carbon::createFromFormat('d/m/Y', $request->data)->format('Y-m-d')
        );
        if (intval($request->supervisor)) $json["id_supervisor"] = $request->supervisor;
        $this->retirada_salvar($json);
    }

    public function desfazer(Request $request) {
        $this->log_inserir2("D", "retiradas", "id_pessoa = ".$request->id_pessoa, "NULL");
        DB::statement("DELETE FROM retiradas WHERE id_pessoa = ".$request->id_pessoa);
    }
}