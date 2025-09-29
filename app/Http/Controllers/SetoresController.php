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
        $linha = Setores::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            $comparar_sup = $this->comparar_num($linha->permissao()->supervisor, $request->supervisor == "S" ? 1 : 0); // App\Http\Controllers\Controller.php
            $adm_ant = intval($linha->cria_usuario);
            if (
                !$comparar_sup &&
                $adm_ant == $cria_usuario &&
                $linha->id_empresa == $request->id_empresa &&
                !$this->comparar_num($linha->permissao()->financeiro, $request->financeiro == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->atribuicoes, $request->atribuicoes == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->retiradas, $request->retiradas == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->pessoas, $request->pessoas == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->usuarios, $request->usuarios == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($linha->permissao()->solicitacoes, $request->solicitacoes == "S" ? 1 : 0) && // App\Http\Controllers\Controller.php
                !$this->comparar_texto($request->descr, $linha->descr) // App\Http\Controllers\Controller.php
            ) return 400;
            if ($adm_ant != $cria_usuario) {
                if ($adm_ant) {
                    $lista = array();
                    $pessoas = array();
                    $permissoes = array();
                    $consulta = DB::table("users")
                                    ->select(
                                        "users.id",
                                        "users.id_pessoa",
                                        "permissoes.id AS id_permissao"
                                    )
                                    ->join("pessoas", "pessoas.id", "users.id_pessoa")
                                    ->join("permissoes", "permissoes.id_usuario", "users.id")
                                    ->where("pessoas.id_setor", $request->id)
                                    ->where("users.admin", 0)
                                    ->get();
                    foreach($consulta as $usuario) {
                        array_push($lista, $usuario->id);
                        array_push($pessoas, $usuario->id_pessoa);
                        array_push($permissoes, $usuario->id_permissao);
                        $this->log_inserir("D", "users", $usuario->id); // App\Http\Controllers\Controller.php
                        $this->log_inserir("D", "permissoes", $usuario->id_permissao); // App\Http\Controllers\Controller.php
                    }
                    if (sizeof($lista)) {
                        if (isset($request->id_pessoa)) {
                            for ($i = 0; $i < sizeof($request->id_pessoa); $i++) {
                                $modelo = Pessoas::find($request->id_pessoa[$i]);
                                $modelo->senha = $request->password[$i];
                                $modelo->email = DB::table("users")->where("id_pessoa", $request->id_pessoa[$i])->value("email");
                                $modelo->save();
                                $this->log_inserir("E", "pessoas", $modelo->id); // App\Http\Controllers\Controller.php
                            }
                        }
                        if ($comparar_sup) {
                            if ($request->supervisor == "S") {
                                foreach ($pessoas as $pessoa) {
                                    $modelo = Pessoas::find($pessoa);
                                    if (!intval($modelo->supervisor)) {
                                        $modelo->supervisor = 1;
                                        $modelo->save();
                                        if (!in_array($modelo->id, $request->id_pessoa)) $this->log_inserir("E", "pessoas", $modelo->id); // App\Http\Controllers\Controller.php
                                    }
                                }
                            } else {
                                foreach ($pessoas as $pessoa) {
                                    $modelo = Pessoas::find($pessoa);
                                    if (intval($modelo->supervisor)) {
                                        $modelo->supervisor = 0;
                                        $modelo->save();
                                        if (!in_array($modelo->id, $request->id_pessoa)) $this->log_inserir("E", "pessoas", $modelo->id); // App\Http\Controllers\Controller.php
                                    }
                                }
                            }
                        }
                        DB::table("users")->whereIn("id", $lista)->delete();
                        Permissoes::whereIn("id", $permissoes)->delete();
                    }
                } elseif (isset($request->id_pessoa)) {
                    for ($i = 0; $i < sizeof($request->id_pessoa); $i++) {
                        $editou_pessoa = false;
                        $modelo = Pessoas::find($request->id_pessoa[$i]);
                        $email = $modelo->email;
                        if (isset($request->email[$i])) {
                            $email = $request->email[$i];
                            if ($this->comparar_texto($email, $modelo->email)) $editou_pessoa = true; // App\Http\Controllers\Controller.php
                        }
                        $telefone = $modelo->telefone;
                        if (isset($request->phone[$i])) {
                            $telefone = $request->phone[$i];
                            if ($this->comparar_texto($telefone, $modelo->telefone)) $editou_pessoa = true; // App\Http\Controllers\Controller.php
                        }
                        if ($comparar_sup) {
                            if ($request->supervisor == "S") {
                                if (!intval($modelo->supervisor)) {
                                    $modelo->supervisor = 1;
                                    $modelo->save();
                                    $editou_pessoa = true;
                                }
                            } else {
                                if (intval($modelo->supervisor)) {
                                    $modelo->supervisor = 0;
                                    $modelo->save();
                                    $editou_pessoa = true;
                                }
                            }
                        }
                        $id_usuario = DB::table("users")->insertGetId([
                            "name" => $modelo->nome,
                            "email" => $email,
                            "senha" => Hash::make($request->password[$i]),
                            "id_pessoa" => $request->id_pessoa[$i]
                        ]);
                        $this->log_inserir("C", "users", $id_usuario); // App\Http\Controllers\Controller.php
                        $modelo->telefone = $telefone;
                        $modelo->email = $email;
                        $modelo->save();
                        if ($editou_pessoa) $this->log_inserir("E", "pessoas", $modelo->id); // App\Http\Controllers\Controller.php
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
        $linha->permissao()->updateOrCreate(
            ["id_setor" => $linha->id],
            [
                "financeiro" => $request->financeiro == "S" ? 1 : 0,
                "atribuicoes" => $request->atribuicoes == "S" ? 1 : 0,
                "retiradas" => $request->retiradas == "S" ? 1 : 0,
                "pessoas" => $request->pessoas == "S" ? 1 : 0,
                "usuarios" => $request->usuarios == "S" ? 1 : 0,
                "solicitacoes" => $request->solicitacoes == "S" ? 1 : 0,
                "supervisor" => $request->supervisor == "S" ? 1 : 0
            ]
        );
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
}