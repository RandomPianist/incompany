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
        $linha->gerado = 0;
        $linha->id_empresa_autor = $this->obter_empresa(); // App\Http\Controllers\Controller.php
        $linha->save();
        $this->atualizar_atribuicoes($this->obter_atb_ant($linha->id)); // App\Http\Controllers\Controller.php
        $this->log_inserir($request->id ? "E" : "C", "atribuicoes", $linha->id); // App\Http\Controllers\Controller.php
        return 201;
    }

    public function excluir(Request $request) {
        $linha = Atribuicoes::find($request->id);
        $ant = $this->obter_atb_ant($linha->id); // App\Http\Controllers\Controller.php
        $linha->lixeira = 1;
        $linha->save();
        $this->atualizar_atribuicoes($ant); // App\Http\Controllers\Controller.php
        $this->log_inserir("D", "atribuicoes", $linha->id); // App\Http\Controllers\Controller.php
        $this->excluir_atribuicao_sem_retirada(); // App\Http\Controllers\Controller.php
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
            vatbold.psm_chave
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
                    "vatbold.psm_chave"
                )
                ->orderby("vatbold.id")
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
}
