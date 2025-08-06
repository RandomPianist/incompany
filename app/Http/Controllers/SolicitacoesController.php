<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Comodatos;
use App\Models\Solicitacoes;
use App\Models\SolicitacoesProdutos;

class SolicitacoesController extends ControllerKX {
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
        if (!in_array($solicitacao->status, ["A", "E"])) {
            $resultado->continuar = 1;
            return $resultado;
        }
        $id_autor = $this->obter_autor_da_solicitacao($solicitacao->id);
        $resultado->continuar = 0;
        $resultado->status = $solicitacao->status;
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
        $tela = $this->sugestao_main($request);
        $resultado = $tela->resultado;
        $criterios = $tela->criterios;
        $mostrar_giro = $tela->mostrar_giro;
        if (sizeof($resultado)) return view("solicitacoes", compact("resultado", "criterios", "mostrar_giro"));
        return view("nada");
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
                    $sql->where("estoque.es", $request->tipo);
                })
                ->whereRaw("log.data >= '".$inicio."'")
                ->whereRaw("log.data <= '".$fim."'")
                ->where("mp.id_maquina", $request->id_maquina)
                ->where("mp.id_produto", $request->id_produto);
        return json_encode($consulta->get());
    }

    public function meus_comodatos(Request $request) {
        return json_encode(
            DB::table("comodatos")
                    ->whereRaw("((CURDATE() BETWEEN inicio AND fim) OR (CURDATE() BETWEEN inicio AND fim))")
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "comodatos"))
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
            $solicitacao->status <> "C" &&
            Auth::user()->id_pessoa == $this->obter_autor_da_solicitacao($solicitacao->id)
        ) {
            $solicitacao->avisou = 1;
            $solicitacao->save();
            $this->log_inserir("E", "solicitacoes", $solicitacao->id);
            $possui_inconsistencias = "";
            $consulta = DB::table("solicitacoes_produtos")
                            ->whereRaw("IFNULL(obs, '') <> ''")
                            ->where("id_solicitacao", $solicitacao->id)
                            ->pluck("obs");
            foreach ($consulta as $obs) {
                $aux = explode("|", $obs);
                if (($aux[1] == config("app.msg_inexistente") && $solicitacao->status == "A") || $aux[1] != config("app.msg_inexistente")) $possui_inconsistencias = "A";
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
                "usuario_erp" => $solicitacao->status != "F" ? $solicitacao->usuario_erp : $solicitacao->usuario_erp2,
                "status" => $solicitacao->status,
                "data" => Carbon::parse($solicitacao->data)->format("d/m/Y"),
                "possui_inconsistencias" => $possui_inconsistencias
            ));
        }
        return 200;
    }
    
    public function criar(Request $request) {
        if (!$this->consultar_main($request->id_comodato)->continuar) return 401;
        $solicitacao = new Solicitacoes;
        $solicitacao->status = "A";
        $solicitacao->avisou = 1;
        $solicitacao->data = date("Y-m-d");
        $solicitacao->id_comodato = $request->id_comodato;
        $solicitacao->usuario_web = Pessoas::find(Auth::user()->id_pessoa)->nome;
        $solicitacao->save();
        $this->log_inserir("C", "solicitacoes", $solicitacao->id);
        for ($i = 0; $i < sizeof($request->id_produto); $i++) {
            if (intval($request->qtd[$i])) {
                $sp = new SolicitacoesProdutos;
                $sp->id_produto_orig = $request->id_produto[$i];
                $sp->qtd_orig = $request->qtd[$i];
                $sp->origem = "WEB";
                $sp->preco_orig = DB::table("maquinas_produtos")
                                    ->where("id_maquina", Comodatos::find($solicitacao->id_comodato)->id_maquina)
                                    ->where("id_produto", $sp->id_produto_orig)
                                    ->value("preco");
                $sp->id_solicitacao = $solicitacao->id;
                $sp->save();
                $this->log_inserir("C", "solicitacoes_produtos", $solicitacao->id);
            }
        }
        $where = "id_comodato = ".$request->id_comodato;
        DB::statement("UPDATE previas SET confirmado = 1 WHERE ".$where);
        $this->log_inserir_lote("E", "WEB", "previas", $where);
        return view("sucesso");
    }

    public function cancelar(Request $request) {
        $solicitacao = Solicitacoes::find($request->id);
        if ($solicitacao->status != "A" || $this->obter_autor_da_solicitacao($solicitacao->id) != Auth::user()->id_pessoa) return 401;
        $solicitacao->status = "C";
        $solicitacao->save();
        $this->log_inserir("D", "solicitacoes", $solicitacao->id);
    }
}