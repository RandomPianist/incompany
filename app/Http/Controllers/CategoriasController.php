<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Categorias;

class CategoriasController extends ControllerListavel {
    private function aviso_main($id) {
        $resultado = new \stdClass;
        $categoria = Categorias::find($id);
        $nome = "<b>".$categoria->descr."</b>";
        if (!intval($categoria->id_usuario_editando)) {
            $resultado->permitir = 1;
            $resultado->aviso = $categoria->produtos()->exists() ? 
                "Não é recomendado excluir ".$nome." porque existem produtos vinculados a essa categoria.<br>Deseja prosseguir assim mesmo?"
            :
                "Tem certeza que deseja excluir ".$nome."?"
            ;
        } else {
            $resultado->permitir = 0;
            $resultado->aviso = "Não é possível excluir ".$nome." porque essa categoria está sendo editada por ".$this->obter_nome_usuario($categoria->id_usuario_editando); // App\Http\Controllers\Controller.php
        }
        return $resultado;
    }

    protected function busca($param, $tipo = "") {
        return DB::table("categorias")
                    ->select(
                        "id",
                        "descr"
                    )
                    ->whereRaw(str_replace("?", "categorias.descr", $param))
                    ->where("lixeira", 0)
                    ->get();
    }

    public function ver() {
        return view("categorias");
    }

    public function consultar(Request $request) {
        return (!$request->id && Categorias::where("lixeira", 0)->where("descr", $request->descr)->exists()) ? "1" : "0";
    }

    public function mostrar($id) {
        $categoria = Categorias::find($id);
        $categoria->id_usuario_editando = Auth::user()->id;
        $categoria->save();
        return $categoria->descr;
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function salvar(Request $request) {
        if (!trim($request->descr)) return 400;
        if (intval($this->consultar($request))) return 401;
        $linha = Categorias::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            if (!$this->comparar_texto($request->descr, $linha->descr)) return 400; // App\Http\Controllers\Controller.php
        }
        $linha->descr = mb_strtoupper($request->descr);
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "categorias", $linha->id); // App\Http\Controllers\Controller.php
        return redirect("/categorias");
    }

    public function excluir(Request $request) {
        if (!intval($this->aviso_main($id)->permitir)) return 401;
        $linha = Categorias::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $where = "id_categoria = ".$request->id;
        $this->log_inserir("D", "categorias", $linha->id); // App\Http\Controllers\Controller.php
        $this->log_inserir_lote("E", "produtos", $where); // App\Http\Controllers\Controller.php
        DB::statement("UPDATE produtos SET id_categoria = NULL ".$where);
    }
}