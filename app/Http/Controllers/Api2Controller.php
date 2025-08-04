<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use App\Models\Empresas;
use App\Models\Valores;
use App\Models\Produtos;
use App\Models\Estoque;
use App\Models\Solicitacoes;
use App\Models\SolicitacoesProdutos;
use App\Models\Comodatos;
use App\Models\Retiradas;
use Illuminate\Http\Request;

class Api2Controller extends ControllerKX {
    private function comparar_texto($a, $b) {
        if ($a === null) $a = "";
        if ($b === null) $b = "";
        return mb_strtoupper(trim($a)) != mb_strtoupper(trim($b));
    }

    private function comparar_num($a, $b) {
        if ($a === null) $a = 0;
        if ($b === null) $b = 0;
        return floatval($a) != floatval($b);
    }

    private function maquinas($cft) {
        DB::table("valores")
                    ->select(
                        "valores.id",
                        "valores.seq",
                        "valores.descr",
                        DB::raw("
                            CASE
                                WHEN ((CURDATE() BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim)) THEN 'S'
                                ELSE 'N'
                            END AS ativo
                        ")
                    )
                    ->join("comodatos", "comodatos.id_maquina", "valores.id")
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
                    )
                    ->where("valores.lixeira", 0);
    }

    public function maquinas_por_cliente(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return $this->maquinas($request->cft)->get();
    }

    public function consultar_maquina(Request $request) {
        if ($request->token != config("app.key")) return 401;
        if (sizeof(
            $this->maquinas($request->cft)
                ->where("valores.descr", $request->maq)
                ->get()
        )) return "CLIENTE";
        if (sizeof(
            DB::table("valores")
                ->where("descr", $request->maq)
                ->where("lixeira", 0)
                ->get()
        )) return "MAQUINA";
        return "OK";
    }

    public function criar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $cnpj = filter_var($str, $request->cnpj);
        $id_empresa = DB::table("empresas")
                        ->where("cnpj", $cnpj)
                        ->orWhere("cod_externo", $request->emp_cod)
                        ->value("id");
        $continua = false;
        $empresa = null;
        if ($id_empresa !== null) {
            $empresa = Empresas::find($id_empresa);
            if (intval($empresa->lixeira)) return "EXCLUIDO";
            if ($this->comparar_texto($empresa->cnpj, $cnpj)) $continua = true;
            if ($this->comparar_texto($empresa->razao_social, $request->emp_razao)) $continua = true;
            if ($this->comparar_texto($empresa->nome_fantasia, $request->emp_fantasia)) $continua = true;   
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
            $this->log_inserir($id_empresa !== null ? "E" : "C", "empresas", $empresa->id, "ERP", $request->usu);
        }
        $maquina = new Valores;
        $maquina->descr = mb_strtoupper($request->maq);
        $maquina->alias = "maquinas";
        $maquina->seq = intval(
            DB::table("valores")
                ->selectRaw("IFNULL(MAX(seq), 0) AS ultimo")
                ->where("alias", "maquinas")
                ->value("ultimo")
        ) + 1;
        $maquina->save();
        $this->log_inserir("C", "valores", $maquina->id, "ERP", $request->usu);
        $this->criar_mp("produtos.id", $maquina->id, true, $request->usu);
        $this->criar_comodato_main($maquina->id, $empresa->id, $request->ini, $request->fim);
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
                            DB::raw("DATE_FORMAT(produtos.validade_ca, '%d/%m/%Y') AS validade_ca"),
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
                            DB::raw("IFNULL(valores.id, 0) AS iCdp"),
                            DB::raw("IFNULL(valores.descr, '') AS categoria"),
                            "produtos.lixeira"
                        )
                        ->leftjoin("valores", "valores.id", "produtos.id_categoria")
                        ->where("produtos.cod_externo", $request->itm)
                        ->first();
        if ($consulta === null) return "";
        return json_encode($consulta);
    }

    private function sincronizar_produtos($maquina, $usuario, $produtos) {
        $ids_cdp = array();
        $cods_cdp = array();
        $ids_itm = array();
        $cods_itm = array();
        foreach ($produtos as $req_produto) {
            $produto = Produtos::find($req_produto->id);
            $continua = false;
            $inserir_log = true;
            $validade_ca = Carbon::createFromFormat('d/m/Y', $req_produto->validade_ca)->format('Y-m-d');
            if ($produto !== null) {
                if ($this->comparar_texto($req_produto->cod, $produto->cod_externo)) $continua = true;
                if ($this->comparar_texto($req_produto->descr, $produto->descr)) $continua = true;
                if ($this->comparar_texto($req_produto->ca, $produto->ca)) $continua = true;
                if ($this->comparar_texto($validade_ca, $produto->validade_ca)) $continua = true;
                if ($this->comparar_texto($req_produto->refer, $produto->referencia)) $continua = true;
                if ($this->comparar_texto($req_produto->cod_fab, $produto->cod_fab)) $continua = true;
                if ($this->comparar_texto($req_produto->tamanho, $produto->tamanho)) $continua = true;
                if ($this->comparar_texto($req_produto->foto, $produto->foto)) $continua = true;
                if ($this->comparar_num($req_produto->prcad, $produto->preco)) $continua = true;
                if ($this->comparar_num($req_produto->prmin, $produto->prmin)) $continua = true;
                if ($this->comparar_num($req_produto->validade, $produto->validade)) $continua = true;
                if ($this->comparar_num($req_produto->consumo, $produto->consumo)) $continua = true;
            } else {
                $produto = new Produtos;
                $continua = true;
            }
            if ($continua) {
                $produto->cod_externo = $req_produto->cod_externo;
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
                $this->log_inserir(intval($req_produto->id) ? "E" : "C", "produtos", $produto->id, "ERP", $usuario);
            }
            $where_mp = "id_maquina = ".$maquina." AND id_produto = ".$produto->id;
            if (!intval($req_produto->id)) {
                $this->criar_mp($produto->id, "valores.id", true, $usuario);
                if ($this->comparar_num($req_produto->preco, $req_produto->prcad)) {
                    DB::statement("
                        UPDATE maquinas_produtos
                        SET preco = ".$req_produto->preco."
                        WHERE ".$where_mp
                    );
                }
                array_push($ids_itm, $produto->id);
                array_push($cods_itm, $produto->cod_externo);
            }
            $req_categoria = $req_produto->categoria;
            if (intval($req_categoria->cod)) {
                $categoria = Valores::find($req_categoria->id);
                $continua = false;
                if ($categoria !== null) {
                    if ($this->comparar_num($req_categoria->cod, $categoria->id_externo)) $continua = true;
                    if ($this->comparar_texto($req_categoria->descr, $categoria->descr)) $continua = true;
                } else {
                    $categoria = new Valores;
                    $continua = true;
                }
                if ($continua) {
                    $categoria->id_externo = $req_categoria->cod;
                    $categoria->descr = $req_categoria->descr;
                    $categoria->alias = "categorias";
                    $categoria->save();
                    $this->log_inserir(intval($req_categoria->id) ? "E" : "C", "valores", $categoria->id, "ERP", $usuario);
                    if (!intval($req_categoria->id)) {
                        array_push($ids_cdp, $categoria->id);
                        array_push($cods_cdp, $categoria->id_externo);
                    }
                }
                if ($this->comparar_num($produto->id_categoria, $categoria->id)) {
                    $produto->id_categoria = $categoria->id;
                    $produto->save();
                    if ($inserir_log || !intval($req_categoria->id)) $this->log_inserir("E", "produtos", $produto->id, "ERP", $usuario);
                }
            } else {
                $id_cat = 0;
                if ($produto->id_categoria !== null) $id_cat = intval($produto->id_categoria);
                if ($id_cat) {
                    $produto->id_categoria = 0;
                    $produto->save();
                    if ($inserir_log || !intval($req_categoria->id)) $this->log_inserir("E", "produtos", $produto->id, "ERP", $usuario);
                }
            }
            $estq = new Estoque;
            $estq->es = "E";
            $estq->qtd = $req_produto->qtd;
            $estq->id_mp = DB::table("maquinas_produtos")
                                ->whereRaw($where_mp)
                                ->value("id");
            $estq->save();
            $this->log_inserir("C", "estoque", $estq->id, "ERP", $usuario);
        }
        $resultado = new \stdClass;
        $resultado->ids_cdp = $ids_cdp;
        $resultado->cods_cdp = $cods_cdp;
        $resultado->ids_itm = $ids_itm;
        $resultado->cods_itm = $cods_itm;
        return $resultado;
    }

    public function sincronizar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $resultado = $this->sincronizar_produtos($request->maq, $request->usu, $request->produtos);
        return json_encode(array(
            "ids_cdp" => join("|", $resultado->ids_cdp),
            "cods_cdp" => join("|", $resultado->cods_cdp),
            "ids_itm" => join("|", $resultado->ids_itm),
            "cods_itm" => join("|", $resultado->cods_itm)
        ));
    }

    public function pode_faturar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return sizeof(
            $this->maquinas($request->cft)
                    ->whereRaw("((CURDATE() BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim))")
                    ->where("valores.id", $request->maq)
                    ->get()
        ) ? "OK" : "ERRO";
    }

    public function enviar_solicitacoes() {
        if ($request->token != config("app.key")) return 401;
        return json_encode(collect(
            DB::table("solicitacoes")
                ->select(
                    "solicitacoes.id",
                    "solicitacoes.status",
                    "solicitacoes.usuario_web AS autor",
                    "empresas.cod_externo AS cft",
                    DB::raw("DATE_FORMAT(solicitacoes.data, '%d/%m/%Y') AS data"),
                    "produtos.cod_externo AS cod",
                    "mp.preco AS vunit",
                    "sp.qtd_orig AS qtd"
                )
                ->join("comodatos", "comodatos.id", "solicitacoes.id_comodato")
                ->join("empresas", "empresas.id", "comodatos.id_empresa")
                ->join("solicitacoes_produtos AS sp", "sp.id_solicitacao", "solicitacoes.id")
                ->join("produtos", "produtos.id", "sp.id_produto_orig")
                ->join("maquinas_produtos AS mp", function($join) {
                    $join->on("mp.id_produto", "produtos.id")
                        ->on("mp.id_maquina", "comodatos.id_maquina");
                })
                ->whereRaw("((CURDATE() BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim))")
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
                "produtos" => collect($produtos)->map(function($produto) {
                    return [
                        "cod" => $produto->cod,
                        "qtd" => $produto->qtd,
                        "vunit" => $produto->vunit
                    ];
                })->values()->all()
            ];
        })->values()->all());
    }

    public function gravar_solicitacao(Request $request) {
        if ($request->token != config("app.key")) return 401;
        foreach($request->solicitacoes as $req_solicitacao) {
            $solicitacao = Solicitacoes::find($req_solicitacao->id);
            $solicitacao->id_externo = $req_solicitacao->recntf;
            $solicitacao->save();
            $this->log_inserir("E", "solicitacoes", $solicitacao->id, "ERP", $request->usu);
        }
    }

    public function aceitar_solicitacao(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $solicitacao = Solicitacoes::find($request->id);
        $solicitacao->data = Carbon::createFromFormat('d/m/Y', $request->prazo)->format('Y-m-d');
        $solicitacao->status = "E";
        $solicitacao->usuario_erp = $request->usu;
        $solicitacao->save();
        $this->log_inserir("E", "solicitacoes", $solicitacao->id, "ERP", $request->usu);
    }

    public function recusar_solicitacao(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $solicitacao = Solicitacoes::find($request->id);
        $solicitacao->data = date("Y-m-d");
        $solicitacao->status = "R";
        $solicitacao->usuario_erp = $request->usu;
        $solicitacao->save();
        $this->log_inserir("E", "solicitacoes", $solicitacao->id, "ERP", $request->usu);
    }

    public function receber_solicitacao(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $ids_cdp = array();
        $cods_cdp = array();
        $ids_itm = array();
        $cods_itm = array();
        foreach ($request->solicitacoes as $req_solicitacao) {
            $solicitacao = Solicitacoes::find($req_solicitacao->id);
            $solicitacao->status = "F";
            $solicitacao->avisou = 0;
            $solicitacao->usuario_erp2 = $request->usu;
            $solicitacao->data = Carbon::createFromFormat('d/m/Y', $request->data)->format('Y-m-d');
            $solicitacao->save();
            $this->log_inserir("E", "solicitacoes", $solicitacao->id, "ERP", $request->usu);
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
            foreach ($req_solicitacao->produtos as $req_produto) {
                $id_sp = DB::table("solicitacoes_produtos AS sp")
                            ->join("produtos", "produtos.id", "sp.id_produto_orig")
                            ->where("sp.id_solicitacao", $solicitacao->id)
                            ->where("produtos.cod_externo", $req_produto->cod)
                            ->value("sp.id");
                if ($id_sp === null) $id_sp = 0;
                $sp = SolicitacoesProdutos::firstOrNew(["id" => $id_sp]);
                $sp->id_produto = DB::table("produtos")
                                        ->where("cod_externo", $req_produto->cod)
                                        ->value("id");
                $sp->id_solicitacao = $solicitacao->id;
                $sp->obs = $req_produto->obs;
                $sp->qtd = $req_produto->qtd;
                $sp->preco = DB::table("maquinas_produtos")
                                ->where("id_maquina", $maquina)
                                ->where("id_produto", $sp->id_produto)
                                ->value("preco");
                if (!intval($id_sp)) $sp->origem = "ERP";
                $sp->save();
                $this->log_inserir(!intval($id_sp) ? "C" : "E", "solicitacoes_produtos", $sp->id, "ERP", $request->usu);
            }
        }
        return json_encode(array(
            "ids_cdp" => join("|", $ids_cdp),
            "cods_cdp" => join("|", $cods_cdp),
            "ids_itm" => join("|", $ids_itm),
            "cods_itm" => join("|", $cods_itm)
        ));
    }

    public function obter_retiradas(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return json_encode(collect(
            DB::table("retiradas")
                ->select(
                    "retiradas.id",
                    "empresas.cod_externo AS cft",
                    DB::raw("DATE_FORMAT(retiradas.data, '%d/%m/%Y') AS data"),
                    "produtos.cod_externo AS cod_itm",
                    "retiradas.preco AS vunit",
                    "retiradas.qtd",
                    "IFNULL(retiradas.hora, '') AS hora"
                )
                ->join("empresas", "empresas.id", "retiradas.id_empresa")
                ->join("produtos", "produtos.id", "retiradas.id_produto")
                ->whereRaw("IFNULL(retiradas.num_ped, 0) = 0")
                ->where("empresas.lixeira", 0)
                ->whereNotNull("empresas.cod_externo")
                ->get()
        )->groupBy("cft")->map(function($retiradas) {
            return [
                "cft" => $retiradas[0]->cft,
                "retiradas" => collect($retiradas)->map(function($retirada) {
                    return [
                        "id" => $retirada->id,
                        "data" => $retirada->data,
                        "cod_itm" => $retirada->cod_itm,
                        "qtd" => $retirada->qtd,
                        "vunit" => $retirada->vunit,
                        "hora" => $retirada->hora
                    ];
                })->values()->all()
            ];
        })->values()->all());
    }

    public function salvar_retirada(Request $request) {
        if ($request->token != config("app.key")) return 401;
        foreach($request->retiradas as $req_retirada) {
            $retirada = Retiradas::find($req_retirada->id);
            $retirada->num_ped = $req_retirada->cod_ods;
            $retirada->save();
            $this->log_inserir("E", "retiradas", $retirada->id, "ERP", $request->usu);
        }
    }
}