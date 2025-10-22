<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Previas;
use App\Models\Pessoas;
use App\Models\Produtos;
use App\Models\Permissoes;
use App\Models\Solicitacoes;
use App\Models\SolicitacoesProdutos;

class SolicitacoesController extends Controller {
    private function consultar_main($id_comodato) {
        $resultado = new \stdClass;
        $solicitacao = Solicitacoes::find(
            DB::table("solicitacoes")
                    ->selectRaw("MAX(id) AS id")
                    ->where("id_comodato", $id_comodato)
                    ->value("id")
        );
        if ($solicitacao === null) {
            $resultado->continuar = 1;
            return $resultado;
        }
        if (!in_array($solicitacao->situacao, ["A", "E"])) {
            $resultado->continuar = 1;
            return $resultado;
        }
        $id_autor = $this->obter_autor_da_solicitacao($solicitacao->id); // App\Http\Controllers\Controller.php
        $resultado->continuar = 0;
        $resultado->status = $solicitacao->situacao;
        $resultado->data = DB::table("solicitacoes")
                                ->selectRaw("DATE_FORMAT(DATE(solicitacoes.created_at), '%d/%m/%Y') AS data")
                                ->where("id", $solicitacao->id)
                                ->value("data");
        $resultado->autor = Pessoas::find($id_autor)->nome;
        $resultado->sou_autor = Auth::user()->id_pessoa == $id_autor ? 1 : 0;
        $resultado->id = $solicitacao->id;
        return $resultado;
    }

    public function ver(Request $request) {
        if ($this->extrato_consultar_main($request)->el) return 401; // App\Http\Controllers\Controller.php
        $tela = $this->sugestao_main($request); // App\Http\Controllers\Controller.php
        $resultado = $tela->resultado;
        $criterios = $tela->criterios;
        if (sizeof($resultado)) return view("solicitacoes", compact("resultado", "criterios"));
        return $this->view_mensagem("warning", "Não há nada para exibir"); // App\Http\Controllers\Controller.php
    }

    public function consultar($id_comodato) {
        return json_encode($this->consultar_main($id_comodato));
    }

    public function mostrar(Request $request) {
        $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
        $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
        $consulta = $request->tipo == "R" ?
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
                ->whereRaw("CURDATE() >= comodatos.inicio")
                ->whereRaw("CURDATE() < comodatos.fim")
                ->whereRaw("retiradas.data >= '".$inicio."'")
                ->whereRaw("retiradas.data < '".$fim."'")
                ->where("comodatos.id_maquina", $request->id_maquina)
                ->where("retiradas.id_produto", $request->id_produto)
        :
            DB::table("comodatos_produtos AS cp")
                ->select(
                    DB::raw("CONCAT(IFNULL(CONCAT(log.nome, ' ('), ''), log.origem, CASE WHEN log.nome IS NOT NULL THEN ')' ELSE '' END) AS origem"),
                    DB::raw("DATE_FORMAT(log.data, '%d/%m/%Y') AS data"),
                    DB::raw("ROUND(estoque.qtd) AS qtd")
                )
                ->join("estoque", "estoque.id_cp", "cp.id")
                ->join("log", function($join) {
                    $join->on("log.fk", "estoque.id")
                        ->where("log.tabela", "estoque");
                })
                ->where(function($sql) use ($request) {
                    $sql->where("estoque.es", $request->tipo);
                })
                ->whereRaw("log.data >= '".$inicio."'")
                ->whereRaw("log.data < '".$fim."'")
                ->where("cp.id_comodato", $this->obter_comodato($request->id_maquina)->id)
                ->where("cp.id_produto", $request->id_produto);
        return json_encode($consulta->get());
    }

    public function meus_comodatos(Request $request) {
        return json_encode(
            DB::table("comodatos")
                ->whereRaw("CURDATE() >= comodatos.inicio")
                ->whereRaw("CURDATE() < comodatos.fim")
                ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "comodatos")) // App\Http\Controllers\Controller.php
                ->where(function($sql) use($request) {
                    if (isset($request->id_maquina)) $sql->where("id_maquina", $request->id_maquina);
                })
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
        if (
            !intval($solicitacao->avisou) &&
            $solicitacao->situacao != "C" &&
            Auth::user()->id_pessoa == $this->obter_autor_da_solicitacao($solicitacao->id) // App\Http\Controllers\Controller.php
        ) {
            $solicitacao->avisou = 1;
            $solicitacao->save();
            $this->log_inserir("E", "solicitacoes", $solicitacao->id); // App\Http\Controllers\Controller.php
            $possui_inconsistencias = "";
            $consulta = DB::table("solicitacoes_produtos")
                            ->whereRaw("IFNULL(obs, '') <> ''")
                            ->where("id_solicitacao", $solicitacao->id)
                            ->pluck("obs");
            foreach ($consulta as $obs) {
                $aux = explode("|", $obs);
                if (($aux[1] == config("app.msg_inexistente") && $solicitacao->situacao == "A") || $aux[1] != config("app.msg_inexistente")) $possui_inconsistencias = "A";
            }
            $resultado = array();
            return json_encode(array(
                "id" => $solicitacao->id,
                "criacao" => DB::table("log")
                                ->selectRaw("DATE_FORMAT(log.data, '%d/%m/%Y') AS data")
                                ->where("fk", $solicitacao->id)
                                ->where("tabela", "solicitacoes")
                                ->where("acao", "C")
                                ->value("data"),
                "usuario_erp" => $solicitacao->situacao != "F" ? $solicitacao->usuario_erp : $solicitacao->usuario_erp2,
                "status" => $solicitacao->situacao,
                "data" => Carbon::parse($solicitacao->data)->format("d/m/Y"),
                "possui_inconsistencias" => $possui_inconsistencias
            ));
        }
        return 200;
    }
    
    public function criar(Request $request) {
        if (!Permissoes::where("id_usuario", Auth::user()->id)->first()->solicitacoes) return 401;
        if (!$this->consultar_main($request->id_comodato)->continuar) return 401;
        $solicitacao = new Solicitacoes;
        $solicitacao->situacao = "A";
        $solicitacao->avisou = 1;
        $solicitacao->data = date("Y-m-d");
        $solicitacao->id_comodato = $request->id_comodato;
        $solicitacao->usuario_web = Pessoas::find(Auth::user()->id_pessoa)->nome;
        $solicitacao->save();
        $this->log_inserir("C", "solicitacoes", $solicitacao->id); // App\Http\Controllers\Controller.php
        for ($i = 0; $i < sizeof($request->id_produto); $i++) {
            if (intval($request->qtd[$i])) {
                $sp = new SolicitacoesProdutos;
                $sp->id_produto_orig = $request->id_produto[$i];
                $sp->qtd_orig = $request->qtd[$i];
                $sp->origem = "WEB";
                $sp->preco_orig = Produtos::find($request->id_produto[$i])->cp($solicitacao->id_comodato)->value("preco");
                $sp->id_solicitacao = $solicitacao->id;
                $sp->save();
                $this->log_inserir("C", "solicitacoes_produtos", $solicitacao->id); // App\Http\Controllers\Controller.php
            }
        }
        $where = "id_solicitacao = ".$solicitacao->id;
        Previas::whereRaw($where)->update(["confirmado" => 1]);
        $this->log_inserir_lote("E", "previas", $where); // App\Http\Controllers\Controller.php
        return $this->view_mensagem("success", "Solicitação realizada"); // App\Http\Controllers\Controller.php
    }

    public function cancelar(Request $request) {
        $solicitacao = Solicitacoes::find($request->id);
        if ($solicitacao->situacao != "A" || $this->obter_autor_da_solicitacao($solicitacao->id) != Auth::user()->id_pessoa) return 401; // App\Http\Controllers\Controller.php
        $solicitacao->situacao = "C";
        $solicitacao->save();
        $this->log_inserir("D", "solicitacoes", $solicitacao->id); // App\Http\Controllers\Controller.php
        return 200;
    }
}