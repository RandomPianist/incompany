<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Atribuicoes;
use App\Models\Pessoas;
use App\Models\Setores;
use App\Models\Maquinas;

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

    private function salvar_main(Atribuicoes $atribuicao, $qtd, $validade, $obrigatorio) {
        $atribuicao->qtd = $qtd;
        $atribuicao->validade = $validade;
        $atribuicao->obrigatorio = $obrigatorio;
        $atribuicao->data = date("Y-m-d");
        $atribuicao->id_usuario = Auth::user()->id;
        $atribuicao->id_empresa_autor = $this->obter_empresa(); // App\Http\Controllers\Controller.php
        $atribuicao->save();
    }

    public function salvar(Request $request) {
        if (
            !DB::table("vprodaux")
                ->where($request->pr_chave == "P" ? "descr" : "referencia", $request->pr_valor)
                ->where("lixeira", 0)
                ->exists()
        ) return 404;
        $pr_valor = $request->pr_chave == "P" ?
            DB::table("vprodaux")
                ->where("descr", $request->pr_valor)
                ->where("lixeira", 0)
                ->value("cod_externo")
        : $request->pr_valor;
        if (!intval($request->id) &&
            DB::table("vatbold")
                ->where("psm_chave", $request->psm_chave)
                ->where("psm_valor", $request->psm_valor)
                ->where("pr_valor", $pr_valor)
                ->where("pr_chave", $request->pr_chave)
                ->exists()
        ) return 403;
        $linha = Atribuicoes::firstOrNew(["id" => $request->id]);
        if ($request->id) $this->backup_atribuicao($linha); // App\Http\Controllers\Controller.php
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
        $linha->rascunho = $request->id ? "E" : "C";
        $linha->gerado = 0;
        $this->salvar_main($linha, $request->qtd, $request->validade, $request->obrigatorio);
        return 201;
    }

    public function excluir(Request $request) {
        $linha = Atribuicoes::find($request->id);
        $linha->rascunho = 'R';
        $linha->id_usuario = Auth::user()->id;
        $linha->save();
    }

    public function permissao(Request $request) {
        $resultado = new \stdClass;
        $consulta = DB::table("vatbold")
                        ->selectRaw("UPPERCASE(IFNULL(users1.name, users2.name)) AS usuario")
                        ->leftjoin("excecoes", "excecoes.id_atribuicao", "atribuicoes.id")
                        ->leftjoin("users AS users1", "users1.id", "excecoes.id_usuario")
                        ->leftjoin("users AS users2", "users2.id", "atribuicoes.id_usuario")
                        ->where("vatbold.psm_valor", $request->id)
                        ->where("vatbold.pr_chave", $request->tipo)
                        ->where("vatbold.psm_chave", $request->tipo2)
                        ->where(function($sql) {
                            $sql->where("vatbold.rascunho", "<>", "S")
                                ->orWhere("excecoes.rascunho", "<>", "S");
                        })
                        ->get();
        if (!sizeof($consulta)) {
            $resultado->code = 200;
            return json_encode($resultado);
        }
        switch($request->tipo2) {
            case "P":
                $resultado->nome = Pessoas::find($request->id)->nome;
                break;
            case "S":
                $resultado->nome = Setores::find($request->id)->descr;
                break;
            case "M":
                $resultado->nome = Maquinas::find($request->id)->descr;
                break;
        }
        $resultado->usuario = $consulta[0]->usuario;
        return json_encode($resultado);
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
                ->where("vatbold.psm_valor", $request->id)
                ->where("vatbold.pr_chave", $request->tipo)
                ->where("vatbold.psm_chave", $request->tipo2)
                ->where(function($sql) {
                    $sql->where("vatbold.rascunho", "S")
                        ->orWhere("vatbold.id_usuario", Auth::user()->id);
                })
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
                ->orderby("vatbold.rascunho")
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
        $consulta = DB::table("vatbold")
                        ->whereRaw($where)
                        ->where("rascunho", "C")
                        ->get();
        foreach ($consulta as $linha) {
            $id_excluir = $linha->id;
            $id_restaurar = DB::table("atribuicoes")
                                ->whereRaw("
                                    (CASE
                                        WHEN cod_produto IS NOT NULL THEN 'P'
                                        ELSE 'R'
                                    END) = '".$linha->pr_chave."'"
                                )
                                ->whereRaw("
                                    (CASE
                                        WHEN cod_produto IS NOT NULL THEN cod_produto
                                        ELSE referencia
                                    END) = '".$linha->pr_valor."'"
                                )
                                ->whereRaw("
                                    (CASE
                                        WHEN id_pessoa IS NOT NULL THEN 'P'
                                        WHEN id_setor IS NOT NULL THEN 'S'
                                        ELSE 'M'
                                    END) = '".$linha->psm_chave."'"
                                )
                                ->whereRaw("
                                    (CASE
                                        WHEN id_pessoa IS NOT NULL THEN id_pessoa
                                        WHEN id_setor IS NOT NULL THEN id_setor
                                        ELSE id_maquina
                                    END) = '".$linha->psm_valor."'"
                                )
                                ->value("id");
            if ($id_restaurar !== null) {
                $modelo = Atribuicoes::find($id_excluir);
                $modelo->delete();
                $modelo = Atribuicoes::find($id_restaurar);
                $modelo->lixeira = 0;
                $modelo->rascunho = "S";
                $this->salvar_main($modelo, $linha->qtd, $linha->validade, $linha->obrigatorio);
                $this->log_inserir("E", "atribuicoes", $modelo->id);
                DB::statement("DELETE FROM log WHERE fk = ".$id_excluir." AND acao = 'D' AND tabela = 'atribuicoes'");
            }
        }
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
        $this->atualizar_atribuicoes($lista); // App\Http\Controllers\Controller.php
    }

    public function descartar() {
        $id_usuario = Auth::user()->id;
        $tabelas = ["atribuicoes", "excecoes"];
        $lista = DB::table("retiradas")
                    ->select("retiradas.id")
                    ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                    ->where("atribuicoes.rascunho", "C")
                    ->where("atribuicoes.id_usuario", $id_usuario)
                    ->pluck("id")
                    ->toArray();
        if (sizeof($lista)) {
            DB::statement("DELETE FROM retiradas WHERE id IN (".join(",", $lista).")");
            DB::statement("DELETE FROM log WHERE fk IN (".join(",", $lista).") AND tabela = 'retiradas'");
        }
        foreach ($tabelas as $tabela) {
            $tab_bkp = $tabela == "atribuicoes" ? "atbbkp" : "excbkp";
            DB::statement("
                UPDATE ".$tabela."
                SET rascunho = 'S'
                WHERE id_usuario = ".$id_usuario."
                  AND rascunho IN ('E', 'R')
            ");
            DB::statement($tabela == "atribuicoes" ? "
                UPDATE atribuicoes
                JOIN atbbkp
                    ON atbbkp.id_atribuicao = atribuicoes.id
                SET
                    atribuicoes.qtd = atbbkp.qtd,
                    atribuicoes.data = atbbkp.data,
                    atribuicoes.validade = atbbkp.validade,
                    atribuicoes.obrigatorio = atbbkp.obrigatorio,
                    atribuicoes.gerado = atbbkp.gerado,
                    atribuicoes.id_usuario = atbbkp.id_usuario
                WHERE atribuices.id_usuario = ".$id_usuario
            : "
                UPDATE excecoes
                JOIN excbkp
                    ON excbkp.id_excecao = excecoes.id
                SET
                    excecoes.id_pessoa = excbkp.id_pessoa
                    excecoes.id_setor = excbkp.id_setor
                    excecoes.id_usuario = excbkp.id_usuario
                WHERE excecoes.id_usuario = ".$id_usuario
            );
            DB::statement("DELETE FROM ".$tab_bkp." WHERE id_usuario_editando = ".$id_usuario);
            if (!DB::table($tab_bkp)->exists()) DB::statement("TRUNCATE TABLE ".$tab_bkp);
            DB::statement("
                DELETE FROM ".$tabela."
                WHERE id_usuario = ".$id_usuario."
                  AND rascunho = 'C'
            ");
        }
    }
}
