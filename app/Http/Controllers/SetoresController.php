<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Setores;
use App\Models\Permissoes;
use App\Models\Pessoas;
use Illuminate\Http\Request;

class SetoresController extends ControllerListavel {
    private function aviso_main($id) {
        $resultado = $this->pode_abrir_main("setores", $id, "excluir"); // App\Http\Controllers\Controller.php
        if (!$resultado->permitir) return $resultado;
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
                ->where("setores.id", $id)
                ->exists()
        ) {
            $resultado->aviso = "Não é possível excluir um centro de custo do sistema";
        } elseif ($setor->pessoas()->exists()) {
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem pessoas vinculadas a esse centro de custo";
        } else {
            $resultado->permitir = 1;
            $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
        }
        return $resultado;
    }

    private function consultar_main(Request $request) {
        $resultado = new \stdClass;

        if ($this->empresa_consultar($request)) { // App\Http\Controllers\Controller.php
            $resultado->msg = "Empresa não encontrada";
            $resultado->el = "setor-empresa";
            return $resultado;
        }
        
        if (
            !$request->id &&
            Setores::where("lixeira", 0)
                    ->where("descr", $request->descr)
                    ->where("id_empresa", $request->id_empresa)
                    ->exists()
        ) {
            $resultado->msg = "Já existe um centro de custo de mesmo nome nessa empresa";
            $resultado->el = "descr";
            return $resultado;
        }

        $resultado->msg = "";
        $resultado->el = "";
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
        return view("setores");
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function consultar(Request $request) {
        return json_encode($this->consultar_main($request));
    }

    public function mostrar($id) {
        $this->alterar_usuario_editando("setores", $id); // App\Http\Controllers\Controller.php
        $condicoes_padrao = [
            "WHEN (log.id IS NOT NULL) THEN 'SYS'",
            "WHEN (minhas_permissoes.usuarios = 0) THEN 'PER'",
            "ELSE ''"
        ];
        $campos_permissao = array();
        foreach ($this->permissoes_lista as $campo) {
            $sql = "permissoes".$campo.", CASE ";
            $condicoes = $campo == "usuarios" ? ["WHEN (setores.id = ".Pessoas::find(Auth::user()->id_pessoa)->id_setor.") THEN 'USU'"] : [];
            foreach ($condicoes_padrao as $condicao) array_push($condicoes, str_replace("usuarios", $campo, $condicao));
            $sql .= implode(" ", $condicoes)." END AS ".$campo."_motivo";
            array_push($campos_permissao, $sql);
        }
        return json_encode(DB::select(DB::raw("
            SELECT
                setores.descr,
                setores.cria_usuario,
                setores.id_empresa,
                empresa.nome_fantasia AS empresa,
                CASE
                    WHEN (log.id IS NOT NULL) THEN 'SYS'
                    WHEN (lim.id_setor IS NOT NULL) THEN 'PES'
                    ELSE ''
                END AS empresa_motivo,
                CASE ".implode(" ", $condicoes_padrao)." END AS cria_usuario_motivo,
                ".implode(",", $campos_permissao)."
            
            FROM setores

            CROSS JOIN permissoes AS minhas_permissoes

            JOIN empresas
                ON empresas.id = setores.id_empresa

            JOIN permissoes
                ON permissoes.id_setor = setores.id

            LEFT JOIN (
                SELECT DISTINCTROW id_setor
                FROM pessoas
                WHERE lixeira = 0
            ) ON lim ON lim.id_setor = setores.id

            LEFT JOIN log
                ON log.fk = setores.id
                    AND log.origem = 'SYS'
                    AND log.acao = 'C'
                    AND log.tabela = 'setores'

            WHERE setores.id = ".$id."
                AND minhas_permissoes.id_usuario = ".Auth::user()->id
        ))[0]);
    }

    public function salvar(Request $request) {
        if ($this->consultar_main($request)->msg) return 401;
        $cria_usuario = $request->cria_usuario == "S" ? 1 : 0;
        $supervisor = $request->supervisor == "S" ? 1 : 0;
        $cria_usuario_ant = null;
        $supervisor_ant = null;
        $setor = Setores::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            $cria_usuario_ant = $setor->cria_usuario;
            $supervisor_ant = $setor->permissao()->supervisor ? 1 : 0;
            if (
                $setor->id_empresa == $request->id_empresa &&
                !$this->comparar_texto($request->descr, $linha->descr) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($supervisor_ant, $supervisor) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($cria_usuario_ant, $cria_usuario) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao()->financeiro, $request->financeiro == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao()->atribuicoes, $request->atribuicoes == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao()->retiradas, $request->retiradas == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao()->pessoas, $request->pessoas == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao()->usuarios, $request->usuarios == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao()->solicitacoes, $request->solicitacoes == "S" ? 1 : 0) // App\Http\Controllers\Controller.php
            ) return 400;
        }
        $setor->descr = mb_strtoupper($request->descr);
        $setor->id_empresa = $request->id_empresa;
        $setor->cria_usuario = $cria_usuario;
        $setor->save();
        $this->log_inserir($request->id ? "E" : "C", "setores", $setor->id); // App\Http\Controllers\Controller.php
        $setor->permissao()->updateOrCreate(
            ["id_setor" => $setor->id],
            [
                "financeiro" => $request->financeiro == "S" ? 1 : 0,
                "atribuicoes" => $request->atribuicoes == "S" ? 1 : 0,
                "retiradas" => $request->retiradas == "S" ? 1 : 0,
                "pessoas" => $request->pessoas == "S" ? 1 : 0,
                "usuarios" => $request->usuarios == "S" ? 1 : 0,
                "solicitacoes" => $request->solicitacoes == "S" ? 1 : 0,
                "supervisor" => $supervisor
            ]
        );
        $this->log_inserir($request->id ? "E" : "C", "permissoes", $setor->permissao()->id); // App\Http\Controllers\Controller.php
        if ($request->id) {
            $atualizar = array();
            if ($supervisor != $supervisor_ant) {
                $pessoas = Pessoas::where("id_setor", $request->id)->get();
                foreach ($pessoas as $pessoa) {
                    if ($this->comparar_num($pessoa->supervisor ? 1 : 0, $supervisor)) { // App\Http\Controllers\Controller.php
                        $chave = "id".$pessoa->id;
                        $atualizar[$chave] = array_merge($atualizar[$chave] ?? [], ["supervisor" => $supervisor]);
                    }
                }
            }
            if ($cria_usuario != $cria_usuario_ant) {
                if ($cria_usuario_ant) {
                    $usuarios = DB::table("users")
                                    ->select(
                                        "users.id",
                                        "users.email",
                                        "users.id_pessoa",
                                        "permissoes.id AS id_permissao"
                                    )
                                    ->join("pessoas", "pessoas.id", "users.id_pessoa")
                                    ->join("permissoes", "permissoes.id_usuario", "users.id")
                                    ->where("pessoas.id_setor", $request->id)
                                    ->where("users.admin", 0)
                                    ->get();
                    foreach($usuarios as $usuario) {               
                        if (isset($request->id_pessoa)) {
                            $posicao = array_search($usuario->id_pessoa, $request->id_pessoa);
                            if ($posicao !== false) {
                                $chave = "id".$usuario->id_pessoa;
                                $atualizar[$chave] = array_merge($atualizar[$chave] ?? [], [
                                    "senha" => $request->password[$posicao],
                                    "email" => $usuario->email
                                ]);
                            }
                        }
                        $this->log_inserir("D", "users", $usuario->id); // App\Http\Controllers\Controller.php
                        DB::table("users")->where("id", $usuario->id)->delete();
                    }
                } elseif (isset($request->id_pessoa)) {
                    for ($i = 0; $i < sizeof($request->id_pessoa); $i++) {
                        $pessoa = Pessoas::find($request->id_pessoa[$i]);
                        $chave = "id".$pessoa->id;
                        
                        $id_usuario = DB::table("users")->insertGetId([
                            "name" => $pessoa->nome,
                            "email" => $email,
                            "password" => Hash::make($request->password[$i]),
                            "id_pessoa" => $pessoa->id
                        ]);
                        $this->log_inserir("C", "users", $id_usuario); // App\Http\Controllers\Controller.php
                        
                        if (isset($request->email[$i])) {
                            if ($this->comparar_texto($request->email[$i], $pessoa->email)) $atualizar[$chave] = array_merge($atualizar[$chave] ?? [], ["email" => $request->email[$i]]); // App\Http\Controllers\Controller.php
                        }
                        if (isset($request->phone[$i])) {
                            if ($this->comparar_texto($request->phone[$i], $pessoa->telefone)) $atualizar[$chave] = array_merge($atualizar[$chave] ?? [], ["telefone" => $request->phone[$i]]); // App\Http\Controllers\Controller.php
                        }
                        
                        $permissao = $setor->permissao()->replicate(["id_setor"]);
                        $permissao->id_usuario = $id_usuario;
                        $permissao->save();
                        $this->log_inserir("C", "permissoes", $permissao->id); // App\Http\Controllers\Controller.php
                    }
                }
            }
            foreach ($atualizar as $pessoa => $info) {
                $id = str_replace("id", "", $pessoa);
                Pessoas::find($id)->update($info);
                $this->log_inserir("E", "pessoas", $id);
            }
        }
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
                ->whereNull("pessoas.senha")
                ->where("pessoas.id_setor", $id)
                ->where("pessoas.lixeira", 0)
                ->where("users.admin", 0)
                ->get()
        );
    }

    public function pessoas($id) {
        $resultado = new \stdClass;
        $consulta = DB::table("pessoas")
                        ->select(
                            "pessoas.id",
                            "pessoas.nome",
                            DB::raw("IFNULL(pessoas.email, '') AS email"),
                            DB::raw("IFNULL(pessoas.telefone, '') AS telefone")
                        )
                        ->leftjoin("users", "users.id_pessoa", "pessoas.id")
                        ->whereNull("users.id")
                        ->where("pessoas.id_setor", $id)
                        ->where("pessoas.lixeira", 0)
                        ->get();
        $resultado->mostrar_email = 0;
        foreach($consulta as $linha) {
            if (!$linha->email) $resultado->mostrar_email = 1;
        }
        $resultado->mostrar_fone = 0;
        foreach($consulta as $linha) {
            if (!$linha->telefone) $resultado->mostrar_fone = 1;
        }
        $resultado->consulta = $consulta;
        return json_encode($resultado);
    }

    public function permissoes($id) {
        $setor = Setores::find($id);
        $resultado = $setor->permissao();
        $resultado->cria_usuario = $setor->cria_usuario;
        return json_encode($resultado);
    }
}