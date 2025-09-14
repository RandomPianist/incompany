<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Atribuicoes;
use App\Models\Pessoas;
use App\Models\Setores;

class AtribuicoesController extends Controller {
    private function consulta_main($select) {
        return DB::table("vprodaux")
                    ->select(DB::raw($select))
                    ->join("vatbold", function($join) {
                        $join->on("vatbold.cod_produto", "vprodaux.cod_externo")
                            ->orOn("vatbold.referencia", "vprodaux.referencia");
                    });
    }

    private function consulta($select, $where) {
        return $this->consulta_main($select)
                    ->whereRaw($where)
                    ->where("vprodaux.lixeira", 0);
    }

    public function salvar(Request $request) {
        if (!sizeof(
            DB::table("vprodaux")
                ->where($request->pr_chave == "P" ? "descr" : "referencia", $request->pr_valor)
                ->where("lixeira", 0)
                ->get()
        )) return 404;
        $pr_valor = $request->pr_chave == "P" ?
            DB::table("vprodaux")
                ->where("descr", $request->pr_valor)
                ->where("lixeira", 0)
                ->value("cod_externo")
        : $request->pr_valor;
        if (sizeof(
            DB::table("vatbold")
                ->where("psm_chave", $request->psm_chave)
                ->where("psm_valor", $request->psm_valor)
                ->where("pr_valor", $pr_valor)
                ->where("pr_chave", $request->pr_chave)
                ->get()
        ) && !intval($request->id)) return 403;
        $linha = Atribuicoes::firstOrNew(["id" => $request->id]);
        switch ($request->psm_chave) {
            case "P":
                $linha->id_pessoa = $request->psm_valor;
                $linha->id_empresa = Pessoas::find($request->psm_valor)->id_empresa;
                break;
            case "S":
                $linha->id_setor = $request->psm_valor;
                $linha->id_empresa = Setores::find($request->psm_valor)->id_empresa;
                break;
            case "M":
                $linha->id_maquina = $request->psm_valor;
                $linha->id_empresa = DB::table("vcomodatos")
                                        ->where("id_maquina", $request->psm_valor)
                                        ->value("id_empresa");
                break;
        }
        switch ($request->pr_chave) {
            case "P":
                $linha->cod_produto = $pr_valor;
                break;
            case "M":
                $linha->referencia = $pr_valor;
                break;
        }
        $linha->qtd = $request->qtd;
        $linha->validade = $request->validade;
        $linha->obrigatorio = $request->obrigatorio;
        $linha->data = date("Y-m-d");
        $linha->rascunho = $request->id ? "E" : "C";
        $linha->id_usuario = Auth::user()->id;
        $linha->id_empresa_autor = $this->obter_empresa(); // App\Http\Controllers\Controller.php
        $linha->save();
        return 201;
    }

    public function excluir(Request $request) {
        $linha = Atribuicoes::find($request->id);
        $linha->rascunho = 'R';
        $linha->id_usuario = Auth::user()->id;
        $linha->save();
    }

    public function listar(Request $request) {
        $select = "vatbold.id, ";
        if ($request->tipo == "P") $select .= "vprodaux.descr AS ";
        $select .= "pr_valor,
            vatbold.qtd,
            vatbold.validade, 
            vatbold.id_empresa_autor AS id_empresa,
            CASE
                WHEN vatbold.obrigatorio = 1 THEN 'SIM'
                ELSE 'NÃO'
            END AS obrigatorio,
            vatbold.psm_chave,
            vatbold.rascunho
        ";
        return json_encode($this->atribuicao_listar( // App\Http\Controllers\Controller.php
            $this->consulta_main($select)
                ->leftjoin("pessoas", function($join) {
                    $join->on(function($sql) {
                        $sql->on("vatbold.psm_valor", "pessoas.id")
                            ->where("vatbold.psm_chave", "P");
                    })->orOn(function($sql) {
                        $sql->on("vatbold.psm_valor", "pessoas.id_setor")
                            ->where("vatbold.psm_chave", "S");
                    });
                })
                ->where(function($sql) use($request) {
                    $sql->where("vatbold.psm_chave", $request->tipo2);
                })
                ->where("vatbold.psm_valor", $request->id)
                ->where("vatbold.pr_chave", $request->tipo)
                ->where("vprodaux.lixeira", 0)
                ->groupby(
                    "vatbold.id",
                    ($request->tipo == "P" ? "vprodaux.descr" : "vatbold.pr_valor"),
                    "vatbold.qtd",
                    "vatbold.validade",
                    "vatbold.id_empresa",
                    "vatbold.obrigatorio",
                    "vatbold.psm_chave",
                    "vatbold.rascunho"
                )
                ->orderby(
                    "vatbold.rascuho",
                    "vatbold.id"
                )
                ->get()
        ));
    }

    public function mostrar($id) {
        return json_encode($this->consulta("
            CASE
                WHEN (vatbold.pr_chave = 'R') THEN vprodaux.referencia
                ELSE vprodaux.descr
            END AS descr,
            vatbold.qtd,
            vatbold.validade,
            vatbold.obrigatorio
        ", "vatbold.id = ".$id)->first());
    }

    public function produtos($id) {
        return json_encode($this->consulta("
            vprodaux.id,
            CASE
                WHEN (vatbold.pr_chave = 'R') THEN CONCAT(vprodaux.descr, ' ', tamanho)
                ELSE vprodaux.descr
            END AS descr,
            CASE
                WHEN (vatbold.pr_chave = 'R') THEN vprodaux.referencia
                ELSE vprodaux.descr
            END AS titulo
        ", "vatbold.id = ".$id)->orderby("descr")->get());
    }

    public function grade($id) {
        return json_encode(
            DB::table("vprodaux")
                ->select(
                    "vprodaux.referencia",
                    "vprodaux.descr",
                    "vprodaux.tamanho"
                )
                ->join("atribuicoes", "atribuicoes.referencia", "vprodaux.referencia")
                ->where("atribuicoes.id", $id)
                ->where("vprodaux.lixeira", 0)
                ->get()
        );
    }

    public function recalcular() {
        $tabelas = ["atribuicoes", "excecoes"];
        $where = "id_usuario = ".Auth::user()->id;
        foreach ($tabelas as $tabela) {
            DB::statement("
                UPDATE ".$tabela."
                SET rascunho = 'T'
                WHERE ".$where." AND rascunho = 'R'
            ");
        }
        $lista = DB::table("vatbold")
                    ->select(
                        "psm_chave",
                        "psm_valor"
                    )
                    ->joinSub(
                        DB::table("atribuicoes")
                            ->select("id")
                            ->whereRaw($where)
                            ->where("rascunho", "<>", "S")
                            ->unionAll(
                                DB::table("excecoes")
                                    ->select("id_atribuicao")
                                    ->whereRaw($where)
                                    ->where("rascunho", "<>", "S")
                            ),
                        "lim",
                        "lim.id",
                        "vatbold.id"
                    )
                    ->groupby(
                        "psm_chave",
                        "psm_valor"
                    )
                    ->get();
        foreach ($tabelas as $tabela) {
            $this->log_inserir_lote("C", $tabela, $where." AND rascunho = 'C'"); // App\Http\Controllers\Controller.php
            DB::statement("
                UPDATE ".$tabela."
                SET rascunho = 'S'
                WHERE ".$where." AND rascunho = 'C'
            ");
            $this->log_inserir_lote("E", $tabela, $where." AND rascunho = 'E'"); // App\Http\Controllers\Controller.php
            DB::statement("
                UPDATE ".$tabela."
                SET rascunho = 'S'".($tabela == "atribuicoes" ? ", gerado = 0, data = '".date('Y-m-d')."'" : "")."
                WHERE ".$where." AND rascunho = 'E'
            ");
            $this->log_inserir_lote("D", $tabela, $where." AND rascunho = 'T'"); // App\Http\Controllers\Controller.php
            DB::statement("
                UPDATE ".$tabela."
                SET rascunho = 'S', lixeira = 1
                WHERE ".$where." AND rascunho = 'T'
            ");
        }
        $this->atualizar_atribuicoes($lista);
        $this->excluir_atribuicao_sem_retirada();
    }

    public function descartar() {
        $tabelas = ["atribuicoes", "excecoes"];
        $lista = DB::table("retiradas")
                    ->select("retiradas.id")
                    ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                    ->where("atribuicoes.rascunho", "C")
                    ->where("atribuicoes.id_usuario", Auth::user()->id)
                    ->pluck("id")
                    ->toArray();
        if (sizeof($lista)) {
            DB::statement("DELETE FROM retiradas WHERE id IN (".join(",", $lista).")");
            DB::statement("DELETE FROM log WHERE fk IN (".join(",", $lista).") AND tabela = 'retiradas'");
        }
        foreach ($tabelas as $tabela) {
            DB::statement("
                UPDATE ".$tabela."
                SET rascunho = 'S'
                WHERE id_usuario = ".Auth::user()->id."
                  AND rascunho IN ('E', 'R')
            ");
            DB::statement("
                DELETE FROM ".$tabela."
                WHERE id_usuario = ".Auth::user()->id."
                  AND rascunho = 'C'
            ");
        }
    }
}
