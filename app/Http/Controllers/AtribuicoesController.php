<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Atribuicoes;
use App\Models\Pessoas;
use App\Models\Setores;

class AtribuicoesController extends Controller {
    private function atualizar_aa(Atribuicoes $atribuicao) {
        $this->atualizar_aa_main(
            DB::table("pessoas")
                ->where("lixeira", 0)
                ->where($atribuicao->pessoa_ou_setor_chave == "S" ? "id_setor" : "id", $atribuicao->pessoa_ou_setor_valor)
        ); // App\Http\Controllers\Controller.php
    }

    private function consulta_main($select) {
        return DB::table("vprodaux")
                    ->select(DB::raw($select))
                    ->join("atribuicoes", function($join) {
                        $join->on(function($sql) {
                            $sql->on("atribuicoes.produto_ou_referencia_valor", "vprodaux.cod_externo")
                                ->where("atribuicoes.produto_ou_referencia_chave", "P");
                        })->orOn(function($sql) {
                            $sql->on("atribuicoes.produto_ou_referencia_valor", "vprodaux.referencia")
                                ->where("atribuicoes.produto_ou_referencia_chave", "R");
                        });
                    });
    }

    private function consulta($select, $where) {
        return $this->consulta_main($select)
                    ->whereRaw($where)
                    ->where("vprodaux.lixeira", 0)
                    ->where("atribuicoes.lixeira", 0);
    }

    public function salvar(Request $request) {
        if (!sizeof(
            DB::table("vprodaux")
                ->where($request->produto_ou_referencia_chave == "P" ? "descr" : "referencia", $request->produto_ou_referencia_valor)
                ->where("lixeira", 0)
                ->get()
        )) return 404;
        $produto_ou_referencia_valor = $request->produto_ou_referencia_chave == "P" ?
            DB::table("vprodaux")
                ->where("descr", $request->produto_ou_referencia_valor)
                ->where("lixeira", 0)
                ->value("cod_externo")
        : $request->produto_ou_referencia_valor;
        if (sizeof(
            DB::table("atribuicoes")
                ->where("pessoa_ou_setor_chave", $request->pessoa_ou_setor_chave)
                ->where("pessoa_ou_setor_valor", $request->pessoa_ou_setor_valor)
                ->where("produto_ou_referencia_valor", $produto_ou_referencia_valor)
                ->where("produto_ou_referencia_chave", $request->produto_ou_referencia_chave)
                ->where("lixeira", 0)
                ->get()
        ) && !intval($request->id)) return 403;
        $linha = Atribuicoes::firstOrNew(["id" => $request->id]);
        $linha->pessoa_ou_setor_chave = $request->pessoa_ou_setor_chave;
        $linha->pessoa_ou_setor_valor = $request->pessoa_ou_setor_valor;
        $linha->produto_ou_referencia_chave = $request->produto_ou_referencia_chave;
        $linha->produto_ou_referencia_valor = $produto_ou_referencia_valor;
        $linha->qtd = $request->qtd;
        $linha->validade = $request->validade;
        $linha->obrigatorio = $request->obrigatorio;
        $linha->id_empresa = $request->pessoa_ou_setor_chave == "P" ? Pessoas::find($request->pessoa_ou_setor_valor)->id_empresa : Setores::find($request->pessoa_ou_setor_valor)->id_empresa;
        $linha->save();
        $this->atualizar_aa($linha); // App\Http\Controllers\Controller.php
        $this->log_inserir($request->id ? "E" : "C", "atribuicoes", $linha->id); // App\Http\Controllers\Controller.php
        return 201;
    }

    public function excluir(Request $request) {
        $linha = Atribuicoes::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $this->atualizar_aa($linha); // App\Http\Controllers\Controller.php
        $this->log_inserir("D", "atribuicoes", $linha->id); // App\Http\Controllers\Controller.php
    }

    public function listar(Request $request) {
        $select = "atribuicoes.id, ";
        if ($request->tipo == "P") $select .= "vprodaux.descr AS ";
        $select .= "produto_ou_referencia_valor,
            atribuicoes.qtd,
            atribuicoes.validade, 
            atribuicoes.id_empresa,
            CASE
                WHEN obrigatorio = 1 THEN 'SIM'
                ELSE 'NÃO'
            END AS obrigatorio,
            pessoa_ou_setor_chave
        ";
        $consulta = $this->consulta_main($select)
                        ->leftjoin("pessoas", function($join) {
                            $join->on(function($sql) {
                                $sql->on("pessoa_ou_setor_valor", "pessoas.id")
                                    ->where("pessoa_ou_setor_chave", "P");
                            })->orOn(function($sql) {
                                $sql->on("pessoa_ou_setor_valor", "pessoas.id_setor")
                                    ->where("pessoa_ou_setor_chave", "S");
                            });
                        })
                        ->where(function($sql) use($request) {
                            if ($request->tipo2 != "S") {
                                $sql->whereNotNull("pessoas.id")
                                    ->orWhere("pessoa_ou_setor_chave", $request->tipo2);
                            } else $sql->where("pessoa_ou_setor_chave", $request->tipo2);
                        })
                        ->where("pessoa_ou_setor_valor", $request->id)
                        ->where("produto_ou_referencia_chave", $request->tipo)
                        ->where("vprodaux.lixeira", 0)
                        ->where("atribuicoes.lixeira", 0)
                        ->groupby(
                            "atribuicoes.id",
                            ($request->tipo == "P" ? "vprodaux.descr" : "produto_ou_referencia_valor"),
                            "atribuicoes.qtd",
                            "atribuicoes.validade",
                            "atribuicoes.id_empresa",
                            "atribuicoes.obrigatorio",
                            "atribuicoes.pessoa_ou_setor_chave"
                        )
                        ->orderby("atribuicoes.id")
                        ->get();
        $resultado = array();
        foreach ($consulta as $linha) {
            $linha->pode_editar = 1;
            $mostrar = $linha->pessoa_ou_setor_chave != "S";
            if (!$mostrar) {
                $aux = DB::table("pessoas")
                            ->select(
                                DB::raw("IFNULL(empresas.id, 0) AS id_empresa"),
                                DB::raw("IFNULL(empresas.id_matriz, 0) AS id_matriz")
                            )
                            ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                            ->where("pessoas.id", Auth::user()->id_pessoa)
                            ->first();
                $empresa_atribuicao = intval($linha->id_empresa);
                $empresa_logada = intval($aux->id_empresa);
                $mostrar = in_array($empresa_atribuicao, [0, $empresa_logada, intval($aux->id_matriz)]);
                $linha->pode_editar = $empresa_atribuicao == $empresa_logada ? 1 : 0;
            }
            if ($mostrar) array_push($resultado, $linha);
        }
        return json_encode($resultado);
    }

    public function mostrar($id) {
        return json_encode($this->consulta("
            CASE
                WHEN produto_ou_referencia_chave = 'R' THEN vprodaux.referencia
                ELSE vprodaux.descr
            END AS descr,
            qtd,
            atribuicoes.validade,
            obrigatorio
        ", "atribuicoes.id = ".$id)->first());
    }

    public function produtos($id) {
        return json_encode($this->consulta("
            vprodaux.id,
            CASE
                WHEN produto_ou_referencia_chave = 'R' THEN CONCAT(vprodaux.descr, ' ', tamanho)
                ELSE vprodaux.descr
            END AS descr,
            CASE
                WHEN produto_ou_referencia_chave = 'R' THEN vprodaux.referencia
                ELSE vprodaux.descr
            END AS titulo
        ", "atribuicoes.id = ".$id)->orderby("descr")->get());
    }

    public function grade($id) {
        return json_encode(
            DB::table("vprodaux")
                ->select(
                    "referencia",
                    "descr",
                    "tamanho"
                )
                ->join("atribuicoes", "atribuicoes.produto_ou_referencia_valor", "vprodaux.referencia")
                ->where("atribuicoes.id", $id)
                ->where("vprodaux.lixeira", 0)
                ->get()
        );
    }
}
