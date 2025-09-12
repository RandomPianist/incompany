<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Produtos;
use App\Models\Atribuicoes;

class ProdutosController extends Controller {
    private function busca($where) {
        return DB::table("produtos")
                    ->select(
                        DB::raw("produtos.*"),
                        DB::raw("
                            CASE
                                WHEN (IFNULL(categorias.descr, '') = '') THEN 'A CLASSIFICAR'
                                ELSE categorias.descr
                            END AS categoria
                        ")
                    )
                    ->leftjoin("categorias", "categorias.id", "produtos.id_categoria")
                    ->whereRaw($where)
                    ->where("produtos.lixeira", 0)
                    ->get();
    }

    public function ver() {
        return view("produtos");
    }

    public function listar(Request $request) {
        $filtro = trim($request->filtro);
        if ($filtro) {
            $busca = $this->busca("produtos.descr LIKE '".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("produtos.descr LIKE '%".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("(produtos.descr LIKE '%".implode("%' AND produtos.descr LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')");
        } else $busca = $this->busca("1");
        foreach($busca as $linha) $linha->foto = asset("storage/".$linha->foto);
        return json_encode($busca);
    }

    public function consultar(Request $request) {
        if (!sizeof(
            DB::table("categorias")
                ->where("id", $request->id_categoria)
                ->where("descr", $request->categoria)
                ->get()
        )) return "invalido";
        if (sizeof(
            DB::table("produtos")
                ->where("lixeira", 0)
                ->where("cod_externo", $request->cod_externo)
                ->get()
        ) && !$request->id) return "duplicado";
        if ($request->id) {
            $prmin = floatval(
                DB::table("produtos")
                    ->selectRaw("IFNULL(prmin, 0) AS prmin")
                    ->where("id", $request->id)
                    ->value("prmin")
            );
            $preco = floatval($request->preco);
            if ($prmin > 0 && $preco < $prmin) return "preco".strval($prmin);
        }
        if (sizeof(
            DB::table("atribuicoes")
                ->where("referencia", Produtos::find($request->id)->referencia)
                ->get()
        ) && !trim($request->referencia)) return "aviso";
        return "";
    }

    public function mostrar($id) {
        $produto = DB::table("produtos")
                        ->select(
                            DB::raw("produtos.*"),
                            DB::raw("IFNULL(categorias.descr, 'A CLASSIFICAR') AS categoria"),
                            DB::raw("IFNULL(produtos.consumo, 0) AS e_consumo"),
                            DB::raw("DATE_FORMAT(produtos.validade_ca, '%d/%m/%Y') AS validade_ca_fmt")
                        )
                        ->leftjoin("categorias", "categorias.id", "produtos.id_categoria")
                        ->where("produtos.id", $id)
                        ->first();
        if ($produto->foto == null) $produto->foto = "";
        else if (stripos($produto->foto, "//") === false) $produto->foto = asset("storage/".$produto->foto);
        return json_encode($produto);
    }

    public function aviso($id) {
        $resultado = new \stdClass;
        $nome = Produtos::find($id)->descr;
        $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
        $resultado->permitir = 1;
        return json_encode($resultado);
    }

    public function validade(Request $request) {
        return DB::table("produtos")
                ->selectRaw($request->tipo == "P" ? "validade" : "MAX(validade) AS validade")
                ->whereRaw(
                    $request->tipo == "P" ? "id = ".$request->id : "referencia IN (
                        SELECT referencia
                        FROM produtos
                        WHERE id = ".$request->id."
                          AND lixeira = 0
                    )"
                )
                ->value("validade");
    }

    public function salvar(Request $request) {
        if ($this->verifica_vazios($request, ["cod_externo", "descr", "ca", "validade", "categoria", "tamanho", "validade_ca"])) return 400; // App\Http\Controllers\Controller.php
        $validade_ca = Carbon::createFromFormat('d/m/Y', $request->validade_ca)->format('Y-m-d');
        if ($this->consultar($request)) return 401;
        $linha = Produtos::firstOrNew(["id" => $request->id]);
        if (
            $request->id &&
            !$request->file("foto") &&
            $validade_ca == strval($linha->validade_ca) &&
            !$this->comparar_texto($request->descr, $linha->descr) && // App\Http\Controllers\Controller.php
            !$this->comparar_texto($request->tamanho, $linha->tamanho) && // App\Http\Controllers\Controller.php
            !$this->comparar_texto($request->detalhes, $linha->detalhes) && // App\Http\Controllers\Controller.php
            !$this->comparar_texto($request->referencia, $linha->referencia) && // App\Http\Controllers\Controller.php
            !$this->comparar_num($request->ca, $linha->ca) && // App\Http\Controllers\Controller.php
            !$this->comparar_num($request->preco, $linha->preco) && // App\Http\Controllers\Controller.php
            !$this->comparar_num($request->consumo, $linha->consumo) && // App\Http\Controllers\Controller.php
            !$this->comparar_num($request->validade, $linha->validade) && // App\Http\Controllers\Controller.php
            !$this->comparar_num($request->id_categoria, $linha->id_categoria) // App\Http\Controllers\Controller.php
        ) return 400;
        $this->atribuicao_atualiza_ref($request->id, $linha->referencia, $request->referencia); // App\Http\Controllers\Controller.php
        $linha->descr = mb_strtoupper($request->descr);
        $linha->preco = $request->preco;
        $linha->validade = $request->validade;
        $linha->ca = $request->ca;
        $linha->cod_externo = $request->cod_externo;
        $linha->id_categoria = $request->id_categoria;
        $linha->referencia = $request->referencia;
        $linha->tamanho = $request->tamanho;
        $linha->detalhes = $request->detalhes;
        $linha->consumo = $request->consumo;
        $linha->validade_ca = Carbon::createFromFormat('d/m/Y', $request->validade_ca)->format('Y-m-d');
        if ($request->file("foto")) $linha->foto = $request->file("foto")->store("uploads", "public");
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "produtos", $linha->id); // App\Http\Controllers\Controller.php
        return redirect("/produtos");
    }

    public function excluir(Request $request) {
        $linha = Produtos::find($request->id);
        $ant = DB::table("vatbold")
                    ->select(
                        "psm_chave",
                        "psm_valor"
                    )
                    ->join("produtos", function($join) {
                        $join->on("produtos.cod_externo", "vatbold.cod_produto")
                            ->orOn("produtos.referencia", "vatbold.referencia");
                    })
                    ->where("produtos.id", $linha->id)
                    ->groupby(
                        "psm_chave",
                        "psm_valor"
                    )
                    ->get();
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "produtos", $linha->id); // App\Http\Controllers\Controller.php
        $this->atualizar_atribuicoes($ant); // App\Http\Controllers\Controller.php
    }
}