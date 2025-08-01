<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Solicitacoes;

class SolicitacoesController extends ControllerKX {
    private function autorizado($solicitacao) {
        return DB::table("log")
                    ->where("fk", $solicitacao)
                    ->where("tabela", "solicitacoes")
                    ->where("acao", "C")
                    ->value("id_pessoa") == Auth::user()->id_pessoa;
    }

    public function ver(Request $request) {
        $tela = $this->sugestao_main($request);
        $resultado = $tela->resultado;
        $criterios = $tela->criterios;
        $mostrar_giro = $tela->mostrar_giro;
        if (sizeof($resultado)) return view("solicitacoes", compact("resultado", "criterios", "mostrar_giro"));
        return view("nada");
    }

    public function consultar($id_comodato) {
        // pode_cancelar
        $solicitacao = Solicitacoes::find(
            DB::table("solicitacoes")
                    ->selectRaw("MAX(id) AS id")
                    ->where("id_comodato", $id_comodato)
                    ->value("id")
        );
        if ($solicitacao === null) return 200;
        if ($solicitacao->status != "A" && $solicitacao->status != "E") return 200;
        if ($solicitacao->status == "E" && Carbon::parse($solicitacao->prazo) < Carbon::parse(date('Y-m-d'))) {
            if ($this->autorizado($solicitacao->id)) {
                $solicitacao->status = "C";
                $solicitacao->save();
                $this->log_inserir("E", "solicitacoes", $solicitacao->id);
                return 200;
            }
            return 400;
        }
        if ($solicitacao->status == "E") return Carbon::parse($solicitacao->prazo)->format("d/m/Y");
        return 400;
    }

    public function mostrar(Request $request) {
        $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
        $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
        $consulta = $request->tipo == "retirada" ?
            DB::table("retiradas")
                ->select(
                    "funcionario.nome AS funcionario",
                    DB::raw("IFNULL(supervisor.nome, '') AS supervisor"),
                    DB::raw("
                        CASE
                            WHEN log.origem = 'WEB' THEN CONCAT(autor.nome, ' em ', DATE_FORMAT(log.data, '%d/%m/%Y'))
                            ELSE ''
                        END AS autor
                    "),
                    DB::raw("DATE_FORMAT(retiradas.data, '%d/%m/%Y') AS data"),
                    DB::raw("ROUND(retiradas.qtd) AS qtd")
                )
                ->join("log", function($join) {
                    $join->on("log.fk", "retiradas.id")
                        ->where("log.tabela", "retiradas")
                        ->where("log.acao", "C");
                })
                ->join("comodatos", "comodatos.id", "retiradas.id_comodato")
                ->join("pessoas AS funcionario", "funcionario.id", "retiradas.id_pessoa")
                ->leftjoin("pessoas AS supervisor", "supervisor.id", "retiradas.id_supervisor")
                ->leftjoin("pessoas AS autor", "autor.id", "log.id_pessoa")
                ->whereRaw("((CURDATE() BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim))")
                ->whereRaw("retiradas.data >= '".$inicio."'")
                ->whereRaw("retiradas.data <= '".$fim."'")
                ->where("comodatos.id_maquina", $request->id_maquina)
                ->where("retiradas.id_produto", $request->id_produto)
        :
            DB::table("maquinas_produtos AS mp")
                ->select(
                    DB::raw("CONCAT(IFNULL(CONCAT(log.nome, ' ('), ''), log.origem, CASE WHEN log.nome IS NOT NULL THEN ')' ELSE '' END) AS origem"),
                    DB::raw("DATE_FORMAT(log.data, '%d/%m/%Y') AS data"),
                    DB::raw("ROUND(estoque.qtd) AS qtd")
                )
                ->join("estoque", "estoque.id_mp", "mp.id")
                ->join("log", function($join) {
                    $join->on("log.fk", "estoque.id")
                        ->where("log.tabela", "estoque");
                })
                ->where(function($sql) use ($request) {
                    $sql->where("estoque.es", $request->tipo == "entrada" ? "E" : "S");
                })
                ->whereRaw("log.data >= '".$inicio."'")
                ->whereRaw("log.data <= '".$fim."'")
                ->where("mp.id_maquina", $request->id_maquina)
                ->where("mp.id_produto", $request->id_produto);
        return json_encode($consulta->get());
    }

    public function meus_comodatos() {
        return json_encode(
            DB::table("comodatos")
                    ->whereRaw("((CURDATE() BETWEEN inicio AND fim) OR (CURDATE() BETWEEN inicio AND fim))")
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "comodatos.id_empresa"))
                    ->pluck("id")
                    ->toArray()
        );
    }

    public function aviso($id_comodato) {
        $solicitacao = Solicitacoes::find(
            DB::table("solicitacoes")
                    ->selectRaw("MAX(id) AS id")
                    ->where("id_comodato", $id_comodato)
                    ->value("id")
        );
        if ($solicitacao === null) return 200;
        if ($this->autorizado($solicitacao->id) && in_array($solicitacao->status, ["E", "R"]) && !intval($solicitacao->avisou)) {
            $solicitacao->avisou = 1;
            $solicitacao->save();
            $this->log_inserir("E", "solicitacoes", $solicitacao->id);
            return Carbon::parse($solicitacao->prazo)->format("d/m/Y");
        }
        return 200;
    }
}