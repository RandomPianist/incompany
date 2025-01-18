<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Setores;

class SetoresController extends ControllerKX {
    private function busca($param) {
        return DB::table("setores")
                    ->select(
                        "id",
                        "descr"
                    )
                    ->whereRaw($param)
                    ->where("lixeira", 0)
                    ->get();
    }

    public function ver() {
        $ultima_atualizacao = $this->log_consultar("setores");
        return view("setores", compact("ultima_atualizacao"));
    }

    public function listar(Request $request) {
        $busca = null;
        $resultado = new \stdClass;
        $filtro = trim($request->filtro);
        if ($filtro) {
            $busca = $this->busca("descr LIKE '".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("descr LIKE '%".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("(descr LIKE '%".implode("%' AND descr LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')");
        } else $busca = $this->busca("1");
        $resultado->consulta = $busca;
        $resultado->empresa = Pessoas::find(Auth::user()->id_pessoa)->id_empresa;
        return json_encode($resultado);
    }

    public function consultar(Request $request) {
        if (sizeof(
            DB::table("setores")
                ->where("lixeira", 0)
                ->where("descr", $request->descr)
                ->get()
        )) return "1";
        return "0";
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
        return json_encode($resultado);
    }

    public function salvar(Request $request) {
        $cria_usuario = $request->cria_usuario == "S" ? 1 : 0;
        $linha = Setores::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            $adm_ant = intval($linha->cria_usuario);
            if ($adm_ant != $cria_usuario) {
                if ($adm_ant) {
                    $lista = array();
                    $consulta = DB::table("users")
                                    ->join("pessoas", "pessoas.id", "users.id_pessoa")
                                    ->where("id_setor", $request->id)
                                    ->pluck("users.id");
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
        $linha->cria_usuario = $cria_usuario;
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "setores", $linha->id);
        return redirect("/setores");
    }

    public function excluir(Request $request) {
        $linha = Setores::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "setores", $linha->id);
    }

    public function primeiro_admin() {
        return json_encode(
            DB::table("setores")
                ->where("lixeira", 0)
                ->where("cria_usuario", 1)
                ->first()
        );
    }
}