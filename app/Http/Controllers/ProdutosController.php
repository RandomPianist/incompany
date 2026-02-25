<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Produtos;
use App\Models\Categorias;

class ProdutosController extends ControllerListavel {
    private function busca_maq($where, $bindings, $id_produto) {
        return DB::table("comodatos_produtos AS cp")
                    ->select(
                        "comodatos.id_maquina",
                        "maquinas.descr AS maquina",
                        "cp.lixeira",
                        "cp.preco",
                        DB::raw("IFNULL(cp.minimo, 0) AS minimo"),
                        DB::raw("IFNULL(cp.maximo, 0) AS maximo")
                    )
                    ->join("comodatos", "comodatos.id", "cp.id_comodato")
                    ->join("maquinas", "maquinas.id", "comodatos.id_maquina")
                    ->where("cp.id_produto", $id_produto)
                    ->whereRaw("CURDATE() >= comodatos.inicio AND CURDATE() < comodatos.fim")
                    ->whereRaw($where, $bindings)
                    ->where("maquinas.lixeira", 0)
                    ->orderby("cp.lixeira")
                    ->take(20)
                    ->get();
    }

    private function aviso_main($id) {
        $resultado = $this->pode_abrir_main("produtos", $id, "excluir"); // App\Http\Controllers\Controller.php
        if (!$resultado->permitir) return $resultado;
        $resultado = new \stdClass;
        $nome = "<b>".Produtos::find($id)->descr."</b>";
        $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
        $resultado->permitir = 1;
        return $resultado;
    }

    private function mostrar_main($id) {
        $produto = DB::table("produtos")
                        ->select(
                            DB::raw("produtos.*"),
                                DB::raw("
                                CASE
                                    WHEN ((IFNULL(categorias.descr, '') = '') OR (IFNULL(categorias.lixeira, 1) = 1)) THEN 'A CLASSIFICAR'
                                    ELSE categorias.descr
                                END AS categoria
                            "),
                            DB::raw("IFNULL(produtos.consumo, 0) AS e_consumo"),
                            DB::raw("DATE_FORMAT(produtos.validade_ca, '%d/%m/%Y') AS validade_ca_fmt")
                        )
                        ->leftjoin("categorias", "categorias.id", "produtos.id_categoria")
                        ->where("produtos.id", $id)
                        ->first();
        if ($produto->foto == null) $produto->foto = "";
        elseif (strpos($produto->foto, "//") === false) $produto->foto = asset("storage/".$produto->foto);
        return json_encode($produto);
    }

    protected function busca($where, $bindings = [], $tipo = "") {
        $where = str_replace("#", "produtos.descr", $where);
        $where = str_replace("!", "produtos.cod_externo", $where);
        return DB::table("produtos")
                    ->select(
                        DB::raw("produtos.*"),
                        DB::raw("
                            CASE
                                WHEN ((IFNULL(categorias.descr, '') = '') OR (IFNULL(categorias.lixeira, 1) = 1)) THEN 'A CLASSIFICAR'
                                ELSE categorias.descr
                            END AS categoria
                        ")
                    )
                    ->leftjoin("categorias", "categorias.id", "produtos.id_categoria")
                    ->whereRaw($where, $bindings)
                    ->where("produtos.lixeira", 0)
                    ->get();
    }

    public function ver() {
        return view("produtos");
    }

    public function consultar(Request $request) {
        if (
            !Categorias::where("id", $request->id_categoria)
                        ->where("descr", $request->categoria)
                        ->exists()
        ) return "invalido";
        if (!$request->id &&
            Produtos::where("lixeira", 0)
                    ->where("cod_externo", $request->cod_externo)
                    ->exists()
        ) return "duplicado";
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
        $produto = Produtos::find($request->id);
        if ($produto !== null) {
            if (
                !trim($request->referencia) &&
                $produto->atribuicoes_por_referencia()->exists()
            ) return "aviso";
        }
        return "";
    }

    public function mostrar($id) {
        $this->alterar_usuario_editando("produtos", $id); // App\Http\Controllers\Controller.php
        return $this->mostrar_main($id);
    }

    public function mostrar2($id) {
        return $this->mostrar_main($id);
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
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
        if ($this->obter_empresa()) return 401; // App\Http\Traits\GlobaisTrait.php
        if ($this->verifica_vazios($request, ["cod_externo", "descr", "validade", "categoria"])) return 400; // App\Http\Controllers\Controller.php
        $validade_ca = $request->validade_ca ? Carbon::createFromFormat('d/m/Y', $request->validade_ca)->format('Y-m-d') : null;
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
        $linha->validade_ca = $validade_ca;
        if ($request->file("foto")) $linha->foto = $request->file("foto")->store("uploads", "public");
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "produtos", $linha->id); // App\Http\Controllers\Controller.php
        return redirect("/produtos");
    }

    public function excluir(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Traits\GlobaisTrait.php
        if (!$this->aviso_main($request->id)->permitir) return 401;
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            $linha = Produtos::find($request->id);
            $linha->lixeira = 1;
            $linha->save();
            $this->log_inserir("D", "produtos", $linha->id); // App\Http\Controllers\Controller.php
            $this->atualizar_atribuicoes(); // App\Http\Controllers\Controller.php

            $where = "id_produto = ".$linha->id;
            $this->log_inserir_lote("D", "comodatos_produtos", $where); // App\Http\Controllers\Controller.php
            DB::statement("UPDATE comodatos_produtos SET lixeira = 1 WHERE ".$where);

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    public function listar_maquina(Request $request) {
        $filtro = trim($request->filtro);
        $filtro_limpo = str_replace("  ", " ", $filtro);

        if ($filtro) {
            $busca = $this->busca_maq(
                "maquinas.descr LIKE ?", 
                [$filtro_limpo . '%'], 
                $request->id_produto
            );

            if (count($busca) < 3) {
                $busca = $this->busca_maq(
                    "maquinas.descr LIKE ?", 
                    ['%' . $filtro_limpo . '%'], 
                    $request->id_produto
                );
            }

            if (count($busca) < 3) {
                $palavras = explode(" ", $filtro_limpo);
                $sql_parts = [];
                $bindings = [];
                foreach ($palavras as $palavra) {
                    if (trim($palavra) !== '') {
                        $sql_parts[] = "maquinas.descr LIKE ?";
                        $bindings[] = '%' . $palavra . '%';
                    }
                }
                if (count($sql_parts) > 0) {
                    $sql_final = "(" . implode(" AND ", $sql_parts) . ")";
                    $busca = $this->busca_maq(
                        $sql_final, 
                        $bindings, 
                        $request->id_produto
                    );
                }
            }
        } else {
            $busca = $this->busca_maq("1 = 1", [], $request->id_produto);
        }
        $resultado = new \stdClass;
        $resultado->lista = $busca;
        $resultado->total = DB::table("comodatos_produtos AS cp")
                                ->selectRaw("COUNT(cp.id) AS total")
                                ->join("comodatos", "comodatos.id", "cp.id_comodato")
                                ->whereRaw("CURDATE() >= comodatos.inicio AND CURDATE() < comodatos.fim")
                                ->where("id_produto", $request->id_produto)
                                ->value("total");
        return json_encode($resultado);
    }

    public function consultar_maquina(Request $request) {
        return json_encode($this->consultar_comodatos_produtos( // App\Http\Controllers\Controller.php
            "produto",
            $request->id_produto,
            explode("|!|", $request->maquinas_id),
            explode("|!|", $request->maquinas_descr),
            explode("|!|", $request->precos),
            explode("|!|", $request->maximos)
        ));
    }

    public function maquina(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Traits\GlobaisTrait.php

        if ($this->consultar_comodatos_produtos( // App\Http\Controllers\Controller.php
            "produto",
            $request->id_produto,
            $request->id_maquina,
            $request->maquina,
            $request->preco,
            $request->maximo
        )->texto) return 401;
        
        $salvamento = $this->salvar_comodatos_produtos("produto", $request); // App\Http\Controllers\Controller.php
        if ($salvamento == "/produtos") return redirect($salvamento);
        return $this->view_mensagem("error", $salvamento); // App\Http\Controllers\Controller.php
    }
}