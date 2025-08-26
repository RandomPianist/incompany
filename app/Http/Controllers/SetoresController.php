<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Setores;

class SetoresController extends Controller {
    private function busca($param) {
        return DB::table("setores")
                    ->select(
                        "setores.id",
                        "setores.descr",
                        "empresas.nome_fantasia AS empresa"
                    )
                    ->leftjoin("empresas", "setores.id_empresa", "empresas.id")
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "setores"))
                    ->whereRaw($param)
                    ->get();
    }

    private function consultar_main(Request $request) {
        $resultado = new \stdClass;

        if ($this->empresa_consultar($request)) {
            $resultado->msg = "Empresa não encontrada";
            $resultado->el = "setor-empresa";
            return $resultado;
        }

        if (sizeof(
            DB::table("setores")
                ->where("lixeira", 0)
                ->where("descr", $request->descr)
                ->where("id_empresa", $request->id_empresa)
                ->get()
        ) && !$request->id) {
            $resultado->msg = "Já existe um centro de custo de mesmo nome nessa empresa";
            $resultado->el = "descr";
            return $resultado;
        }

        $resultado->msg = "";
        $resultado->el = "";
        return $resultado;
    }

    private function aviso_main($id) {
        $resultado = new \stdClass;
        $nome = Setores::find($id)->descr;
        if (sizeof(
            DB::table("pessoas")
                ->where("id_setor", $id)
                ->where("lixeira", 0)
                ->get()
        )) {
            $resultado->permitir = 0;
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem pessoas vinculadas a esse setor";
        } else {
            $resultado->permitir = 1;
            $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
        }
        return $resultado;
    }

    public function ver() {
        $ultima_atualizacao = $this->log_consultar("setores");
        return view("setores", compact("ultima_atualizacao"));
    }

    public function listar(Request $request) {
        $busca = null;
        $filtro = $request->filtro;
        if ($filtro) {
            $busca = $this->busca("descr LIKE '".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("descr LIKE '%".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("(descr LIKE '%".implode("%' AND descr LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')");
        } else $busca = $this->busca("1");
        return json_encode($busca);
    }

    public function consultar(Request $request) {
        return json_encode($this->consultar_main($request));
    }

    public function usuarios($id) {
        $resultado = new \stdClass;
        $resultado->consulta = DB::table("pessoas")
                                    ->select(
                                        "pessoas.id",
                                        "pessoas.nome"
                                    )
                                    ->join("users", "users.id_pessoa", "pessoas.id")
                                    ->where("pessoas.id_setor", $id)
                                    ->where("pessoas.lixeira", 0)
                                    ->get();
        $resultado->bloquear = Pessoas::find(Auth::user()->id_pessoa)->id_setor == $id ? "1" : "0";
        return json_encode($resultado);
    }

    public function pessoas($id) {
        return DB::table("pessoas")
                    ->select(
                        "pessoas.id",
                        "pessoas.nome"
                    )
                    ->leftjoin("users", "users.id_pessoa", "pessoas.id")
                    ->where("pessoas.id_setor", $id)
                    ->where("pessoas.lixeira", 0)
                    ->whereNull("users.id")
                    ->get();
    }

    public function mostrar($id) {
        return json_encode($this->setor_mostrar($id));
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function permissao() {
        return !intval(
            DB::table("pessoas")
                ->selectRaw("IFNULL(id_usuario, 0) AS id_usuario")
                ->where("id", Auth::user()->id_pessoa)
                ->value("id_usuario")
        ) && !intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa) ? 1 : 0;
    }

    public function salvar(Request $request) {
        if (!trim($request->descr)) return 400;
        if ($this->consultar_main($request)->msg) return 401;
        $cria_usuario = $request->cria_usuario == "S" ? 1 : 0;
        $linha = Setores::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            if (!$this->permissao()) return 401;
            $adm_ant = intval($linha->cria_usuario);
            if (
                $adm_ant == $cria_usuario &&
                $linha->id_empresa == $request->id_empresa &&
                !$this->comparar_texto($request->descr, $linha->descr)
            ) return 400;
            if ($adm_ant != $cria_usuario) {
                if ($adm_ant) {
                    $lista = array();
                    $consulta = DB::table("users")
                                    ->select("users.id")
                                    ->join("pessoas", "pessoas.id", "users.id_pessoa")
                                    ->where("id_setor", $request->id)
                                    ->pluck("id");
                    foreach($consulta as $usuario) {
                        array_push($lista, $usuario);
                        $this->log_inserir("D", "users", $usuario);
                    }
                    $lista = join(",", $lista);
                    if ($lista) {
                        if (isset($request->id_pessoa)) {
                            for ($i = 0; $i < sizeof($request->id_pessoa); $i++) {
                                $modelo = Pessoas::find($request->id_pessoa[$i]);
                                $modelo->senha = $request->password[$i];
                                $modelo->save();
                                $this->log_inserir("E", "pessoas", $modelo->id);
                            }
                        }
                        DB::statement("DELETE FROM users WHERE id IN (".$lista.")");
                    }
                } else if (isset($request->id_pessoa)) {
                    for ($i = 0; $i < sizeof($request->id_pessoa); $i++) {
                        $senha = Hash::make($request->password[$i]);
                        DB::statement("INSERT INTO users (name, email, password, id_pessoa) VALUES ('".trim($request->nome[$i])."', '".trim($request->email[$i])."', '".$senha."', ".$request->id_pessoa[$i].")");
                        $this->log_inserir("C", "users", DB::table("users")
                                                            ->selectRaw("MAX(id) AS id")
                                                            ->value("id")
                        );
                    }
                }
            }
        }
        $linha->descr = mb_strtoupper($request->descr);
        $linha->id_empresa = $request->id_empresa;
        $linha->cria_usuario = $cria_usuario;
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "setores", $linha->id);
        return redirect("/setores");
    }

    public function excluir(Request $request) {
        if (!$this->aviso_main($request->id)->permitir) return 401;
        $linha = Setores::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "setores", $linha->id);
    }

    public function primeiro_admin() {
        return json_encode(
            DB::table("setores")
                ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "setores"))
                ->where("cria_usuario", 1)
                ->first()
        );
    }
}