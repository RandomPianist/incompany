<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Hash;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Setores;
use App\Models\Permissoes;
use App\Http\Traits\NomearTrait;

class PessoasController extends ControllerListavel {
    use NomearTrait;

    private function consultar_main(Request $request) {
        $resultado = new \stdClass;
        if (trim($request->cpf) && !$request->id &&
            Pessoas::where("lixeira", 0)
                    ->where("cpf", $request->cpf)
                    ->exists()
        ) {
            $resultado->tipo = "duplicado";
            $resultado->dado = "CPF";
        } elseif (!$request->id &&
            DB::table("pessoas")
                ->leftjoin("users", "users.id_pessoa", "pessoas.id")
                ->where(function($sql) use($request) {
                    $sql->where("users.email", $request->email)
                        ->orWhere("pessoas.email", $request->email);
                })
                ->where("pessoas.lixeira", 0)
                ->exists()
        ) {
            $resultado->tipo = "duplicado";
            $resultado->dado = "e-mail";
        } else {
            $resultado->tipo = "ok";
            $resultado->dado = "";
        }
        return $resultado;
    }

    private function aviso_main($id) {
        $resultado = $this->pode_abrir_main("pessoas", $id, "excluir"); // App\Http\Controllers\Controller.php
        if (!$resultado->permitir) return $resultado;
        $resultado = new \stdClass;
        if ($id != Auth::user()->id_pessoa) {
            $nome = Pessoas::find($id)->nome;
            $resultado->permitir = 1;
            $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
            return $resultado;
        }
        $resultado->permitir = 0;
        $resultado->aviso = "Não é possível excluir a si mesmo";
        return $resultado;
    }

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
                                WHEN ret.id_pessoa IS NOT NULL THEN 1
                                ELSE 0
                            END AS possui_retiradas
                        ")
                    )
                    ->leftjoinSub(
                        DB::table("retiradas")
                            ->selectRaw("DISTINCTROW id_pessoa")
                            ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "retiradas")),
                        "ret",
                        "ret.id_pessoa",
                        "pessoas.id"
                    )
                    ->leftjoin("setores", "setores.id", "pessoas.id_setor")
                    ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                    ->where(function($sql) use($tipo) {
                        $id_emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
                        if ($id_emp) $sql->whereRaw($id_emp." IN (empresas.id, empresas.id_matriz)");
                        if (in_array($tipo, ["A", "U"])) {
                            $sql->where(function($q) {
                                $q->whereNull("setores.id")
                                    ->orWhere("setores.cria_usuario", 1);
                            });
                            if ($tipo == "A") $sql->where("pessoas.id_empresa", 0);
                        } else $sql->where("pessoas.supervisor", ($tipo == "S" ? 1 : 0));
                    })
                    ->whereRaw(str_replace("?", "pessoas.nome", $where))
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
        $this->alterar_usuario_editando("pessoas", $id); // App\Http\Controllers\Controller.php
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
                    "pessoas.telefone",
                    DB::raw("IFNULL(setores.cria_usuario, 1) AS cria_usuario"),
                    DB::raw("DATE_FORMAT(pessoas.admissao, '%d/%m/%Y') AS admissao"),
                    DB::raw("IFNULL(users.name, pessoas.nome) AS nome"),
                    DB::raw("
                        CASE
                            WHEN users.email IS NOT NULL THEN users.email
                            WHEN pessoas.email IS NOT NULL THEN pessoas.email
                            ELSE ''
                        END AS email
                    "),
                    DB::raw("IFNULL(users.id, 0) AS id_usuario"),
                    DB::raw("IFNULL(permissoes.financeiro, 0) AS financeiro"),
                    DB::raw("IFNULL(permissoes.atribuicoes, 0) AS atribuicoes"),
                    DB::raw("IFNULL(permissoes.retiradas, 0) AS retiradas"),
                    DB::raw("IFNULL(permissoes.usuarios, 0) AS usuarios"),
                    DB::raw("IFNULL(permissoes.pessoas, 0) AS pessoas"),
                    DB::raw("IFNULL(permissoes.solicitacoes, 0) AS solicitacoes")
                )
                ->leftjoin("setores", "setores.id", "pessoas.id_setor")
                ->leftjoin("users", "users.id_pessoa", "pessoas.id")
                ->leftjoin("permissoes", "permissoes.id_usuario", "users.id")
                ->where("pessoas.id", $id)
                ->first()
        );
    }

    public function salvar(Request $request) {
        if ($this->verifica_vazios($request, ["nome", "funcao", "admissao", "cpf", "telefone"])) return 400; // App\Http\Controllers\Controller.php
        $setor = $request->setor;
        $m_setor = Setores::find($setor);
        if ($m_setor !== null) {
            if ($m_setor->cria_usuario) {
                if (!trim($request->email)) return 400;
                if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) return 400;
            }
        }
        $admissao = Carbon::createFromFormat('d/m/Y', $request->admissao)->startOfDay();
        $hj = Carbon::today();
        if ($admissao->greaterThan($hj)) return 400;
        if ($this->consultar_main($request)->tipo != "ok") return 401;

        if ($this->obter_empresa()) { // App\Http\Controllers\Controller.php
            $dados = $this->minhas_empresas(); // App\Http\Controllers\Controller.php
            $empresas_possiveis_obj = $dados->empresas;
            $empresas_possiveis_arr = array();
            foreach ($empresas_possiveis_obj as $matriz) {
                if ($dados->filial == "N") array_push($empresas_possiveis_arr, intval($matriz->id));
                $filiais = $matriz->filiais;
                foreach ($filiais as $filial) array_push($empresas_possiveis_arr, intval($filial->id));
            }
            if (!in_array(intval($request->id_empresa), $empresas_possiveis_arr)) return 401;
        }

        $pessoa = Pessoas::firstOrNew(["id" => $request->id]);
        $pessoa->nome = mb_strtoupper($request->nome);
        $pessoa->cpf = $request->cpf;
        $pessoa->funcao = mb_strtoupper($request->funcao);
        $pessoa->admissao = Carbon::createFromFormat('d/m/Y', $request->admissao)->format('Y-m-d');
        $pessoa->id_empresa = $request->id_empresa;
        $pessoa->id_setor = $setor;
        if (trim($request->senha)) $pessoa->senha = $request->senha;
        $pessoa->supervisor = $request->supervisor;
        if ($request->file("foto")) $pessoa->foto = $request->file("foto")->store("uploads", "public");
        $pessoa->telefone = $request->telefone;
        $pessoa->email = $request->email;
        $pessoa->save();
        $this->log_inserir($request->id ? "E" : "C", "pessoas", $pessoa->id); // App\Http\Controllers\Controller.php

        $usuario = DB::table("users")
                            ->select(
                                "id",
                                "email"
                            )
                            ->where("id_pessoa", $pessoa->id)
                            ->first();
        if ($pessoa->setor->cria_usuario) {
            $json_usuario = array();
            $id_usuario = 0;
            $password = trim($request->password);
            $email = trim($request->email);
            if ($password) $json_usuario += ["password" => Hash::make($password)];

            if ($usuario !== null) {
                if ($email != $usuario->email && $email) $json_usuario += ["email" => $email];
            } elseif ($email) $json_usuario += ["email" => $email];

            if ($usuario === null) {
                $json_usuario += ["id_pessoa" => $pessoa->id];
                $id_usuario = DB::table("users")->insertGetId($json_usuario);
            } else {
                DB::table("users")->where("id", $usuario->id)->update($json_usuario);
                $id_usuario = $usuario->id;
            }
            $this->log_inserir($usuario === null ? "C" : "E", "users", $id_usuario); // App\Http\Controllers\Controller.php
            $permissao = Permissoes::updateOrCreate(
                ["id_usuario" => $id_usuario],
                [
                    "financeiro" => $request->financeiro,
                    "atribuicoes" => $request->atribuicoes,
                    "retiradas" => $request->retiradas,
                    "pessoas" => $request->pessoas,
                    "usuarios" => $request->usuarios,
                    "solicitacoes" => $request->solicitacoes,
                    "supervisor" => $request->supervisor
                ]
            );
            $this->log_inserir($usuario === null ? "C" : "E", "permissoes", $permissao->id); // App\Http\Controllers\Controller.php
        } elseif ($usuario !== null) {
            $this->log_inserir("D", "users", $usuario->id); // App\Http\Controllers\Controller.php
            DB::table("users")->where("id", $usuario->id)->delete();
        }
        return redirect("/colaboradores/pagina/".substr(strtoupper($this->nomear($pessoa->id)), 0, 1)); // App\Http\Traits\NomearTrait.php
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function excluir($id) {
        if (!$this->aviso_main($request->id)->permitir) return 400;
        $linha = Pessoas::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "pessoas", $linha->id); // App\Http\Controllers\Controller.php
    }

    public function consultar(Request $request) {
        return json_encode($this->consultar_main($request));
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
