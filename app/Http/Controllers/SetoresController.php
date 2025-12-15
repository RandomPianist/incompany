<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Hash;
use App\Models\Setores;
use App\Models\Pessoas;
use App\Models\Empresas;
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
        $param = str_replace("?", "setores.descr", $param);
        $param = str_replace("!", "setores.descr", $param);
        return DB::table("setores")
                    ->select(
                        "setores.id",
                        "setores.descr",
                        "empresas.nome_fantasia AS empresa"
                    )
                    ->join("empresas", "setores.id_empresa", "empresas.id")
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "setores")) // App\Http\Controllers\Controller.php
                    ->whereRaw($param)
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
        return json_encode(
            DB::table("setores")
                ->select(
                    "setores.id",
                    "setores.descr",
                    "setores.cria_usuario",
                    "setores.id_empresa",
                    "setores.supervisor",
                    "setores.visitante",
                    "empresas.nome_fantasia AS empresa",
                    "permissoes.financeiro",
                    "permissoes.atribuicoes",
                    "permissoes.retiradas",
                    "permissoes.usuarios",
                    "permissoes.pessoas",
                    "permissoes.solicitacoes",
                    DB::raw("
                        CASE
                            WHEN pes.id_setor IS NOT NULL THEN 1
                            ELSE 0
                        END AS pessoas
                    "),
                    DB::raw("
                        CASE
                            WHEN usu.id_setor IS NOT NULL THEN 1
                            ELSE 0
                        END AS usuarios
                    "),
                    DB::raw("
                        CASE
                            WHEN log.id IS NOT NULL THEN 1
                            ELSE 0
                        END AS sistema
                    "),
                    DB::raw("
                        CASE
                            WHEN setores.id = ".Pessoas::find(Auth::user()->id_pessoa)->id_setor." THEN 1
                            ELSE 0
                        END AS meu_setor
                    ")
                )
                ->leftjoin("empresas", "empresas.id", "setores.id_empresa")
                ->leftjoin("permissoes", "permissoes.id_setor", "setores.id")
                ->leftjoin("log", function($join) {
                    $join->on("log.fk", "setores.id")
                        ->where("log.origem", "SYS")
                        ->where("log.tabela", "setores");
                })
                ->leftjoinSub(
                    DB::table("pessoas")
                        ->selectRaw("DISTINCTROW id_setor")
                        ->where("lixeira", 0),
                    "pes",
                    "pes.id_setor",
                    "setores.id"
                )
                ->leftjoinSub(
                    DB::table("pessoas")
                        ->selectRaw("DISTINCTROW pessoas.id_setor")
                        ->join("users", "users.id_pessoa", "pessoas.id")
                        ->where("pessoas.lixeira", 0),
                    "usu",
                    "usu.id_setor",
                    "setores.id"
                )
                ->where("setores.id", $id)
                ->first()
        );
    }

    public function mostrar2($id) {
        return json_encode(Setores::find($id));
    }

    public function salvar(Request $request) {
        $emp = $this->obter_empresa(); // App\Http\Traits\GlobaisTrait.php
        if ($this->consultar_main($request)->msg) return 401;
        if ($this->validar_permissoes($request) != 200) return 401; // App\Http\Controllers\Controller.php
        $ok = true;
        if ($emp) $ok = ($request->id_empresa == $emp || $request->id_empresa == Empresas::find($emp)->id_matriz);
        if (!$ok) return 401;
        $cria_usuario = $request->cria_usuario;
        $supervisor = $request->supervisor;
        $visitante = $request->visitante;
        $cria_usuario_ant = null;
        $supervisor_ant = null;
        $visitante_ant = null;
        $setor = Setores::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            $cria_usuario_ant = $setor->cria_usuario;
            $supervisor_ant = $setor->supervisor ? 1 : 0;
            if (
                $setor->id_empresa == $request->id_empresa &&
                !$this->comparar_texto($request->descr, $setor->descr) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($cria_usuario_ant, $cria_usuario) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($supervisor_ant, $supervisor) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($visitante_ant, $visitante) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao->financeiro, $request->financeiro) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao->atribuicoes, $request->atribuicoes) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao->retiradas, $request->retiradas) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao->pessoas, $request->pessoas) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao->usuarios, $request->usuarios) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($setor->permissao->solicitacoes, $request->solicitacoes) // App\Http\Controllers\Controller.php
            ) return 400;
        }
        $setor->descr = mb_strtoupper($request->descr);
        $setor->id_empresa = $request->id_empresa;
        $setor->supervisor = $supervisor;
        $setor->visitante = $visitante;
        $setor->cria_usuario = $cria_usuario;
        $setor->save();
        $this->log_inserir($request->id ? "E" : "C", "setores", $setor->id); // App\Http\Controllers\Controller.php
        $setor->permissao()->updateOrCreate(
            ["id_setor" => $setor->id],
            [
                "financeiro" => $request->financeiro,
                "atribuicoes" => $request->atribuicoes,
                "retiradas" => $request->retiradas,
                "pessoas" => $request->pessoas,
                "usuarios" => $request->usuarios,
                "solicitacoes" => $request->solicitacoes
            ]
        );
        $this->log_inserir($request->id ? "E" : "C", "permissoes", $setor->permissao->id); // App\Http\Controllers\Controller.php
        if ($request->id) {
            $atualizar = array();
            $pessoas = Pessoas::where("id_setor", $request->id)->get();
            foreach ($pessoas as $pessoa) {
                $chave = "id".$pessoa->id;
                if (
                    $supervisor != $supervisor_ant &&
                    $this->comparar_num($pessoa->supervisor ? 1 : 0, $supervisor) // App\Http\Controllers\Controller.php
                ) {
                    $atualizar[$chave] = array_merge($atualizar[$chave] ?? [], ["supervisor" => $supervisor]);
                }
                if (
                    $visitante != $visitante_ant &&
                    $this->comparar_num($pessoa->visitante ? 1 : 0, $visitante) // App\Http\Controllers\Controller.php
                ) {
                    $atualizar[$chave] = array_merge($atualizar[$chave] ?? [], ["visitante" => $visitante]);
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
                            "email" => $pessoa->email,
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
                        
                        $permissao = $setor->permissao->replicate(["id_setor"]);
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
        $emp = $this->obter_empresa(); // App\Http\Traits\GlobaisTrait.php
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
        $resultado = $setor->permissao;
        $resultado->cria_usuario = $setor->cria_usuario;
        $resultado->supervisor = $setor->supervisor;
        $resultado->visitante = $setor->visitante;
        return json_encode($resultado);
    }
}