<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Hash;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Empresas;

class PessoasController extends Controller {
    private function busca($where, $tipo) {
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
                                WHEN ret.id_pessoa IS NULL THEN 0
                                ELSE 1
                            END AS possui_retiradas
                        ")
                    )
                    ->leftjoin("setores", "setores.id", "pessoas.id_setor")
                    ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                    ->leftjoinSub(
                        DB::table("retiradas")
                            ->select(
                                "id_pessoa",
                                "id_empresa"
                            )
                            ->groupby(
                                "id_pessoa",
                                "id_empresa"
                            ),
                        "ret",
                        function($join) {
                            $join->on(function($sql) {
                                $sql->on("pessoas.id", "ret.id_pessoa")
                                    ->where("pessoas.id_empresa", 0);
                            })->orOn(function($sql) {
                                $sql->on("pessoas.id", "ret.id_pessoa")
                                    ->on("pessoas.id_empresa", "ret.id_empresa");
                            });
                        }
                    )
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

    private function criados_por_mim($usuarios) {
        $consulta = DB::table("pessoas")
                        ->select("users.id")
                        ->join("users", "users.id_pessoa", "pessoas.id")
                        ->whereIn("pessoas.id_usuario", $usuarios)
                        ->pluck("id");
        $adicionou = false;
        foreach ($consulta as $id) {
            $id_usuario = intval($id);
            if (!in_array($id_usuario, $usuarios)) {
                array_push($usuarios, $id_usuario);
                $adicionou = true;
            }
        }
        return $adicionou ? $this->criados_por_mim($usuarios) : $usuarios;
    }

    private function permissao_usuario($id_pessoa) {
        $consulta = DB::table("pessoas")->selectRaw("IFNULL(id_usuario, 0) AS id_usuario");
        if (!intval($consulta->where("id", Auth::user()->id_pessoa)->value("id_usuario"))) return true;
        if (!intval($consulta->where("id", $id_pessoa)->value("id_usuario"))) return true;
        return in_array(Pessoas::find($id_pessoa)->id_usuario, $this->criados_por_mim([Auth::user()->id]));
    }

    private function criar_usuario($id_pessoa, Request $request, $tipo) {
        if (($tipo == "supervisor" && Pessoas::find(Auth::user()->id_pessoa)->supervisor) || ($tipo == "permissao" && $this->permissao_usuario($id_pessoa))) {
            $senha = Hash::make($request->password);
            DB::statement("INSERT INTO users (name, email, password, id_pessoa) VALUES ('".trim($request->nome)."', '".trim($request->email)."', '".$senha."', ".$id_pessoa.")");
            $this->log_inserir("C", "users", DB::table("users")
                                                ->selectRaw("MAX(id) AS id")
                                                ->value("id")
            ); // App\Http\Controllers\Controller.php
            return true;
        }
        return false;
    }

    private function deletar_usuario($id_pessoa) {
        $fk = DB::table("users")
                ->where("id_pessoa", $id_pessoa)
                ->value("id");
        DB::statement("DELETE FROM users WHERE id_pessoa = ".$id_pessoa);
        $this->log_inserir("D", "users", $fk); // App\Http\Controllers\Controller.php
    }

    private function salvar_main(Pessoas $modelo, Request $request) {
        $modelo->nome = mb_strtoupper($request->nome);
        $modelo->cpf = $request->cpf;
        $modelo->funcao = mb_strtoupper($request->funcao);
        $modelo->admissao = Carbon::createFromFormat('d/m/Y', $request->admissao)->format('Y-m-d');
        $modelo->id_empresa = $request->id_empresa;
        $modelo->id_setor = $request->id_setor;
        if (trim($request->senha)) $modelo->senha = $request->senha;
        $modelo->supervisor = $request->supervisor;
        if ($request->file("foto")) $modelo->foto = $request->file("foto")->store("uploads", "public");
        $modelo->id_usuario = Auth::user()->id;
        $modelo->save();
        $this->log_inserir($request->id ? "E" : "C", "pessoas", $modelo->id); // App\Http\Controllers\Controller.php
        return $modelo;
    }

    private function cria_usuario($id) {
        return intval($this->setor_mostrar($id)->cria_usuario); // App\Http\Controllers\Controller.php
    }

    private function busca_emp($id_emp, $id_matriz) {
        return DB::table("empresas")
                    ->select(
                        "id",
                        "nome_fantasia"
                    )
                    ->where("id_matriz", $id_matriz)
                    ->where(function($sql) use($id_emp) {
                        if ($id_emp) {
                            $where = "id = ".$id_emp;
                            if (DB::table("empresas")
                                    ->where("id_matriz", $id_emp)
                                    ->where("lixeira", 0)
                                    ->exists()
                            ) $where .= " OR id_matriz = ".$id_emp;
                            $sql->whereRaw($where);
                        }
                    })
                    ->where(function($sql) {
                        $m_emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
                        if ($m_emp) {
                            $possiveis = [$m_emp];
                            $matriz = intval(Empresas::find($possiveis[0])->id_matriz);
                            if ($matriz) {
                                array_push($possiveis, $matriz);
                                $sql->whereIn("id", $possiveis);
                            }
                        }
                    })
                    ->where("lixeira", 0)
                    ->orderby("nome_fantasia")
                    ->get();
    }

    private function consultar_main(Request $request) {
        $resultado = new \stdClass;
        if (trim($request->cpf) && !$request->id &&
            DB::table("pessoas")
                ->where("lixeira", 0)
                ->where("cpf", $request->cpf)
                ->exists()
        ) {
            $resultado->tipo = "duplicado";
            $resultado->dado = "CPF";
        } else if (!$request->id &&
            DB::table("pessoas")
                ->join("users", "users.id_pessoa", "pessoas.id")
                ->where("lixeira", 0)
                ->where("email", $request->email)
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
        $resultado = new \stdClass;
        if ($id != Auth::user()->id_pessoa) {
            if (DB::table("users")->where("id_pessoa", $id)->exists()) {
                if (!$this->permissao_usuario($id)) {
                    $resultado->permitir = 0;
                    $resultado->aviso = "Você não tem permissão para excluir essa pessoa";
                    return $resultado;
                }
            }
            $nome = Pessoas::find($id)->nome;
            $resultado->permitir = 1;
            $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
            return $resultado;
        }
        $resultado->permitir = 0;
        $resultado->aviso = "Não é possível excluir a si mesmo";
        return $resultado;
    }

    private function obter_dados() {
        $resultado = new \stdClass;
        $id_emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
        $matriz = $id_emp ? intval(Empresas::find($id_emp)->id_matriz) : 0;
        $filial = "N";
        if ($matriz > 0) {
            $filial = "S";
            $id_emp = $matriz;
        }
        $empresas = $this->busca_emp($id_emp, 0);
        foreach($empresas as $matriz) {
            $filiais = $this->busca_emp($id_emp, $matriz->id);
            $matriz->filiais = $filiais;
        }
        $resultado->filial = $filial;
        $resultado->empresas = $empresas;
        return $resultado;
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

    public function listar(Request $request) {
        $filtro = trim($request->filtro);
        if ($filtro) {
            $busca = $this->busca("nome LIKE '".$filtro."%'", $request->tipo);
            if (sizeof($busca) < 3) $busca = $this->busca("nome LIKE '%".$filtro."%'", $request->tipo);
            if (sizeof($busca) < 3) $busca = $this->busca("(nome LIKE '%".implode("%' AND nome LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')", $request->tipo);
        } else $busca = $this->busca("1", $request->tipo);
        return json_encode($busca);
    }

    public function consultar(Request $request) {
        $consulta = $this->consultar_main($request);
        if ($consulta->tipo == "ok") {
            if ($request->id) {
                if ($this->cria_usuario($request->id_setor) != $this->cria_usuario(Pessoas::find($request->id)->id_setor)) {
                    if (!$this->permissao_usuario($request->id)) $consulta->tipo = "permissao";
                }
            } else if ($this->cria_usuario($request->id_setor)) {
                if (!intval(Pessoas::find(Auth::user()->id_pessoa)->supervisor)) $consulta->tipo = "permissao";
            }
        }
        return json_encode($consulta);
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
                    "users.email"
                )
                ->leftjoin("users", "users.id_pessoa", "pessoas.id")
                ->where("pessoas.id", $id)
                ->first()
        );
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function salvar(Request $request) {
        if ($this->verifica_vazios($request, ["nome", "funcao", "admissao", "cpf"])) return 400; // App\Http\Controllers\Controller.php
        if ($this->cria_usuario($request->id_setor)) {
            if (!trim($request->email)) return 400;
            if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) return 400;
        }
        $admissao = Carbon::createFromFormat('d/m/Y', $request->admissao)->startOfDay();
        $hj = Carbon::today();
        if ($admissao->greaterThan($hj)) return 400;
        if ($this->consultar_main($request)->tipo != "ok") return 401;

        if ($this->obter_empresa()) { // App\Http\Controllers\Controller.php
            $dados = $this->obter_dados();
            $empresas_possiveis_obj = $dados->empresas;
            $empresas_possiveis_arr = array();
            foreach ($empresas_possiveis_obj as $matriz) {
                if ($dados->filial == "N") array_push($empresas_possiveis_arr, intval($matriz->id));
                $filiais = $matriz->filiais;
                foreach ($filiais as $filial) array_push($empresas_possiveis_arr, intval($filial->id));
            }
            if (!in_array(intval($request->id_empresa), $empresas_possiveis_arr)) return 401;
        }

        $linha = 0;
        $setores = array();
        if ($request->id_setor) array_push($setores, $request->id_setor);
        if ($request->id) {
            $modelo = Pessoas::find($request->id);
            $setor_ant = $modelo->id_setor;
            if (
                !$this->comparar_texto($request->cpf, $modelo->cpf) && // App\Http\Controllers\Controller.php
                !$this->comparar_texto($request->nome, $modelo->nome) && // App\Http\Controllers\Controller.php
                !$this->comparar_texto($request->funcao, $modelo->funcao) && // App\Http\Controllers\Controller.php
                !$this->comparar_num($request->id_setor, $setor_ant) && // App\Http\Controllers\Controller.php
                !$this->comparar_texto($admissao->format('Y-m-d'), strval($modelo->admissao)) // App\Http\Controllers\Controller.php
            ) return 400;
            if ($setor_ant != $request->id_setor) array_push($setores, $setor_ant);
            if ($request->id_setor) {
                if (
                    $this->cria_usuario($request->id_setor) && $this->cria_usuario($setor_ant) && (
                        $request->password ||
                        $this->comparar_texto($request->email, $modelo->email) || // App\Http\Controllers\Controller.php
                        $this->comparar_texto($request->nome, $modelo->nome) // App\Http\Controllers\Controller.php
                    )
                ) {
                    if (!$this->permissao_usuario($request->id)) return 401;
                    $senha = Hash::make($request->password);
                    $atualiza_senha = $request->password ? "password = '".$senha."'," : "";
                    DB::statement("
                        UPDATE users SET
                            ".$atualiza_senha."
                            name = '".trim($request->nome)."',
                            email = '".trim($request->email)."'
                        WHERE id_pessoa = ".$request->id
                    );
                    $this->log_inserir("E", "users", DB::table("users")
                                                        ->where("id_pessoa", $request->id)
                                                        ->value("id")
                    );
                } else if ($this->cria_usuario($request->id_setor) != $this->cria_usuario($setor_ant)) {
                    if ($this->cria_usuario($setor_ant)) {
                        if (!$this->permissao_usuario($request->id)) return 401;
                        $this->deletar_usuario($request->id);
                    } else if (!$this->criar_usuario($request->id, $request, "permissao")) return 401;
                }
            }
            $linha = $this->salvar_main($modelo, $request);
        } else {
            $modelo = new Pessoas;
            $linha = $this->salvar_main($modelo, $request);
            if ($this->cria_usuario($linha->id_setor)) {
                if (!$this->criar_usuario($linha->id, $request, "supervisor")) {
                    DB::statement("DELETE FROM log WHERE fk = ".$linha->id." AND tabela = 'pessoas'");
                    $linha->delete();
                    return 401;
                }
            }
        }
        if ($request->password) {
            if (str_replace(".", "", $request->password) == $request->password && is_numeric($request->password) && strlen($request->password) == 4) {
                $linha->senha = $request->password;
                $linha->save();
            }
        }
        $modelo = Pessoas::find($linha->id);
        $tipo = $request->tipo;
        if ($this->cria_usuario($modelo->id_setor)) $tipo = !intval($modelo->id_empresa) ? "A" : "U";
        else $tipo = intval($modelo->supervisor) ? $tipo = "S" : "F";
        if ($request->id || 
            !DB::table("vativos")
                ->where("id", $linha->id)
                ->where("atb_todos", ">", 0)
                ->exists()
        ) {
            $this->atualizar_atribuicoes(
                DB::table("vatbold")
                    ->select(
                        "psm_chave",
                        "psm_valor"
                    )
                    ->where("psm_chave", "S")
                    ->whereIn("psm_valor", $setores)
                    ->groupby(
                        "psm_chave",
                        "psm_valor"
                    )
                    ->get()
            ); // App\Http\Controllers\Controller.php            
        } else $this->atualizar_tudo(explode(",", DB::table("vativos")->where("id", $linha->id)->value("maquinas")), "M", true);
        return redirect("/colaboradores/pagina/".$tipo);
    }

    public function excluir(Request $request) {
        if (!$this->aviso_main($request->id)->permitir) return 401;
        $linha = Pessoas::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "pessoas", $linha->id);
        if ($this->cria_usuario($linha->id_setor)) $this->deletar_usuario($linha->id);
        $tabelas = ["mat_vatbaux", "mat_vatribuicoes", "mat_vretiradas", "mat_vultretirada"];
        foreach ($tabelas as $tabela) DB::statement("DELETE FROM ".$tabela." WHERE id_pessoa = ".$linha->id);
        return 200;
    }

    public function supervisor(Request $request) {
        return $this->supervisor_consultar($request);
    }

    public function alterar_empresa(Request $request) {
        if (Auth::user()->admin) {
            $pessoa = Pessoas::find(Auth::user()->id_pessoa);
            $pessoa->id_empresa = $request->idEmpresa;
            $pessoa->save();
        }
    }

    public function modal() {
        return json_encode($this->obter_dados());
    }

    public function senha(Request $request) {
        if (
            DB::table("pessoas")
                ->where("id", $request->id)
                ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "pessoas")) // App\Http\Controllers\Controller.php
                ->exists()
        ) return 401;
        return Pessoas::find($request->id)->senha;
    }
}
