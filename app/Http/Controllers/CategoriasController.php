<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Categorias;

class CategoriasController extends ControllerListavel {
    protected function busca($where) {
        return DB::table("categorias")
                    ->select(
                        "id",
                        "descr"
                    )
                    ->whereRaw($where)
                    ->where("lixeira", 0)
                    ->get();
    }

    public function ver() {
        return view("categorias");
    }

    public function consultar(Request $request) {
        if (!$request->id &&
            DB::table("categorias")
                ->where("lixeira", 0)
                ->where("descr", $request->descr)
                ->exists()
        ) return "1";
        return "0";
    }

    public function mostrar($id) {
        return Categorias::find($id)->descr;
    }

    public function aviso($id) {
        $resultado = new \stdClass;
        $nome = Categorias::find($id)->descr;
        $resultado->permitir = 1;
        $resultado->aviso = DB::table("produtos")
                                ->where("id_categoria", $id)
                                ->where("lixeira", 0)
                                ->exists()
        ? 
            "Não é recomendado excluir ".$nome." porque existem produtos vinculados a essa categoria.<br>Deseja prosseguir assim mesmo?"
        :
            "Tem certeza que deseja excluir ".$nome."?"
        ;
        return json_encode($resultado);
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
        $linha = Categorias::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $where = "id_categoria = ".$request->id;
        DB::statement("UPDATE produtos SET id_categoria = NULL ".$where);
        $this->log_inserir("D", "categorias", $linha->id); // App\Http\Controllers\Controller.php
        $this->log_inserir_lote("E", "produtos", $where); // App\Http\Controllers\Controller.php
    }
}