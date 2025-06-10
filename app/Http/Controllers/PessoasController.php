<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Hash;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;

class PessoasController extends ControllerKX {
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
                            ->select("id_pessoa")
                            ->groupby("id_pessoa"),
                    "ret", "ret.id_pessoa", "pessoas.id")
                    ->where(function($sql) use($tipo) {
                        $id_emp = intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
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

    private function criar_usuario($id_pessoa, Request $request) {
        $senha = Hash::make($request->password);
        DB::statement("INSERT INTO users (name, email, password, id_pessoa) VALUES ('".trim($request->nome)."', '".trim($request->email)."', '".$senha."', ".$id_pessoa.")");
        $this->log_inserir("C", "users", DB::table("users")
                                            ->selectRaw("MAX(id) AS id")
                                            ->value("id")
        );
    }

    private function deletar_usuario($id_pessoa) {
        $fk = DB::table("users")
                ->where("id_pessoa", $id_pessoa)
                ->value("id");
        DB::statement("DELETE FROM users WHERE id_pessoa = ".$id_pessoa);
        $this->log_inserir("D", "users", $fk);
    }

    private function salvar_main($modelo, Request $request) {
        $modelo->nome = mb_strtoupper($request->nome);
        $modelo->cpf = $request->cpf;
        $modelo->funcao = mb_strtoupper($request->funcao);
        if ($request->admissao) $modelo->admissao = Carbon::createFromFormat('d/m/Y', $request->admissao)->format('Y-m-d');
        $modelo->id_empresa = $request->id_empresa;
        $modelo->id_setor = $request->id_setor;
        if (trim($request->senha)) $modelo->senha = $request->senha;
        $modelo->supervisor = $request->supervisor;
        if ($request->file("foto")) $modelo->foto = $request->file("foto")->store("uploads", "public");
        $modelo->save();
        $this->log_inserir($request->id ? "E" : "C", "pessoas", $modelo->id);
        return $modelo;
    }

    private function cria_usuario($id) {
        return intval($this->setor_mostrar($id)->cria_usuario);
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
                            if (sizeof(
                                DB::table("empresas")
                                    ->where("id_matriz", $id_emp)
                                    ->where("lixeira", 0)
                                    ->get()
                            ) > 0) $where .= " OR id_matriz = ".$id_emp;
                            $sql->whereRaw($where);
                        }
                    })
                    ->orderby("nome_fantasia")
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
        $resultado = new \stdClass;
        if ($this->empresa_consultar($request)) {
            $resultado->tipo = "invalido";
            $resultado->dado = "Empresa";
        } else if (!sizeof(
            DB::table("setores")
                ->where("id", $request->id_setor)
                ->where("descr", $request->setor)
                ->get()
        )) {
            $resultado->tipo = "invalido";
            $resultado->dado = "Setor";
        } else if (sizeof(
            DB::table("pessoas")
                ->where("lixeira", 0)
                ->where("cpf", $request->cpf)
                ->get()
        ) && trim($request->cpf)) {
            $resultado->tipo = "duplicado";
            $resultado->dado = "CPF";
        } else if (sizeof(
            DB::table("pessoas")
                ->join("users", "users.id_pessoa", "pessoas.id")
                ->where("lixeira", 0)
                ->where("email", $request->email)
                ->get()
        )) {
            $resultado->tipo = "duplicado";
            $resultado->dado = "e-mail";
        } else {
            $resultado->tipo = "ok";
            $resultado->dado = "";
        }
        return json_encode($resultado);
    }

    public function mostrar($id) {
        return json_encode(
            DB::table("pessoas")
                ->select(
                    "pessoas.id",
                    "pessoas.cpf",
                    "pessoas.id_setor",
                    "pessoas.id_empresa",
                    "pessoas.funcao",
                    "pessoas.supervisor",
                    "pessoas.foto",
                    DB::raw("DATE_FORMAT(pessoas.admissao, '%d/%m/%Y') AS admissao"),
                    "setores.descr AS setor",
                    "empresas.nome_fantasia AS empresa",
                    DB::raw("IFNULL(users.name, pessoas.nome) AS nome"),
                    "users.email"
                )
                ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                ->leftjoin("setores", "setores.id", "pessoas.id_setor")
                ->leftjoin("users", "users.id_pessoa", "pessoas.id")
                ->where("pessoas.id", $id)
                ->first()
        );
    }

    public function aviso($id) {
        $resultado = new \stdClass;
        if ($id != Auth::user()->id_pessoa) {
            $nome = Pessoas::find($id)->nome;
            $resultado->permitir = 1;
            $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
        } else {
            $resultado->permitir = 0;
            $resultado->aviso = "Não é possível excluir a si mesmo";
        }
        return json_encode($resultado);
    }

    public function salvar(Request $request) {
        $linha = 0;
        if ($request->id) {
            $setor_ant = Pessoas::find($request->id)->id_setor;
            if ($setor_ant != $request->id_setor) {
                if ($this->cria_usuario($setor_ant)) $this->deletar_usuario($request->id);    
                else if ($this->cria_usuario($request->id_setor)) $this->criar_usuario($request->id, $request);
            } else if (
                $this->cria_usuario($request->id_setor) && (
                    $request->password ||
                    mb_strtoupper(trim(Auth::user()->email)) != mb_strtoupper(trim($request->email)) ||
                    mb_strtoupper(trim(Auth::user()->name))  != mb_strtoupper(trim($request->nome))
                )
            ) {
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
            }
            $modelo = Pessoas::find($request->id);
            $linha = $this->salvar_main($modelo, $request);
        } else {
            $modelo = new Pessoas;
            $linha = $this->salvar_main($modelo, $request);
            if ($this->cria_usuario($linha->id_setor)) $this->criar_usuario($linha->id, $request);
        }
        if ($request->password) {
            if (str_replace(".", "", $request->password) == $request->password && is_numeric($request->password) && strlen($request->password) == 4) {
                $linha->senha = $request->password;
                $linha->save();
            }
        }
        $tipo = $request->tipo;
        if (!$tipo) {
            $tipo = "U";
            if (intval(Pessoas::find($modelo->id)->supervisor)) $tipo = "S";
            if (!intval(Pessoas::find($modelo->id)->id_empresa)) $tipo = "A";
        }
        return redirect("/colaboradores/pagina/".$tipo);
    }

    public function excluir(Request $request) {
        $linha = Pessoas::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "pessoas", $linha->id);
        if ($this->cria_usuario($linha->id_setor)) $this->deletar_usuario($linha->id);
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
        $id_emp = intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
        $resultado = new \stdClass;
        $empresas = $this->busca_emp($id_emp, 0);
        foreach($empresas as $matriz) {
            $filiais = $this->busca_emp($id_emp, $matriz->id);
            $matriz->filiais = $filiais;
        }
        $resultado->empresas = $empresas;
        $resultado->setores = DB::table("setores")
                                    ->select(
                                        "id",
                                        "descr"
                                    )
                                    ->where("cria_usuario", 0)
                                    ->where(function($sql) use($id_emp) {
                                        $where = "id_empresa = ".$id_emp." OR id_empresa IN (
                                            SELECT id_matriz
                                            FROM empresas
                                            WHERE id = ".$id_emp."
                                        ) OR id_empresa IN (
                                            SELECT id
                                            FROM empresas
                                            WHERE id_matriz = ".$id_emp."
                                        )";
                                        $sql->whereRaw($where);
                                    })
                                    ->orderby("descr")
                                    ->get();
        return json_encode($resultado);
    }
}