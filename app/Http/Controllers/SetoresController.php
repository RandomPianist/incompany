<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Setores;
use App\Models\Permissoes;
use Illuminate\Http\Request;

class SetoresController extends ControllerListavel {
    private function setor_do_sistema($id) {
        return DB::table("log")
                    ->where("acao", "C")
                    ->where("origem", "SYS")
                    ->where("tabela", "setores")
                    ->where("fk", $id)
                    ->exists();
    }

    private function obter_permissao() {
        $permissao = Permissoes::find(
            DB::table("permissoes")
                ->where("id_usuario", Auth::user()->id)
                ->value("id")
        );
        return $permissao;
    }

    private function comparar_permissoes(Permissoes $permissao, Request $request) {
        return (
            $this->comparar_num($permissao->financeiro, $request->financeiro == "S" ? 1 : 0) || // App\Http\Controllers\Controller.php
            $this->comparar_num($permissao->atribuicoes, $request->atribuicoes == "S" ? 1 : 0) || // App\Http\Controllers\Controller.php
            $this->comparar_num($permissao->retiradas, $request->retiradas == "S" ? 1 : 0) || // App\Http\Controllers\Controller.php
            $this->comparar_num($permissao->pessoas, $request->pessoas == "S" ? 1 : 0) || // App\Http\Controllers\Controller.php
            $this->comparar_num($permissao->usuarios, $request->usuarios == "S" ? 1 : 0) || // App\Http\Controllers\Controller.php
            $this->comparar_num($permissao->solicitacoes, $request->solicitacoes == "S" ? 1 : 0) || // App\Http\Controllers\Controller.php
            $this->comparar_num($permissao->supervisor, $request->supervisor == "S" ? 1 : 0) // App\Http\Controllers\Controller.php
        );
    }

    private function consultar_main(Request $request) {
        $resultado = new \stdClass;

        if ($this->empresa_consultar($request)) { // App\Http\Controllers\Controller.php
            $resultado->msg = "Empresa não encontrada";
            $resultado->el = "setor-empresa";
            return $resultado;
        }

        if ((
            DB::table("pessoas")
                ->where("id_setor", $request->id)
                ->where("id_empresa", "<>", $request->id_empresa)
                ->where("lixeira", 0)
                ->exists()
        )) {
            $nome = Setores::find($id)->descr;
            $resultado->msg = "Não é possível alterar a empresa de ".$nome." porque existem pessoas vinculadas a esse setor";
            $resultado->el = "setor-empresa";
            return $resultado;
        }

        if (!$request->id &&
            DB::table("setores")
                ->where("lixeira", 0)
                ->where("descr", $request->descr)
                ->where("id_empresa", $request->id_empresa)
                ->exists()
        ) {
            $resultado->msg = "Já existe um centro de custo de mesmo nome nessa empresa";
            $resultado->el = "descr";
            return $resultado;
        }

        if ($this->setor_do_sistema($request->id)) {
            $setor = Setores::find($request->id);
            if (
                $this->comparar_permissoes($setor->permissao(), $request) ||
                $this->comparar_num($setor->cria_usuario, $request->cria_usuario == "S" ? 1 : 0) // App\Http\Controllers\Controller.php
            ) {
                $resultado->msg = "Não é possível alterar as configurações de um setor do sistema";
                $resultado->el = "";
                return $resultado;
            }
        }

        if ($this->comparar_permissoes($this->obter_permissao(), $request)) {
            $resultado->msg = "Não é possível atribuir ou retirar de um setor permissões que seu usuário não tem";
            $resultado->el = "";
            return $resultado;
        }

        $resultado->msg = "";
        $resultado->el = "";
        return $resultado;
    }

    private function aviso_main($id) {
        $resultado = new \stdClass;
        $resultado->permitir = 0;
        $setor = Setores::find($id);
        $nome = $setor->descr;
        if ($this->setor_do_sistema($id)) {
            $resultado->aviso = "Não é possível excluir um setor do sistema";
        } else if ($setor->pessoas()->exists()) {
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem pessoas vinculadas a esse setor";
        } else {
            $resultado->permitir = 1;
            $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
        }
        return $resultado;
    }

    protected function busca($param) {
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

    public function consultar(Request $request) {
        return json_encode($this->consultar_main($request));
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function mostrar($id) {
        return json_encode(
            DB::table("setores")
                ->select(
                    "setores.descr",
                    "setores.cria_usuario",
                    "permissoes.*"
                )
                ->join("permissoes", "permissoes.id_setor", "setores.id")
                ->where("setores.id", $id)
                ->first()
        );
    }

    public function salvar(Request $request) {
        if (!trim($request->descr)) return 400;
        if ($this->consultar_main($request)->msg) return 401;
        $cria_usuario = $request->cria_usuario == "S" ? 1 : 0;
        $linha = Setores::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            $adm_ant = intval($linha->cria_usuario);
            if (
                $adm_ant == $cria_usuario &&
                $linha->id_empresa == $request->id_empresa &&
                !$this->comparar_permissoes($linha->permissao(), $request) && 
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
                                    ->pluck("id");
                    foreach($consulta as $usuario) {
                        $permissao = DB::table("permissoes")
                                        ->where("id_usuario", $usuario)
                                        ->value("id");
                        array_push($lista, $usuario);
                        array_push($permissoes, $permissao);
                        $this->log_inserir("D", "users", $usuario); // App\Http\Controllers\Controller.php
                        $this->log_inserir("D", "permissoes", $permissao); // App\Http\Controllers\Controller.php
                    }
                    $lista = join(",", $lista);
                    if ($lista) {
                        if (isset($request->id_pessoa)) {
                            for ($i = 0; $i < sizeof($request->id_pessoa); $i++) {
                                $modelo = Pessoas::find($request->id_pessoa[$i]);
                                $modelo->senha = $request->password[$i];
                                $modelo->save();
                                $this->log_inserir("E", "pessoas", $modelo->id); // App\Http\Controllers\Controller.php
                            }
                        }
                        DB::statement("DELETE FROM users WHERE id IN (".$lista.")");
                        DB::statement("DELETE FROM permissoes WHERE id IN (".$permissoes.")");
                    }
                } else if (isset($request->id_pessoa)) {
                    for ($i = 0; $i < sizeof($request->id_pessoa); $i++) {
                        $senha = Hash::make($request->password[$i]);
                        DB::statement("INSERT INTO users (name, email, password, id_pessoa) VALUES ('".trim($request->nome[$i])."', '".trim($request->email[$i])."', '".$senha."', ".$request->id_pessoa[$i].")");
                        $this->log_inserir("C", "users", DB::table("users")
                                                            ->selectRaw("MAX(id) AS id")
                                                            ->value("id")
                        ); // App\Http\Controllers\Controller.php
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