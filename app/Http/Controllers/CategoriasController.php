<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use App\Models\Categorias;

class CategoriasController extends Controller {
    private function busca($where) {
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

    public function listar(Request $request) {
        $filtro = trim($request->filtro);
        if ($filtro) {
            $busca = $this->busca("descr LIKE '".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("descr LIKE '%".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("(descr LIKE '%".implode("%' AND descr LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')");
        } else $busca = $this->busca("1");
        return json_encode($busca);
    }

    public function consultar(Request $request) {
        if (sizeof(
            DB::table("categorias")
                ->where("lixeira", 0)
                ->where("descr", $request->descr)
                ->get()
        ) && !$request->id) return "1";
        return "0";
    }

    public function mostrar($id) {
        return Categorias::find($id)->descr;
    }

    public function aviso($id) {
        $vinculo = sizeof(
            DB::table("produtos")
                ->where("id_categoria", $id)
                ->where("lixeira", 0)
                ->get()
        ) > 0;
        $resultado = new \stdClass;
        $nome = Categorias::find($id)->descr;
        $resultado->permitir = 1;
        $resultado->aviso = $vinculo ? "Não é recomendado excluir ".$nome." porque existem produtos vinculados a essa categoria.<br>Deseja prosseguir assim mesmo?" : "Tem certeza que deseja excluir ".$nome."?";
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