<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Hash;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Empresas;

class PessoasController extends ControllerListavel {
    protected function busca($where, $tipo = "") {
        return DB::table("pessoas")
                    ->select(
                        "pessoas.id",
                        DB::raw("
                            CASE 
                                WHEN pessoas.id_empresa <> 0 THEN 
                                    CASE
                                        WHEN pessoas.biometria <> '' THEN 'possui'
                                        ELSE 'nao-possui'
                                    END
                                ELSE 'sem-foto'
                            END AS possui_biometria,
                            CONCAT(
                                pessoas.nome,
                                CASE
                                    WHEN pessoas.id = ".Auth::user()->id_pessoa." THEN ' (você)'
                                    ELSE ''
                                END
                            ) AS nome
                        "),
                        DB::raw("IFNULL(setores.descr, 'A CLASSIFICAR') AS setor"),
                        DB::raw("IFNULL(empresas.nome_fantasia, 'A CLASSIFICAR') AS empresa"),
                        DB::raw("
                            CASE
                                WHEN (pessoas.id IN (
                                    SELECT id_pessoa
                                    FROM retiradas
                                    WHERE ".$this->obter_where(Auth::user()->id_pessoa, "retiradas")."
                                )) THEN 1
                                ELSE 0
                            END AS possui_retiradas
                        ")
                    )
                    ->leftjoin("setores", "setores.id", "pessoas.id_setor")
                    ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                    ->where(function($sql) use($tipo) {
                        $id_emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
                        if ($id_emp) $sql->whereRaw($id_emp." IN (empresas.id, empresas.id_matriz)");
                        if (in_array($tipo, ["A", "U"])) {
                            $sql->where("setores.cria_usuario", 1);
                            if ($tipo == "A") $sql->where("pessoas.id_empresa", 0);
                        } else $sql->where("pessoas.supervisor", ($tipo == "S" ? 1 : 0));
                    })
                    ->whereRaw($where)
                    ->where("pessoas.lixeira", 0)
                    ->get();
    }

    public function ver($tipo) {
        switch($tipo) {
            case "A":
                $titulo = "Administradores";
                break;
            case "F":
                $titulo = "Funcionários";
                break;
            case "S":
                $titulo = "Supervisores";
                break;
            case "U":
                $titulo = "Usuários";
                break;
        }
        $where = "setores1.cria_usuario = 0 AND aux1.supervisor = ".($tipo == "S" ? "1" : "0");
        if (in_array($tipo, ["A", "U"])) {
            $where = "setores1.cria_usuario = 1";
            if ($tipo == "A") $where .= " AND aux1.id_empresa = 0";
        }
        $ultima_atualizacao = $this->log_consultar("pessoas", $where);
        $consulta = DB::table("atribuicoes")
                        ->selectRaw("MAX(qtd) AS qtd")
                        ->get();
        $max_atb = sizeof($consulta) ? $consulta[0]->qtd : 0;
        return view("pessoas", compact("ultima_atualizacao", "titulo", "tipo", "max_atb"));
    }

    public function mostrar($id) {
        return json_encode(
            DB::table("pessoas")
                ->select(
                    "pessoas.id",
                    "pessoas.cpf",
                    DB::raw("IFNULL(pessoas.id_setor, 0) AS id_setor"),
                    DB::raw("IFNULL(pessoas.id_empresa, 0) AS id_empresa"),
                    "pessoas.funcao",
                    "pessoas.supervisor",
                    "pessoas.foto",
                    DB::raw("DATE_FORMAT(pessoas.admissao, '%d/%m/%Y') AS admissao"),
                    DB::raw("IFNULL(users.name, pessoas.nome) AS nome"),
                    "users.email",
                    "permissoes.*"
                )
                ->leftjoin("users", "users.id_pessoa", "pessoas.id")
                ->leftjoin("permissoes", "permissoes.id_usuario", "users.id")
                ->where("pessoas.id", $id)
                ->first()
        );
    }

    public function salvar(Request $request) {

    }

    public function excluir(Request $request) {

    }

    public function alterar_empresa(Request $request) {
        if (!intval(Auth::user()->admin)) return 401;
        $pessoa = Pessoas::find(Auth::user()->id_pessoa);
        $pessoa->id_empresa = $request->idEmpresa;
        $pessoa->id_setor = intval($request->idEmpresa) ? 
            DB::table("setores")
                ->select("setores.id")
                ->join("log", function($join) {
                    $join->on("log.fk", "setores.id")
                        ->where("log.tabela", "setores");
                })
                ->join("permissoes", "permissoes.id_setor", "setores.id")
                ->where("log.origem", "SYS")
                ->where("permissoes.financeiro", 1)
                ->where("permissoes.atribuicoes", 1)
                ->where("permissoes.retiradas", 1)
                ->where("permissoes.pessoas", 1)
                ->where("permissoes.usuarios", 1)
                ->where("permissoes.solicitacoes", 1)
                ->where("permissoes.supervisor", 1)
                ->where("setores.id_empresa", $request->idEmpresa)
                ->value("id")
        : 0;
        $pessoa->save();
    }

    public function senha(Request $request) {
        if (
            DB::table("pessoas")
                ->where("id", $request->id)
                ->whereRaw($this->obter_where(Auth::user()->id_pessoa)) // App\Http\Controllers\Controller.php
                ->exists()
        ) return 401;
        return Pessoas::find($request->id)->senha;
    }
}
