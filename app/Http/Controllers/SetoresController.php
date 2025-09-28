<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Setores;
use App\Models\Permissoes;
use App\Models\Pessoas;
use Illuminate\Http\Request;

class SetoresController extends ControllerListavel {
    protected $permissoes_lista = ["usuarios", "atribuicoes", "retiradas", "pessoas", "financeiro", "supervisor", "solicitacoes"];

    private function aviso_main($id) {
        $resultado = new \stdClass;
        $resultado->permitir = 0;
        $setor = Setores::find($id);
        $nome = $setor->descr;
        if (
            DB::table("setores")
                ->join("log", function($join) {
                    $join->on("log.fk", "setores.id")
                        ->where("log.origem", "SYS")
                        ->where("log.tabela", "setores");
                })
                ->exists()
        ) {
            $resultado->aviso = "Não é possível excluir um setor do sistema";
        } elseif ($setor->pessoas()->exists()) {
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem pessoas vinculadas a esse setor";
        } else {
            $resultado->permitir = 1;
            $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
        }
        return $resultado;
    }

    protected function busca($param, $tipo = "") {
        return DB::table("setores")
                    ->select(
                        "setores.id",
                        "setores.descr",
                        "empresas.nome_fantasia AS empresa"
                    )
                    ->join("empresas", "setores.id_empresa", "empresas.id")
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "setores")) // App\Http\Controllers\Controller.php
                    ->whereRaw(str_replace("?", "setores.descr", $param))
                    ->where("empresas.lixeira", 0)
                    ->get();
    }

    public function ver() {
        $permissoes = $this->permissoes_lista;
        return view("setores", compact("permissoes"));
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function mostrar($id) {
        $condicoes_padrao = [
            "WHEN (log.id IS NOT NULL) THEN 'SYS'",
            "WHEN (minhas_permissoes.usuarios = 0) THEN 'PER'",
            "ELSE ''"
        ];
        $campos_permissao = array();
        foreach ($this->permissoes_lista as $campo) {
            $sql = "permissoes".$campo.", CASE ";
            $condicoes = $campo == "usuarios" ? ["WHEN (setores.id = ".Pessoas::find(Auth::user()->id_pessoa)->id_setor.") THEN 'USU'"] : [];
            foreach ($condicoes_padrao as $condicao) array_push($condicoes, $condicao);
            $sql .= implode(" ", $condicoes)." END AS ".$campo."_motivo";
            array_push($campos_permissao, $sql);
        }
        return json_encode(DB::select(DB::raw("
            SELECT
                setores.descr,
                setores.cria_usuario,
                CASE ".implode(" ", $condicoes_padrao)." END AS cria_usuario_motivo,
                ".implode(",", $campos_permissao)."
            
            FROM setores

            CROSS JOIN permissoes AS minhas_permissoes

            LEFT JOIN permissoes
                ON permissoes.id_setor = setores.id

            LEFT JOIN log
                ON log.fk = setores.id
                    AND log.origem = 'SYS'
                    AND log.acao = 'C'
                    AND log.tabela = 'setores'

            WHERE setores.id = ".$id."
                AND minhas_permissoes.id_usuario = ".Auth::user()->id
        )));
    }

    public function salvar(Request $request) {
        $cria_usuario = $request->cria_usuario == "S" ? 1 : 0;
        $linha = Setores::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            $adm_ant = intval($linha->cria_usuario);
            if (
                $adm_ant == $cria_usuario &&
                $linha->id_empresa == $request->id_empresa &&
                !$this->comparar_num($linha->permissao()->financeiro, $request->financeiro == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->atribuicoes, $request->atribuicoes == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->retiradas, $request->retiradas == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->pessoas, $request->pessoas == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->usuarios, $request->usuarios == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->solicitacoes, $request->solicitacoes == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->supervisor, $request->supervisor == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_texto($request->descr, $linha->descr) // App\Http\Controllers\Controller.php
            ) return 400;
            if ($adm_ant != $cria_usuario) {
                if ($adm_ant) {
                    $lista = array();
                    $permissoes = array();
                    $consulta = DB::table("users")
                                    ->select("users.id")
                                    ->join("pessoas", "pessoas.id", "users.id_pessoa")
                                    ->where("pessoas.id_setor", $request->id)
                                    ->where("users.admin", 0)
                                    ->pluck("id");
                    foreach($consulta as $usuario) {
                        $permissao = Permissoes::where("id_usuario", $usuario)->value("id");
                        array_push($lista, $usuario);
                        array_push($permissoes, $permissao);
                        $this->log_inserir("D", "users", $usuario); // App\Http\Controllers\Controller.php
                        $this->log_inserir("D", "permissoes", $permissao); // App\Http\Controllers\Controller.php
                    }
                    if (sizeof($lista)) {
                        if (isset($request->id_pessoa)) {
                            for ($i = 0; $i < sizeof($request->id_pessoa); $i++) {
                                $modelo = Pessoas::find($request->id_pessoa[$i]);
                                $modelo->senha = $request->password[$i];
                                $modelo->save();
                                $this->log_inserir("E", "pessoas", $modelo->id); // App\Http\Controllers\Controller.php
                            }
                        }
                        DB::table("users")->whereIn("id", $lista)->delete();
                        Permissoes::whereIn("id", $permissoes)->delete();
                    }
                } elseif (isset($request->id_pessoa)) {
                    for ($i = 0; $i < sizeof($request->id_pessoa); $i++) {
                        $id_usuario = DB::table("users")->insertGetId([
                            "name" => trim($request->nome[$i]),
                            "email" => trim($request->email[$i]),
                            "senha" => Hash::make($request->password[$i]),
                            "id_pessoa" => $request->id_pessoa[$i]
                        ]);
                        $this->log_inserir("C", "users", $id_usuario); // App\Http\Controllers\Controller.php
                        $permissao = $linha->permissao()->replicate(["id_setor"]);
                        $permissao->id_usuario = $id_usuario;
                        $permissao->save();
                        $this->log_inserir("C", "permissoes", $permissao->id); // App\Http\Controllers\Controller.php
                    }
                }
            }
        }
        $linha->descr = mb_strtoupper($request->descr);
        $linha->id_empresa = $request->id_empresa;
        $linha->cria_usuario = $cria_usuario;
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "setores", $linha->id); // App\Http\Controllers\Controller.php
        return redirect("/setores");
    }

    public function excluir(Request $request) {
        if (!$this->aviso_main($request->id)->permitir) return 401;
        $linha = Setores::find($request->id);
        $emp = $this->obter_empresa();
        if ($emp && $linha->id_empresa != $emp) return 403;
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "setores", $linha->id); // App\Http\Controllers\Controller.php
        return 200;
    }

    public function usuarios($id) {
        return json_encode(
            DB::table("pessoas")
                ->select(
                    "pessoas.id",
                    "pessoas.nome"
                )
                ->join("users", "users.id_pessoa", "pessoas.id")
                ->where("pessoas.id_setor", $id)
                ->where("pessoas.lixeira", 0)
                ->where("users.admin", 0)
                ->get()
        );
    }

    public function pessoas($id) {
        return json_encode(
            DB::table("pessoas")
                ->select(
                    "pessoas.id",
                    "pessoas.nome"
                )
                ->leftjoin("users", "users.id_pessoa", "pessoas.id")
                ->where("pessoas.id_setor", $id)
                ->where("pessoas.lixeira", 0)
                ->whereNull("users.id")
                ->get()
        );
    }
}