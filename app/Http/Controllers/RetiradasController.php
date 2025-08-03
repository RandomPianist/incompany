<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RetiradasController extends ControllerKX {
    public function consultar(Request $request) {
        return $this->retirada_consultar($request->atribuicao, $request->qtd, $request->pessoa);
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
        $where = "id_pessoa = ".$request->id_pessoa;
        $this->log_inserir_lote("D", "WEB", "retiradas", $where);
        DB::statement("DELETE FROM retiradas WHERE ".$where);
    }

    public function proximas($id_pessoa) {
        $hoje = Carbon::createFromFormat('Y-m-d', date("Y-m-d"));
        $consulta = DB::table("vpendentes")
                        ->select(
                            "pessoas.nome",
                            DB::raw("IFNULL(produtos.cod_externo, produtos.id) AS id_produto"),
                            "vpendentes.descr",
                            DB::raw("IFNULL(vpendentes.referencia, '') AS referencia"),
                            "vpendentes.tamanho",
                            "vpendentes.qtd",
                            "vpendentes.proxima_retirada_real",
                            "vpendentes.obrigatorio",
                            "vpendentes.esta_pendente"
                        )
                        ->join("produtos", "produtos.id", "vpendentes.id_produto")
                        ->join("pessoas", "pessoas.id", "vpendentes.id_pessoa")
                        ->where("vpendentes.id_pessoa", $id_pessoa)
                        ->groupby(
                            "pessoas.nome",
                            "produtos.id",
                            "produtos.cod_externo",
                            "vpendentes.descr",
                            "vpendentes.referencia",
                            "vpendentes.tamanho",
                            "vpendentes.qtd",
                            "vpendentes.proxima_retirada_real",
                            "vpendentes.obrigatorio",
                            "vpendentes.esta_pendente"
                        )
                        ->orderby(DB::raw("obrigatorio DESC", "DATE(proxima_retirada_real)"))
                        ->get();
        $resultado = array();
        foreach ($consulta as $linha) {
            $proxima_retirada = Carbon::createFromFormat('Y-m-d', $linha->proxima_retirada_real);
            $dias = $proxima_retirada->diffInDays($hoje);
            if (intval($linha->esta_pendente)) $dias *= -1;
            $aux = new \stdClass;
            $aux->id_produto = $linha->id_produto;
            $aux->descr = $linha->descr;
            $aux->referencia = $linha->referencia;
            $aux->tamanho = $linha->tamanho;
            $aux->qtd = $linha->qtd;
            $aux->proxima_retirada = $proxima_retirada->format("d/m/Y");
            $aux->dias = $dias;
            $aux->nome = $nome;
            array_push($resultado, $aux);
        }
        return json_encode($resultado);
    }
}