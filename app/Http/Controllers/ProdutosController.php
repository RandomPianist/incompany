<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Produtos;
use App\Models\Maquinas;
use App\Models\Categorias;
use App\Models\Atribuicoes;

class ProdutosController extends ControllerListavel {
    private function busca_maq($where, $id_produto) {
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
                    ->whereRaw($where)
                    ->where("maquinas.lixeira", 0)
                    ->orderby("cp.lixeira")
                    ->take(20)
                    ->get();
    }

    private function consultar_maquina_main($id_produto, $maquinas_id, $maquinas_descr, $precos, $maximos) {
        $texto = "";
        $campos = array();
        $valores = array();

        $prmin = floatval(
            DB::table("produtos")
                ->selectRaw("IFNULL(prmin, 0) AS prmin")
                ->where("id", $id_produto)
                ->value("prmin")
        );

        for ($i = 0; $i < sizeof($maquinas_id); $i++) {
            $comodato = $this->obter_comodato($maquinas_id[$i]); // App\Http\Controllers\Controller.php

            if (
                !Maquinas::where("id", $maquinas_id[$i])
                        ->where("descr", $maquinas_descr[$i])
                        ->where("lixeira", 0)
                        ->exists()
            ) {
                array_push($campos, "maquina-".($i + 1));
                array_push($valores, $maquinas_descr[$i]);
                $texto = !$texto ? "Máquinas não encontradas" : "Máquina não encontrada";
            }
        }

        if (!$texto) {
            for ($i = 0; $i < sizeof($maquinas_id); $i++) {
                $saldo = $this->retorna_saldo_cp($this->obter_comodato($maquinas_id[$i])->id, $id_produto);
                if (
                    floatval($maximos[$i]) &&
                    floatval($maximos[$i]) < $saldo
                ) {
                    array_push($campos, "max-".($i + 1));
                    array_push($valores, $saldo);
                    $texto = 
                        $texto ?
                            "Esse valor de estoque máximo é inferior ao saldo atual do produto.<br>O campo foi corrigido."
                        :
                            "Esses valores de estoque máximo são inferiores ao saldo atual dos produtos.<br>Os campos foram corrigidos."
                    ;
                }
            }
        }

        if (!$texto) {
            for ($i = 0; $i < sizeof($maquinas_id); $i++) {
                if (!ceil($precos[$i])) {
                    $texto = $texto ? "Há preços zerados" : "Há um preço zerado";
                    array_push($campos, "preco-".($i + 1));
                    array_push($valores, "0");
                }
            }
        }

        if (!$texto) {
            for ($i = 0; $i < sizeof($maquinas_id); $i++) {
                $preco = floatval($precos[$i]);
                if ($prmin > 0 && $preco < $prmin) {
                    $texto = $texto ? "Há itens com preço abaixo do mínimo.<br>Os campos foram corrigidos" : "Há um item com um preço abaixo do mínimo.<br>O campo foi corrigido";
                    $texto .= " para o preço mínimo.<br>Por favor, verifique e tente novamente.";
                    array_push($campos, "preco-".($i + 1));
                    array_push($valores, $prmin);
                }
            }
        }

        $resultado = new \stdClass;
        $resultado->texto = $texto;
        $resultado->campos = $campos;
        $resultado->valores = $valores;

        return $resultado;
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
                            DB::raw("IFNULL(categorias.descr, 'A CLASSIFICAR') AS categoria"),
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

    protected function busca($where, $tipo = "") {
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
                    ->whereRaw(str_replace("?", "produtos.descr", $param))
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
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php
        if ($this->verifica_vazios($request, ["cod_externo", "descr", "validade", "categoria"])) return 400; // App\Http\Controllers\Controller.php
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
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php
        if (!$this->aviso_main($request->id)->permitir) return 401;
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

    public function listar_maquina(Request $request) {
        $filtro = trim($request->filtro);
        if ($filtro) {
            $busca = $this->busca_maq("maquinas.descr LIKE '".$filtro."%'", $request->id_produto);
            if (sizeof($busca) < 3) $busca = $this->busca_maq("maquinas.descr LIKE '%".$filtro."%'", $request->id_produto);
            if (sizeof($busca) < 3) $busca = $this->busca_maq("(maquinas.descr LIKE '%".implode("%' AND maquinas.descr LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')", $request->id_produto);
        } else $busca = $this->busca_maq("1", $request->id_produto);
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
        return json_encode($this->consultar_maquina_main(
            $request->id_produto,
            explode("|!|", $request->maquinas_id),
            explode("|!|", $request->maquinas_descr),
            explode("|!|", $request->precos),
            explode("|!|", $request->maximos)
        ));
    }

    public function maquina(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php

        if ($this->consultar_maquina_main(
            $request->id_produto,
            $request->id_maquina,
            $request->maquina,
            $request->preco,
            $request->maximo
        )->texto) return 401;
        
        $maquinas_atualizar = array();
        for ($i = 0; $i < sizeof($request->id_maquina); $i++) {
            $comodato = $this->obter_comodato($request->id_maquina[$i]); // App\Http\Controllers\Controller.php

            $modelo = null;
            $letra_log = "";
            $id_cp = $comodato->cp($request->id_produto)->value("id");
            if ($id_cp !== null) {
                $modelo = ComodatosProdutos::find($id_cp);
                $lixeira = str_replace("opt-", "", $request->lixeira[$i]);
                if (
                    $this->comparar_num($modelo->lixeira, $lixeira) ||
                    $this->comparar_num($modelo->preco, $request->preco[$i]) ||
                    $this->comparar_num($modelo->maximo, $request->maximo[$i]) ||
                    $this->comparar_num($modelo->minimo, $request->minimo[$i])
                ) $letra_log = "E";
            } else {
                $modelo = new ComodatosProdutos;
                $letra_log = "C";
            }
            if ($letra_log) {
                $modelo->id_comodato = $comodato->id;
                $modelo->id_produto = $request->id_produto;
                $modelo->preco = $request->preco[$i];
                $modelo->maximo = $request->maximo[$i];
                $modelo->minimo = $request->minimo[$i];
                $modelo->lixeira = $lixeira;
                $modelo->save();
                $this->log_inserir($letra_log, "comodatos_produtos", $modelo->id);
            }
            if ($this->gerar_atribuicoes($comodato)) array_push($maquinas_atualizar, $request->id_maquina[$i]); // App\Http\Controllers\Controller.php
        }
        if (sizeof($maquinas_atualizar)) $this->atualizar_tudo($maquinas_atualizar, "M", true); // App\Http\Controllers\Controller.php
        return redirect("/maquinas");
    }
}