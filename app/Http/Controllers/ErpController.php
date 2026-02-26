<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use App\Models\Comodatos;
use App\Models\Categorias;
use App\Models\ComodatosProdutos;
use App\Models\Produtos;
use App\Models\Estoque;
use Illuminate\Http\Request;

class ErpController extends Controller {
    public function maquinas_listar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        
        return json_encode(
            DB::table("comodatos")
                ->select(
                    "comodatos.id AS id_comodato",
                    DB::raw("DATE_FORMAT(comodatos.inicio, '%d-%m-%Y') AS inicio"),
                    DB::raw("DATE_FORMAT(comodatos.fim, '%d-%m-%Y') AS fim"),
                    "empresas.id AS id_empresa",
                    "empresas.nome_fantasia AS descr_empresa",
                    "empresas.cod_externo AS emp_cod",
                    "maquinas.id AS id_maquina",
                    "maquinas.descr AS descr_maquina",
                    DB::raw("IFNULL(COUNT(DISTINCT cp.id), 0) AS associados"),
                    DB::raw("
                        CASE
                            WHEN (IFNULL(MAX(aux.critico), 0) = 1) THEN 'S'
                            ELSE 'N'
                        END AS critico
                    ")
                )
                ->join("empresas", "empresas.id", "comodatos.id_empresa")
                ->join("maquinas", "maquinas.id", "comodatos.id_maquina")
                ->leftjoin("comodatos_produtos AS cp", "cp.id_comodato", "comodatos.id")
                ->leftjoin("produtos", "produtos.id", "cp.id_produto")
                ->leftjoinSub(
                    DB::table("comodatos_produtos AS cp")
                        ->select(
                            "id_comodato",
                            DB::raw("
                                CASE
                                    WHEN (IFNULL(vestoque.qtd, 0) < IFNULL(cp.minimo, 0)) THEN 1
                                    ELSE 0
                                END AS critico
                            ")
                        )
                        ->join("produtos", "produtos.id", "cp.id_produto")
                        ->leftjoin("vestoque", "vestoque.id_cp", "cp.id")
                        ->whereRaw("IFNULL(produtos.cod_externo, '') <> ''")
                        ->where("cp.lixeira", 0)
                        ->where("produtos.lixeira", 0),
                    "aux", 
                    "aux.id_comodato",
                    "comodatos.id"
                )
                ->whereRaw("IFNULL(empresas.cod_externo, '') <> ''")
                ->whereRaw("CURDATE() >= comodatos.inicio")
                ->whereRaw("CURDATE() < comodatos.fim")
                ->where(function($sql) {
                    $sql->whereNull("cp.id")
                        ->orWhere("cp.lixeira", 0);
                })
                ->where(function($sql) {
                    $sql->whereNull("produtos.id")
                        ->orWhere("produtos.lixeira", 0);
                })
                ->where("maquinas.lixeira", 0)
                ->where("empresas.lixeira", 0)
                ->groupby(
                    "comodatos.id",
                    "comodatos.inicio",
                    "empresas.id",
                    "empresas.nome_fantasia",
                    "maquinas.id",
                    "maquinas.descr"
                )
                ->get()
        );
    }

    public function maquinas_consultar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        if (
            DB::table("maquinas")
                    ->join("comodatos", "comodatos.id_maquina", "maquinas.id")
                    ->whereIn(
                        "comodatos.id_empresa",
                        DB::table("empresas")
                            ->select("id")
                            ->where("cod_externo", $request->cft)
                            ->unionAll(
                                DB::table("empresas")
                                    ->select("filiais.id")
                                    ->join("empresas AS filiais", "filiais.id_matriz", "empresas.id")
                                    ->where("empresas.cod_externo", $request->cft)
                            )
                            ->pluck("id")
                            ->toArray()
                    )
                    ->where("maquinas.descr", $request->maq)
                    ->where("maquinas.lixeira", 0)
                    ->exists()
        ) return "CLIENTE";
        if (Maquinas::where("descr", $request->maq)->where("lixeira", 0)->exists()) return "MAQUINA";
        return "OK";
    }

    public function maquinas_salvar(Request $request) {
        if ($request->token != config("app.key")) return 401;

        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            $cnpj = filter_var($request->cnpj, FILTER_SANITIZE_NUMBER_INT);
            $id_empresa = Empresas::where("cnpj", $cnpj)->orWhere("cod_externo", $request->emp_cod)->value("id");
            $continua = false;
            $empresa = null;
            
            if ($id_empresa !== null) {
                $empresa = Empresas::find($id_empresa);
                if (intval($empresa->lixeira)) {
                    $connection->rollBack();
                    return "EMPRESA EXCLUIDA";
                }
                if ($this->comparar_texto($empresa->cnpj, $cnpj)) $continua = true; // App\Http\Controllers\Controller.php
                if ($this->comparar_texto($empresa->razao_social, $request->emp_razao)) $continua = true; // App\Http\Controllers\Controller.php
                if ($this->comparar_texto($empresa->nome_fantasia, $request->emp_fantasia)) $continua = true; // App\Http\Controllers\Controller.php
            } else {
                $empresa = new Empresas;
                $continua = true;
            }
            if ($continua) {
                $empresa->cnpj = $cnpj;
                $empresa->razao_social = $request->emp_razao;
                $empresa->nome_fantasia = $request->emp_fantasia;
                $empresa->cod_externo = $request->emp_cod;
                $empresa->save();
                $this->log_inserir($id_empresa !== null ? "E" : "C", "empresas", $empresa->id, "ERP", $request->usuario); // App\Http\Controllers\Controller.php
            }

            $id_maquina = Maquinas::find($request->id_maquina)->value("id");
            $continua = false;
            $maquina = null;
            
            if ($id_maquina !== null) {
                $maquina = Maquinas::find($id_maquina);
                if (intval($empresa->lixeira)) {
                    $connection->rollBack();
                    return "MAQUINA EXCLUIDA";
                }
                if ($this->comparar_texto($maquina->descr, $request->maquina)) $continua = true; // App\Http\Controllers\Controller.php
            } else {
                $maquina = new Maquinas;
                $continua = true;
            }
            if ($continua) {
                $maquina->descr = mb_strtoupper($request->maquina);
                $maquina->save();
                $this->log_inserir($id_maquina !== null ? "E" : "C", "maquinas", $maquina->id, "ERP", $request->usuario); // App\Http\Controllers\Controller.php
            }

            $id_comodato = Comodatos::find($request->id_comodato)->value("id");
            $continua = false;
            $comodato = null;

            if ($id_comodato !== null) {
                $comodato = Comodatos::find($id_comodato);
                if ($comodato->id_empresa != $empresa->id) {
                    $comodato->fim = date("Y-m-d");
                    $comodato->save();
                    $this->log_inserir("E", "comodatos", $comodato->id, "ERP", $request->usuario); // App\Http\Controllers\Controller.php
                    if ($this->gerar_atribuicoes($comodato)) $this->atualizar_tudo([$comodato->id_maquina]); // App\Http\Controllers\Controller.php
                    $continua = true;
                }
            } else $continua = true;

            if ($continua) {
                $dtinicio = Carbon::createFromFormat('d-m-Y', $request->inicio)->format('Y-m-d');
                $dtfim = Carbon::createFromFormat('d-m-Y', $request->fim)->format('Y-m-d');

                $comodato = new Comodatos;
                $comodato->id_maquina = $maquina->id;
                $comodato->id_empresa = $empresa->id;
                $comodato->inicio = $dtinicio;
                $comodato->fim = $dtfim;
                $comodato->fim_orig = $dtfim;
                $comodato->save();
                $this->log_inserir("C", "comodatos", $comodato->id, "ERP", $request->usuario);
            }

            $connection->commit();
            
            $resultado = new \stdClass;
            $resultado->empresa = $empresa->id;
            $resultado->maquina = $maquina->id;
            return json_encode($resultado);
        } catch (\Exception $e) {
            $connection->rollBack();
	        return $e->getMessage();
        }
    }

    public function maquinas_inativar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $comodato = Comodatos::find($request->id_comodato);

        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            $comodato->fim = date("Y-m-d");
            $comodato->save();
            $this->log_inserir("E", "comodatos", $comodato->id, "ERP", $request->usuario); // App\Http\Controllers\Controller.php
            if ($this->gerar_atribuicoes($comodato)) $this->atualizar_tudo([$comodato->id_maquina]); // App\Http\Controllers\Controller.php
            $connection->commit();
            return "OK";
        } catch (\Exception $e) {
            $connection->rollBack();
	        return $e->getMessage();
        }
    }

    public function produtos_listar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $id_comodato = intval($request->id_comodato);
        return json_encode(
            DB::table("produtos")
                ->select(
                    "produtos.cod_externo AS cod_itm",
                    "produtos.descr AS descr_itm",
                    DB::raw("IFNULL(produtos.ca, '') AS ca"),
                    DB::raw("IFNULL(DATE_FORMAT(produtos.validade_ca, '%d-%m-%Y'), '') AS validade_ca"),
                    DB::raw("IFNULL(produtos.validade, 0) AS validade"),
                    DB::raw("IFNULL(cp.preco, 0) AS preco"),
                    DB::raw("IFNULL(cp.minimo, 0) AS minimo"),
                    DB::raw("IFNULL(cp.maximo, 0) AS maximo"),
                    DB::raw("IFNULL(estq_maq.qtq, 0) AS qtd_maq"),
                    DB::raw("IFNULL(SUM(estq_emp.qtd), 0) AS qtd_emp"),
                    "empresas.nome_fantasia AS empresa",
                    "empresas.cod_externo AS cod_cft"
                )
                ->join("comodatos_produtos AS cp", "cp.id_produto", "produtos.id")
                ->leftjoin("vestoque AS estq_maq", "estq_maq.id_cp", "cp.id")
                ->join("comodatos", "comodatos.id", "cp.id_comodato")
                ->join("empresas", "empresas.id", "comodatos.id_empresa")
                ->leftjoin("empresas AS filiais", "filiais.id", "empresas.id_matriz")
                ->join("comodatos AS outros", function($join) {
                    $join->on("outros.id_empresa", "empresas.id")
                        ->orOn("outros.id_empresa", "filiais.id");
                })
                ->join("comodatos_produtos AS cpe", function($join) {
                    $join->on("cpe.id_comodato", "outros.id")
                        ->on("cpe.id_produto", "produtos.id");
                })
                ->leftjoin("vestoque AS estq_emp", "estq_emp.id_cp", "cpe.id")
                ->where("produtos.lixeira", 0)
                ->where("cp.lixeira", 0)
                ->where("cpe.lixeira", 0)
                ->where("empresas.lixeira", 0)
                ->where("filiais.lixeira", 0)
                ->where("comodatos.id", $id_comodato)
                ->whereRaw("IFNULL(produtos.cod_externo, '') <> ''")
                ->whereRaw("CURDATE() >= comodatos.inicio")
                ->whereRaw("CURDATE() < comodatos.fim")
                ->whereRaw("CURDATE() >= outros.inicio")
                ->whereRaw("CURDATE() < outros.fim")
                ->groupby(
                    "produtos.cod_externo",
                    "produtos.descr",
                    "produtos.ca",
                    "produtos.validade_ca",
                    "produtos.validade",
                    "cp.preco",
                    "cp.minimo",
                    "cp.maximo",
                    "estq_maq.qtd",
                    "empresas.nome_fantasia",
                    "empresas.cod_externo"
                )
                ->orderBy(DB::raw("
                    CASE
                        WHEN (IFNULL(estq_maq.qtq, 0) < IFNULL(cp.minimo, 0)) THEN (IFNULL(estq_maq.qtq, 0) - IFNULL(cp.minimo, 0))
                        ELSE IFNULL(estq_maq.qtq, 0)
                    END
                "))
                ->get()
        );
    }

    public function produtos_salvar(Request $request) {
        if ($request->token != config("app.key")) return 401;

        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            $ids_cdp = array();
            $cods_cdp = array();
            $ids_itm = array();
            $cods_itm = array();
            $comodato = Comodatos::find($request->comodato);
            $req_produtos = (array) $request->produtos;
            $usuario = $request->usuario;

            foreach ($req_produtos as $req_produto_arr) {
                $req_produto = (object) $req_produto_arr;
                $produto = Produtos::find($req_produto->id);
                $continua = false;
                $criou = false;
                $inserir_log = true;
                $inserir_log_cp = true;
                $validade_ca = Carbon::createFromFormat('d-m-Y', $req_produto->validade_ca)->format('Y-m-d');
                if ($produto !== null) {
                    if ($this->comparar_texto($req_produto->cod, $produto->cod_externo)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_texto($req_produto->descr, $produto->descr)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_texto($req_produto->ca, $produto->ca)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_texto($validade_ca, $produto->validade_ca)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_texto($req_produto->refer, $produto->referencia)) { // App\Http\Controllers\Controller.php
                        $this->atribuicao_atualiza_ref($req_produto->id, $produto->referencia, $req_produto->refer, $usuario, true); // App\Http\Controllers\Controller.php
                        $continua = true;
                    }
                    if ($this->comparar_texto($req_produto->cod_fab, $produto->cod_fab)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_texto($req_produto->tamanho, $produto->tamanho)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_texto($req_produto->foto, $produto->foto)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_num($req_produto->prcad, $produto->preco)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_num($req_produto->prmin, $produto->prmin)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_num($req_produto->validade, $produto->validade)) $continua = true; // App\Http\Controllers\Controller.php
                    if ($this->comparar_num($req_produto->consumo, $produto->consumo)) $continua = true; // App\Http\Controllers\Controller.php
                } else {
                    $produto = new Produtos;
                    $continua = true;
                    $criou = true;
                }
                if ($continua) {
                    $produto->cod_externo = $req_produto->cod;
                    $produto->descr = $req_produto->descr;
                    $produto->ca = $req_produto->ca;
                    $produto->validade_ca = $validade_ca;
                    $produto->referencia = $req_produto->refer;
                    $produto->cod_fab = $req_produto->cod_fab;
                    $produto->tamanho = $req_produto->tamanho;
                    $produto->foto = $req_produto->foto;
                    $produto->preco = $req_produto->prcad;
                    $produto->prmin = $req_produto->prmin;
                    $produto->validade = $req_produto->validade;
                    $produto->consumo = $req_produto->consumo;
                    $produto->save();
                    $inserir_log = false;
                    $this->log_inserir(intval($req_produto->id) ? "E" : "C", "produtos", $produto->id, "ERP", $usuario); // App\Http\Controllers\Controller.php
                }
                $id_cp = $comodato->cp($produto->id)->value("id");
                if ($id_cp === null) {
                    $cp = new ComodatosProdutos;
                    $cp->id_comodato = $comodato->id;
                    $cp->id_produto = $produto->id;
                    $cp->minimo = $produto->minimo;
                    $cp->maximo = $produto->maximo;
                    $cp->preco = $produto->preco;
                    $cp->save();
                    if ($criou) {
                        array_push($ids_itm, $produto->id);
                        array_push($cods_itm, $produto->cod_externo);
                    }
                    $id_cp = $cp->id;
                    $inserir_log_cp = false;
                    $this->log_inserir("C", "comodatos_produtos", $id_cp, "ERP", $usuario); // App\Http\Controllers\Controller.php
                }
                if (
                    $this->comparar_num($req_produto->preco, $req_produto->prcad) || // App\Http\Controllers\Controller.php
                    $this->comparar_num($req_produto->minimo, $req_produto->minimo) || // App\Http\Controllers\Controller.php
                    $this->comparar_num($req_produto->maximo, $req_produto->maximo) // App\Http\Controllers\Controller.php
                ) {
                    $cp = ComodatosProdutos::find($id_cp);
                    $cp->preco = $req_produto->preco;
                    $cp->minimo = $req_produto->minimo;
                    $cp->maximo = $req_produto->maximo;
                    $cp->save();
                    if (intval($req_produto->id) && $inserir_log_cp) $this->log_inserir("E", "comodatos_produtos", $id_cp, "ERP", $usuario); // App\Http\Controllers\Controller.php
                }
                $req_categoria = (object) $req_produto->categoria;
                if (intval($req_categoria->cod)) {
                    $categoria = Categorias::find($req_categoria->id);
                    $continua = false;
                    if ($categoria !== null) {
                        if ($this->comparar_num($req_categoria->cod, $categoria->id_externo)) $continua = true; // App\Http\Controllers\Controller.php
                        if ($this->comparar_texto($req_categoria->descr, $categoria->descr)) $continua = true; // App\Http\Controllers\Controller.php
                    } else {
                        $categoria = new Categorias;
                        $continua = true;
                    }
                    if ($continua) {
                        $categoria->id_externo = $req_categoria->cod;
                        $categoria->descr = $req_categoria->descr;
                        $categoria->save();
                        $this->log_inserir(intval($req_categoria->id) ? "E" : "C", "maquinas", $categoria->id, "ERP", $usuario);
                        if (!intval($req_categoria->id)) {
                            array_push($ids_cdp, $categoria->id);
                            array_push($cods_cdp, $categoria->id_externo);
                        }
                    }
                    if ($this->comparar_num($produto->id_categoria, $categoria->id)) { // App\Http\Controllers\Controller.php
                        $produto->id_categoria = $categoria->id;
                        $produto->save();
                        if ($inserir_log && !intval($req_categoria->id)) $this->log_inserir("E", "produtos", $produto->id, "ERP", $usuario); // App\Http\Controllers\Controller.php
                    }
                } else {
                    $id_cat = 0;
                    if ($produto->id_categoria !== null) $id_cat = intval($produto->id_categoria);
                    if ($id_cat) {
                        $produto->id_categoria = 0;
                        $produto->save();
                        if ($inserir_log && !intval($req_categoria->id)) $this->log_inserir("E", "produtos", $produto->id, "ERP", $usuario); // App\Http\Controllers\Controller.php
                    }
                }
            }
            if ($this->gerar_atribuicoes($comodato)) $this->atualizar_tudo([$comodato->id_maquina]); // App\Http\Controllers\Controller.php
            $connection->commit();
            $resultado = new \stdClass;
            $resultado->ids_cdp = implode("|", $ids_cdp);
            $resultado->cods_cdp = implode("|", $cods_cdp);
            $resultado->ids_itm = implode("|", $ids_itm);
            $resultado->cods_itm = implode("|", $cods_itm);
            return json_encode($resultado);
        } catch (\Exception $e) {
            $connection->rollBack();
	        return $e->getMessage();
        }
    }

    public function produtos_inativar(Request $request) {
        if ($request->token != config("app.key")) return 401;

        $comodato = Comodatos::find($request->id_comodato);

        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            $cp = $comodato->cp($request->id_produto);
            $cp->lixeira = 1;
            $cp->save();

            $this->log_inserir("D", "comodatos_produtos", $cp->id, "ERP", $request->usuario); // App\Http\Controllers\Controller.php
            
            if ($this->gerar_atribuicoes($comodato)) $this->atualizar_tudo([$comodato->id_maquina]); // App\Http\Controllers\Controller.php

            $connection->commit();
            return "OK";
        } catch (\Exception $e) {
            $connection->rollBack();
	        return $e->getMessage();
        }
    }

    public function estoque(Request $request) {
        if ($request->token != config("app.key")) return 401;

        $comodato = Comodatos::find($request->id_comodato);
        $req_produtos = (array) $request->produtos;
        $usuario = $request->usuario;

        foreach ($req_produtos as $produto) {
            $produto = (object) $produto;
            $produto_m = Produtos::where("cod_externo", $produto->cod);
            $cp = ComodatosProdutos::find($comodato->cp($produto_m->id));
            if ($this->comparar_num($produto->preco, $cp->preco)) {
                $cp->preco = $produto->preco;
                $cp->save();
                $this->log_inserir("E", "comodatos_produtos", $cp->id, "ERP", $usuario); // App\Http\Controllers\Controller.php
            }
            $estq = new Estoque;
            $estq->es = "E";
            $estq->qtd = $produto->qtd;
            $estq->id_cp = $cp->id;
            $estq->preco = $produto->preco;
            $estq->data = date("Y-m-d");
            $estq->hms = date("H:i:s");
            $estq->save();
            $this->log_inserir("C", "estoque", $estq->id, "ERP", $usuario); // App\Http\Controllers\Controller.php
        }
        
        return "OK";
    }
}