<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use App\Models\Empresas;
use App\Models\Maquinas;
use App\Models\Categorias;
use App\Models\Produtos;
use App\Models\Estoque;
use App\Models\Solicitacoes;
use App\Models\SolicitacoesProdutos;
use App\Models\Comodatos;
use App\Models\ComodatosProdutos;
use App\Models\Retiradas;
use Illuminate\Http\Request;

class Api2Controller extends Controller {
    private function maquinas($cft) {
        return DB::table("maquinas")
                    ->select(
                        "maquinas.id",
                        DB::raw("
                            CASE
                                WHEN id_ant IS NOT NULL THEN id_ant
                                ELSE id
                            END AS seq
                        "),
                        "maquinas.descr",
                        DB::raw("
                            CASE
                                WHEN (CURDATE() >= comodatos.inicio AND CURDATE() < comodatos.fim) THEN 'S'
                                ELSE 'N'
                            END AS ativo
                        ")
                    )
                    ->join("comodatos", "comodatos.id_maquina", "maquinas.id")
                    ->whereIn(
                        "comodatos.id_empresa",
                        DB::table("empresas")
                            ->select("id")
                            ->where("cod_externo", $cft)
                            ->unionAll(
                                DB::table("empresas")
                                    ->select("filiais.id")
                                    ->join("empresas AS filiais", "filiais.id_matriz", "empresas.id")
                                    ->where("empresas.cod_externo", $cft)
                            )
                            ->pluck("id")
                            ->toArray()
                    );
    }

    private function sincronizar_produtos($maquina, $usuario, $produtos) {
        $ids_cdp = array();
        $cods_cdp = array();
        $ids_itm = array();
        $cods_itm = array();
        $comodato = $this->obter_comodato($maquina); // App\Http\Controllers\Controller.php
        foreach ($produtos as $req_produto_arr) {
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
            if ($this->comparar_num($req_produto->preco, $req_produto->prcad)) { // App\Http\Controllers\Controller.php
                $cp = ComodatosProdutos::find($id_cp);
                $cp->preco = $req_produto->preco;
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
            
            $saldo_ant = $this->retorna_saldo_cp($comodato->id, $produto->id);
            $estq = new Estoque;
            $estq->es = "E";
            $estq->qtd = $req_produto->qtd;
            $estq->id_cp = $id_cp;
            $estq->preco = ComodatosProdutos::find($id_cp)->preco;
            $estq->data = date("Y-m-d");
            $estq->hms = date("H:i:s");
            $estq->save();
            $this->log_inserir("C", "estoque", $estq->id, "ERP", $usuario); // App\Http\Controllers\Controller.php
        }
        $resultado = new \stdClass;
        $resultado->ids_cdp = $ids_cdp;
        $resultado->cods_cdp = $cods_cdp;
        $resultado->ids_itm = $ids_itm;
        $resultado->cods_itm = $cods_itm;
        return $resultado;
    }

    private function alterar_solicitacao(Request $request, $status) {
        if ($request->token != config("app.key")) return 401;
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            $solicitacao = Solicitacoes::find($request->id);
            $solicitacao->data = $status == "E" ? Carbon::createFromFormat('d-m-Y', $request->prazo)->format('Y-m-d') : date("Y-m-d");
            $solicitacao->status = $status;
            $solicitacao->avisou = 0;
            $solicitacao->usuario_erp = $request->usu;
            $solicitacao->save();
            $this->log_inserir("E", "solicitacoes", $solicitacao->id, "ERP", $request->usu); // App\Http\Controllers\Controller.php
            $connection->commit();
            return 200;
        } catch (\Exception $e) {    
            $connection->rollBack();
            return 500;
        }
    }

    public function maquinas_por_cliente(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return json_encode($this->maquinas($request->cft)->get());
    }

    public function maquinas_todas(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $clientes = Empresas::where("lixeira", 0)->whereNotNull("cod_externo")->pluck("cod_externo");
        $resultado = array();
        foreach ($clientes as $cft) {
            $consulta = $this->maquinas($cft)->get();
            foreach ($consulta as $linha) {
                $aux = new \stdClass;
                $aux->cft = $cft;
                $aux->id = $linha->id;
                $aux->seq = $linha->seq;
                $aux->descr = $linha->descr;
                $aux->ativo = $linha->ativo;
                array_push($resultado, $aux);
            }
        }
        return json_encode($resultado);
    }

    public function consultar_maquina(Request $request) {
        if ($request->token != config("app.key")) return 401;
        if (
            $this->maquinas($request->cft)
                ->where("maquinas.descr", $request->maq)
                ->where("maquinas.lixeira", 0)
                ->exists()
        ) return "CLIENTE";
        if (Maquinas::where("descr", $request->maq)->where("lixeira", 0)->exists()) return "MAQUINA";
        return "OK";
    }

    public function criar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $cnpj = filter_var($request->cnpj, FILTER_SANITIZE_NUMBER_INT);
        $id_empresa = Empresas::where("cnpj", $cnpj)->orWhere("cod_externo", $request->emp_cod)->value("id");
        $continua = false;
        $empresa = null;
                
        if ($id_empresa !== null) {
            $empresa = Empresas::find($id_empresa);
            if (intval($empresa->lixeira)) return "EXCLUIDO";
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
            $this->log_inserir($id_empresa !== null ? "E" : "C", "empresas", $empresa->id, "ERP", $request->usu); // App\Http\Controllers\Controller.php
        }
        $maquina = new Maquinas;
        $maquina->descr = mb_strtoupper($request->maq);
        $maquina->save();
        $this->log_inserir("C", "maquinas", $maquina->id, "ERP", $request->usu); // App\Http\Controllers\Controller.php

        $dtinicio = Carbon::createFromFormat('d-m-Y', $request->inicio)->format('Y-m-d');
        $dtfim = Carbon::createFromFormat('d-m-Y', $request->fim)->format('Y-m-d');
        $comodato = new Comodatos;
        $comodato->id_maquina = $maquina->id;
        $comodato->id_empresa = $empresa->id;
        $comodato->inicio = $dtinicio;
        $comodato->fim = $dtfim;
        $comodato->fim_orig = $dtfim;
        $comodato->save();
        $this->log_inserir("C", "comodatos", $comodato->id, "ERP", $request->usu);

        return $empresa->id;
    }

    public function produtos(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $consulta = DB::table("produtos")
                        ->select(
                            "produtos.id",
                            DB::raw("IFNULL(produtos.descr, '') AS descr"),
                            DB::raw("IFNULL(produtos.preco, 0) AS preco"),
                            DB::raw("IFNULL(produtos.ca, '') AS ca"),
                            DB::raw("IFNULL(produtos.validade, 0) AS validade"),
                            DB::raw("DATE_FORMAT(produtos.validade_ca, '%d-%m-%Y') AS validade_ca"),
                            DB::raw("IFNULL(produtos.referencia, '') AS refer"),
                            DB::raw("
                                CASE
                                    WHEN IFNULL(produtos.consumo, 0) = 0 THEN ''
                                    ELSE 'S'
                                END AS consumo
                            "),
                            DB::raw("IFNULL(produtos.cod_fab, '') AS fab"),
                            DB::raw("IFNULL(produtos.tamanho, '') AS tamanho"),
                            DB::raw("IFNULL(produtos.foto, '') AS foto"),
                            DB::raw("IFNULL(categorias.id, 0) AS iCdp"),
                            DB::raw("IFNULL(categorias.descr, '') AS categoria"),
                            "produtos.lixeira"
                        )
                        ->leftjoin("categorias", "categorias.id", "produtos.id_categoria")
                        ->where("produtos.cod_externo", $request->itm)
                        ->first();
        if ($consulta === null) return "";
        return json_encode($consulta);
    }

    public function sincronizar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $produtos = (object) $produtos;
        $resultado = $this->sincronizar_produtos($request->maq, $request->usu, $produtos);
        return json_encode(array(
            "ids_cdp" => implode("|", $resultado->ids_cdp),
            "cods_cdp" => implode("|", $resultado->cods_cdp),
            "ids_itm" => implode("|", $resultado->ids_itm),
            "cods_itm" => implode("|", $resultado->cods_itm)
        ));
    }

    public function pode_faturar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return $this->maquinas($request->cft)
                    ->whereRaw("CURDATE() >= comodatos.inicio")
                    ->whereRaw("CURDATE() < comodatos.fim")
                    ->where("maquinas.id", $request->maq)
                    ->exists()
        ? "OK" : "ERRO";
    }

    public function enviar_solicitacoes(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return json_encode(collect(
            DB::table("solicitacoes")
                ->select(
                    "solicitacoes.id",
                    "solicitacoes.status",
                    "solicitacoes.usuario_web AS autor",
                    "empresas.cod_externo AS cft",
                    DB::raw("DATE_FORMAT(solicitacoes.data, '%d-%m-%Y') AS data"),
                    "produtos.cod_externo AS cod",
                    "cp.preco AS vunit",
                    "sp.qtd_orig AS qtd"
                )
                ->join("comodatos", "comodatos.id", "solicitacoes.id_comodato")
                ->join("empresas", "empresas.id", "comodatos.id_empresa")
                ->join("solicitacoes_produtos AS sp", "sp.id_solicitacao", "solicitacoes.id")
                ->join("produtos", "produtos.id", "sp.id_produto_orig")
                ->join("comodatos_produtos AS cp", function($join) {
                    $join->on("cp.id_produto", "produtos.id")
                        ->on("cp.id_comodato", "comodatos.id");
                })
                ->whereRaw("CURDATE() >= comodatos.inicio")
                ->whereRaw("CURDATE() < comodatos.fim")
                ->where("empresas.lixeira", 0)
                ->where(function($sql) {
                    $sql->where("solicitacoes.status", "A")
                        ->orWhere("solicitacoes.status", "C");
                })
                ->whereNotNull("empresas.cod_externo")
                ->get()
        )->groupBy("id")->map(function($produtos) {
            return [
                "id" => $produtos[0]->id,
                "cft" => $produtos[0]->cft,
                "data" => $produtos[0]->data,
                "status" => $produtos[0]->status,
                "autor" => $produtos[0]->autor,
                "produtos" => collect($produtos)->map(function($produto) {
                    return [
                        "cod" => $produto->cod,
                        "qtd" => (int) $produto->qtd,
                        "vunit" => (float) $produto->vunit
                    ];
                })->values()->all()
            ];
        })->values()->all());
    }

    public function gravar_solicitacao(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            foreach($request->solicitacoes as $req_solicitacao) {
                $solicitacao = Solicitacoes::find($req_solicitacao["id"]);
                $solicitacao->id_externo = $req_solicitacao["recntf"];
                $solicitacao->save();
                $this->log_inserir("E", "solicitacoes", $solicitacao->id, "ERP", $request->usu); // App\Http\Controllers\Controller.php
            }
            $connection->commit();
            return 200;
        } catch (\Exception $e) {    
            $connection->rollBack();
            return 500;
        }
    }

    public function gravar_inexistentes(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            foreach($request->produtos as $req_sp) {
                $consulta = DB::table("solicitacoes_produtos AS sp")
                    ->select(
                        "sp.id",
                        "produtos.descr"
                    )
                    ->join("produtos", "produtos.id", "sp.id_produto_orig")
                    ->where("produtos.cod_externo", $req_sp["cod"])
                    ->where("sp.id_solicitacao", $req_sp["id_solicitacao"])
                    ->get();
                $sp = SolicitacoesProdutos::find($consulta[0]->id);
                $sp->obs = "Item removido: ".$req_sp["cod"]." - ".$consulta[0]->descr."|".config("app.msg_inexistente");
                $sp->save();
                $this->log_inserir("E", "solicitacoes_produtos", $sp->id, "ERP", $request->usu); // App\Http\Controllers\Controller.php
                $solicitacao = Solicitacoes::find($req_sp["id_solicitacao"]);
                if (intval($solicitacao->avisou)) {
                    $solicitacao->avisou = 0;
                    $solicitacao->save();
                    $this->log_inserir("E", "solicitacoes", $solicitacao->id, "ERP", $request->usu); // App\Http\Controllers\Controller.php
                }
            }
            $connection->commit();
            return 200;
        } catch (\Exception $e) {    
            $connection->rollBack();
            return 500;
        }
    }

    public function aceitar_solicitacao(Request $request) {
        return $this->alterar_solicitacao($request, "E");
    }

    public function recusar_solicitacao(Request $request) {
        return $this->alterar_solicitacao($request, "R");
    }

    public function receber_solicitacao(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $ids_cdp = array();
        $cods_cdp = array();
        $ids_itm = array();
        $cods_itm = array();
        foreach ($request->solicitacoes as $req_solicitacao_arr) {
            $req_solicitacao = (object) $req_solicitacao_arr;
            $solicitacao = Solicitacoes::find($req_solicitacao->id);
            $solicitacao->status = "F";
            $solicitacao->avisou = 0;
            $solicitacao->usuario_erp2 = $request->usu;
            $solicitacao->data = Carbon::createFromFormat('d-m-Y', $req_solicitacao->data)->format('Y-m-d');
            $solicitacao->save();
            $this->log_inserir("E", "solicitacoes", $solicitacao->id, "ERP", $request->usu); // App\Http\Controllers\Controller.php
            $maquina = Comodatos::find($solicitacao->id_comodato)->id_maquina;
            $sincronizacao = $this->sincronizar_produtos($maquina, $request->usu, $req_solicitacao->produtos);
            $ids_cdp_tmp = $sincronizacao->ids_cdp;
            $cods_cdp_tmp = $sincronizacao->cods_cdp;
            $ids_itm_tmp = $sincronizacao->ids_itm;
            $cods_itm_tmp = $sincronizacao->cods_itm;
            foreach ($ids_cdp_tmp as $id_cdp) array_push($ids_cdp, $id_cdp);
            foreach ($cods_cdp_tmp as $cod_cdp) array_push($cods_cdp, $cod_cdp);
            foreach ($ids_itm_tmp as $id_itm) array_push($ids_itm, $id_itm);
            foreach ($cods_itm_tmp as $cod_itm) array_push($cods_itm, $cod_itm);
            foreach ($req_solicitacao->produtos as $req_produto_arr) {
                $req_produto = (object) $req_produto_arr;
                $id_sp = DB::table("solicitacoes_produtos AS sp")
                            ->select("sp.id")
                            ->join("produtos", "produtos.id", "sp.id_produto_orig")
                            ->where("sp.id_solicitacao", $solicitacao->id)
                            ->where("produtos.cod_externo", $req_produto->cod)
                            ->get();
                if (sizeof($id_sp)) $id_sp = $id_sp[0]->id;
                else $id_sp = 0;
                $sp = SolicitacoesProdutos::firstOrNew(["id" => $id_sp]);
                $sp->id_produto = Produtos::where("cod_externo", $req_produto->cod)->value("id");
                $sp->id_solicitacao = $solicitacao->id;
                $sp->obs = $req_produto->obs ? $req_produto->obs."|".$req_produto->obs2 : "";
                $sp->qtd = $req_produto->qtd;
                $sp->preco = Produtos::find($sp->id_produto)->cp($solicitacao->id_comodato)->value("preco");
                if (!intval($id_sp)) $sp->origem = "ERP";
                $sp->save();
                $this->log_inserir(!intval($id_sp) ? "C" : "E", "solicitacoes_produtos", $sp->id, "ERP", $request->usu); // App\Http\Controllers\Controller.php
            }
        }
        return json_encode(array(
            "ids_cdp" => implode("|", $ids_cdp),
            "cods_cdp" => implode("|", $cods_cdp),
            "ids_itm" => implode("|", $ids_itm),
            "cods_itm" => implode("|", $cods_itm)
        ));
    }

    public function obter_retiradas(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return json_encode(collect(
            DB::table("retiradas")
                ->select(
                    "retiradas.id",
                    "empresas.cod_externo AS cft",
                    DB::raw("DATE_FORMAT(retiradas.data, '%d-%m-%Y') AS data"),
                    "produtos.cod_externo AS cod_itm",
                    "retiradas.preco AS vunit",
                    "retiradas.qtd",
                    DB::raw("IFNULL(retiradas.hora, '') AS hora")
                )
                ->join("empresas", "empresas.id", "retiradas.id_empresa")
                ->join("produtos", "produtos.id", "retiradas.id_produto")
                ->whereRaw("IFNULL(retiradas.numero_ped, 0) = 0")
                ->where("empresas.lixeira", 0)
                ->whereNotNull("empresas.cod_externo")
                ->limit(400)
                ->get()
        )->groupBy("cft")->map(function($retiradas) {
            return [
                "cft" => $retiradas[0]->cft,
                "retiradas" => collect($retiradas)->map(function($retirada) {
                    return [
                        "id" => $retirada->id,
                        "data" => $retirada->data,
                        "cod_itm" => $retirada->cod_itm,
                        "qtd" => (int) $retirada->qtd,
                        "vunit" => (float) $retirada->vunit,
                        "hora" => $retirada->hms
                    ];
                })->values()->all()
            ];
        })->values()->all());
    }

    public function salvar_retirada(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            foreach($request->retiradas as $req_retirada_arr) {
                $req_retirada = (object) $req_retirada_arr;
                $retirada = Retiradas::find($req_retirada->id);
                $retirada->numero_ped = $req_retirada->cod_ods;
                $retirada->save();
                $this->log_inserir("E", "retiradas", $retirada->id, "ERP", $request->usu); // App\Http\Controllers\Controller.php
            }
            $connection->commit();
            return 200;
        } catch (\Exception $e) {    
            $connection->rollBack();
            return 500;
        }
    }
}