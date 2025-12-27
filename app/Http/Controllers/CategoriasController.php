<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Categorias;

class CategoriasController extends ControllerListavel {
    private function aviso_main($id) {
        $resultado = $this->pode_abrir_main("categorias", $id, "excluir"); // App\Http\Controllers\Controller.php
        if (!$resultado->permitir) return $resultado;
        $resultado = new \stdClass;
        $categoria = Categorias::find($id);
        $nome = "<b>".$categoria->descr."</b>";
        $resultado->permitir = 1;
        $resultado->aviso = $categoria->produtos()->exists() ? 
            "Não é recomendado excluir ".$nome." porque existem produtos vinculados a essa categoria.<br>Deseja prosseguir assim mesmo?"
        :
            "Tem certeza que deseja excluir ".$nome."?"
        ;
        return $resultado;
    }

    protected function busca($param, $bindings = [], $tipo = "") {
        $param = str_replace("?", "categorias.descr", $param);
        $param = str_replace("!", "categorias.descr", $param);
        return DB::table("categorias")
                    ->select(
                        "id",
                        "descr"
                    )
                    ->whereRaw($param, $bindings)
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
        return $this->alterar_usuario_editando("categorias", $id)->descr; // App\Http\Controllers\Controller.php
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function salvar(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Traits\GlobaisTrait.php
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
        $id = intval($id);
        if ($this->obter_empresa()) return 401; // App\Http\Traits\GlobaisTrait.php
        if (!$this->aviso_main($id)->permitir) return 401;
        $linha = Categorias::find($id);
        $linha->lixeira = 1;
        $linha->save();
        $where = "id_categoria = ".$id;
        $this->log_inserir("D", "categorias", $id); // App\Http\Controllers\Controller.php
        $this->log_inserir_lote("E", "produtos", $where); // App\Http\Controllers\Controller.php
        DB::statement("UPDATE produtos SET id_categoria = NULL WHERE ".$where);
    }
}