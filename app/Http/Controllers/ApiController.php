<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Valores;
use App\Models\Produtos;
use App\Models\Estoque;
use App\Models\Retiradas;
use App\Models\Atribuicoes;
use App\Models\Empresas;
use App\Models\Pessoas;
use App\Models\Log;

class ApiController extends ControllerKX {
    private function joincomum($query) {
        return $query->join("pessoas", function($join) {
                $join->on(function($sql) {
                    $sql->on("atribuicoes.pessoa_ou_setor_valor", "pessoas.id")
                        ->where("atribuicoes.pessoa_ou_setor_chave", "P");
                })->orOn(function($sql) {
                    $sql->on("atribuicoes.pessoa_ou_setor_valor", "pessoas.id_setor")
                        ->where("atribuicoes.pessoa_ou_setor_chave", "S");
                });
            })
            ->join("produtos", function($join) {
                $join->on(function($sql) {
                    $sql->on("atribuicoes.produto_ou_referencia_valor", "produtos.cod_externo")
                        ->where("atribuicoes.produto_ou_referencia_chave", "P");
                })->orOn(function($sql) {
                    $sql->on("atribuicoes.produto_ou_referencia_valor", "produtos.referencia")
                        ->where("atribuicoes.produto_ou_referencia_chave", "R");
                });
            });
    }

    private function info_atb($id_pessoa, $obrigatorios, $grade) {
        return $this->joincomum(
                    DB::table("atribuicoes")
                        ->select(DB::raw($obrigatorios ? "
                            atribuicoes.produto_ou_referencia_chave,
                            CASE
                                WHEN atribuicoes.produto_ou_referencia_chave = 'R' THEN produtos.referencia
                                ELSE produtos.id
                            END AS chave,
                            CASE
                                WHEN atribuicoes.produto_ou_referencia_chave = 'R' THEN produtos.referencia
                                ELSE produtos.descr
                            END AS nome
                        " : "
                            atribuicoes.id AS id_atribuicao,
                            atribuicoes.obrigatorio,
                            produtos.id,
                            produtos.referencia,
                            produtos.descr AS nome,
                            produtos.detalhes,
                            produtos.cod_externo AS codbar,
                            IFNULL(produtos.tamanho, '') AS tamanho,
                            IFNULL(produtos.foto, '') AS foto,
                            (atribuicoes.qtd - IFNULL(ret.qtd, 0)) AS qtd,
                            IFNULL(DATE_FORMAT(ret.ultima_retirada, '%d/%m/%Y'), '') AS ultima_retirada,
                            DATE_FORMAT(
                                CASE
                                    WHEN (atribuicoes.qtd - IFNULL(ret.qtd, 0)) > 0 THEN CURDATE()
                                    ELSE ret.proxima_retirada
                                END,
                                '%d/%m/%Y'
                            ) AS proxima_retirada,
                            atbgrp.qtd AS qtdgrp,
                            atbgrp.proxima_retirada AS proxima_retirada_grp,
                            atbgrp.ultima_retirada AS ultima_retirada_grp
                        "))
                )->joinSub(
                    DB::table("atribuicoes_associadas")
                        ->select("id_atribuicao")
                        ->where("id_pessoa", $id_pessoa),
                    "lim1",
                    "lim1.id_atribuicao",
                    "atribuicoes.id"
                )->joinSub(
                    DB::table("vprodutos")
                        ->select("id_produto")
                        ->where("id_pessoa", $id_pessoa),
                    "lim2",
                    "lim2.id_produto",
                    "produtos.id"
                )->leftjoinSub(
                    DB::table("retiradas")
                        ->select(DB::raw("
                            retiradas.id_atribuicao,
                            retiradas.id_pessoa,
                            SUM(retiradas.qtd) AS qtd,
                            ".(!$obrigatorios ? "MAX(retiradas.data) AS ultima_retirada," : "")."
                            DATE_ADD(MAX(retiradas.data), INTERVAL atribuicoes.validade DAY) AS proxima_retirada
                        "))
                        ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                        ->whereNull("retiradas.id_supervisor")
                        ->groupby(
                            "retiradas.id_atribuicao",
                            "retiradas.id_pessoa",
                            "atribuicoes.validade"
                        ),
                    "ret",
                    function($join) {
                        $join->on("ret.id_pessoa", "pessoas.id")
                                ->on("ret.id_atribuicao", "atribuicoes.id");
                    }
                )->leftjoinSub(
                        $this->joincomum(
                            DB::table(DB::raw("(
                                SELECT * FROM atribuicoes_associadas WHERE id_pessoa = ".$id_pessoa."
                            ) AS tab"))
                                ->select(DB::raw("
                                    tab.id_atribuicao,
                                    (MIN(atribuicoes.qtd) - IFNULL(SUM(ret.qtd), 0)) AS qtd,
                                    ".(!$obrigatorios ? "IFNULL(DATE_FORMAT(MAX(ret.ultima_retirada), '%d/%m/%Y'), '') AS ultima_retirada," : "")."
                                    ".(!$obrigatorios ? "DATE_FORMAT(" : "")."
                                        CASE
                                            WHEN (MIN(atribuicoes.qtd) - IFNULL(SUM(ret.qtd), 0)) > 0 THEN CURDATE()
                                            ELSE MIN(ret.proxima_retirada)
                                        END
                                    ".(!$obrigatorios ? ",'%d/%m/%Y')" : "")."
                                    AS proxima_retirada
                                "))
                                ->join("atribuicoes", function ($join) {
                                    $join->on(DB::raw("REPLACE(tab.associados, CONCAT('|', atribuicoes.id, '|'), '')"), "<>", DB::raw("tab.associados"));
                                })
                        )->joinSub(
                            DB::table("vprodutos")
                                ->select("id_produto")
                                ->where("id_pessoa", $id_pessoa),
                            "lim",
                            "lim.id_produto",
                            "produtos.id"
                        )->leftjoinSub(
                            DB::table("retiradas")
                                ->select(DB::raw("
                                    retiradas.id_atribuicao,
                                    retiradas.id_pessoa,
                                    SUM(retiradas.qtd) AS qtd,
                                    ".(!$obrigatorios ? "MAX(retiradas.data) AS ultima_retirada," : "")."
                                    DATE_ADD(MAX(retiradas.data), INTERVAL MIN(atribuicoes.validade) DAY) AS proxima_retirada
                                "))
                                ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                                ->whereNull("retiradas.id_supervisor")
                                ->groupby(
                                    "retiradas.id_atribuicao",
                                    "retiradas.id_pessoa"
                                ),
                            "ret",
                            function($join) {
                                $join->on("ret.id_pessoa", "pessoas.id")
                                    ->on("ret.id_atribuicao", "atribuicoes.id");
                            }
                        )
                        ->groupby("tab.id_atribuicao"),
                    "atbgrp",
                    "atbgrp.id_atribuicao",
                    "atribuicoes.id"
                )->where(function($sql) use($obrigatorios, $grade) {
                    if ($obrigatorios) $sql->where("atribuicoes.obrigatorio", 1);
                    else if ($grade) $sql->whereNotNull("produtos.referencia");
                    else $sql->whereNull("produtos.referencia");
                })->whereRaw("(
                    atbgrp.proxima_retirada <= CURDATE() OR (
                        CASE
                            WHEN (atribuicoes.qtd - IFNULL(ret.qtd, 0)) > 0 THEN CURDATE()
                            ELSE ret.proxima_retirada
                        END
                    ) <= CURDATE()
                )")->whereRaw("(
                    (atribuicoes.qtd - IFNULL(ret.qtd, 0)) > 0
                )")->groupby(DB::raw(!$obrigatorios ? "
                    atribuicoes.id,
                    atribuicoes.obrigatorio,
                    produtos.id,
                    produtos.referencia,
                    produtos.descr,
                    produtos.detalhes,
                    produtos.cod_externo,
                    produtos.tamanho,
                    produtos.foto,
                    atribuicoes.qtd,
                    ret.qtd,
                    ret.ultima_retirada,
                    ret.proxima_retirada,
                    atbgrp.qtd,
                    atbgrp.proxima_retirada,
                    atbgrp.ultima_retirada
                " : "
                    atribuicoes.produto_ou_referencia_chave,
                    produtos.id,
                    produtos.descr,
                    produtos.referencia
                "))->get();
    }

    private function produtos_por_pessoa_main($id_pessoa, $grade) {
        $consulta = $this->info_atb($id_pessoa, false, $grade);

        $resultado = array();
        foreach ($consulta as $linha) {
            if ($linha->foto) {
                $foto = explode("/", $linha->foto);
                $linha->foto = $foto[sizeof($foto) - 1];
            }
            array_push($resultado, $linha);
        }

        return collect($resultado)->groupBy($grade ? "referencia" : "id")->map(function($itens) use($id_pessoa) {
            return [
                "id_pessoa" => $id_pessoa,
                "nome" => $itens[0]->nome,
                "foto" => $itens[0]->foto,
                "referencia" => $itens[0]->referencia,
                "qtd" => $itens[0]->qtd,
                "detalhes" => $itens[0]->detalhes,
                "ultima_retirada" => $itens[0]->ultima_retirada,
                "proxima_retirada" => $itens[0]->proxima_retirada,
                "obrigatorio" => $itens[0]->obrigatorio,
                "tamanhos" => $itens->groupBy("id")->map(function($tamanho) use($id_pessoa) {
                    return [
                        "id" => $tamanho[0]->id,
                        "id_pessoa" => $id_pessoa,
                        "id_atribuicao" => $tamanho[0]->id_atribuicao,
                        "selecionado" => false,
                        "codbar" => $tamanho[0]->codbar,
                        "numero" => $tamanho[0]->tamanho ? $tamanho[0]->tamanho : "UN"
                    ];
                })->values()->all()
            ];
        })->sortBy("nome")->values()->all();
    }

    //GETS
    public function empresas() {
        return json_encode(
            DB::table("empresas")
                ->select(
                    "empresas.id",
                    DB::raw("IFNULL(empresas.cod_externo, '') as cod_externo"),
                    DB::raw("
                        CONCAT(
                            empresas.nome_fantasia,
                            IFNULL(CONCAT(' - ', matriz.nome_fantasia), '')
                        ) AS descr
                    ")
                )
                ->leftjoin("empresas AS matriz", "matriz.id", "empresas.id_matriz")
                ->whereRaw("matriz.lixeira = 0 OR matriz.id IS NULL")
                ->where("empresas.lixeira", 0)
                ->get()
        );
    }

    public function maquinas(Request $request) {
        return DB::table(DB::raw("(
            SELECT
                id,
                descr
            FROM valores
            WHERE alias = 'maquinas'
                AND lixeira = 0
        ) AS tab"))->selectRaw("tab.*")->leftjoinSub(
            DB::table("comodatos")
                ->select("id_maquina")
                ->where(function($sql) use($request) {
                    if (isset($request->idEmp)) $sql->where("id_empresa", $request->idEmp);
                })
                ->whereRaw("CURDATE() >= inicio")
                ->whereRaw("CURDATE() < fim"),
        "aux", "aux.id_maquina", "tab.id")
        ->where(function($sql) use($request) {
            if (isset($request->idEmp)) $sql->whereNotNull("aux.id_maquina");
        })->get();
    }

    public function produtos_por_maquina(Request $request) {
        $consulta = DB::table("maquinas_produtos AS mp")
                        ->select(
                            "produtos.id",
                            "produtos.descr",
                            DB::raw("IFNULL(mp.preco, 0) AS preco"),
                            DB::raw("IFNULL(vestoque.qtd, 0) AS saldo"),
                            DB::raw("IFNULL(mp.minimo, 0) AS minimo"),
                            DB::raw("IFNULL(mp.maximo, 0) AS maximo")
                        )
                        ->leftjoin("vestoque", "vestoque.id_mp", "mp.id")
                        ->join("produtos", "produtos.id", "mp.id_produto")
                        ->where("mp.id_maquina", $request->idMaquina)
                        ->where("produtos.lixeira", 0)
                        ->get();
        foreach ($consulta as $linha) {
            $linha->preco = floatval($linha->preco);
            $linha->saldo = floatval($linha->saldo);
            $linha->minimo = floatval($linha->minimo);
            $linha->maximo = floatval($linha->maximo);
        }
        return json_encode($consulta);
    }

    public function retiradas_por_periodo(Request $request) {
        $retorno = DB::table("retiradas")
                        ->select(
                            "empresas.id AS empid",
                            "empresas.nome_fantasia",
                            "produtos.cod_externo",
                            DB::raw("IFNULL(empresas.cod_externo, '') AS cft_externo"),
                            DB::raw("SUM(retiradas.qtd) AS qtd"),
                            DB::raw("GROUP_CONCAT(retiradas.id) AS ids")
                        )
                        ->leftjoin("produtos", "produtos.id", "retiradas.id_produto")
                        ->leftjoin("pessoas", "pessoas.id", "retiradas.id_pessoa")
                        ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                        ->leftjoin("comodatos", "comodatos.id", "retiradas.id_comodato")
                        ->whereRaw("data BETWEEN '".$request->dini."' AND '".$request->dfim."'")
                        ->where("empresas.id", "<>", 0)
                        ->where(function($sql) use($request) {
                            if ($request->idemp) $sql->whereRaw($request->idemp." IN (empresas.id, empresas.id_matriz)");
                            if ($request->idmaq) $sql->where("comodatos.id_maquina", $request->idmaq);
                            if (!$request->listagerados) $sql->where("retiradas.gerou_pedido", "N");
                        })
                        ->groupBy(
                            "empresas.id",
                            "empresas.nome_fantasia",
                            "produtos.cod_externo"
                        )
                        ->get();
        foreach ($retorno as $item) $item->qtd = floatval($item->qtd);
        return json_encode($retorno);
    }

    //POSTS
    public function categorias(Request $request) {
        $linha = Valores::firstOrNew(["id" => $request->id]);
        $linha->descr = mb_strtoupper($request->descr);
        $linha->alias = "categorias";
        if (!$request->id) {
            $linha->seq = intval(
                DB::table("valores")
                    ->selectRaw("IFNULL(MAX(seq), 0) AS ultimo")
                    ->where("alias", "categorias")
                    ->value("ultimo")
            ) + 1;
        }
        $linha->save();
        $modelo = $this->log_inserir($request->id ? "E" : "C", "valores", $linha->id, true);
        if (isset($request->usu)) $modelo->nome = $request->usu;
        $modelo->save();
        $resultado = new \stdClass;
        $resultado->id = $linha->id;
        $resultado->descr = $linha->descr;
        return json_encode($resultado);
    }

    public function produtos(Request $request) {
        $linha = Produtos::firstOrNew(["id" => $request->id]);
        $nome = "NULL";
        if (isset($request->usu)) $nome = $request->usu;
        $this->atribuicao_atualiza_ref($request->id, $linha->referencia, $request->refer, $nome, true);
        $linha->descr = mb_strtoupper($request->descr);
        $linha->preco = $request->preco;
        $linha->validade = $request->validade;
        $linha->validade_ca = $request->validade_ca;
        $linha->ca = $request->ca;
        $linha->cod_externo = $request->codExterno;
        $linha->id_categoria = $request->idCategoria;
        $linha->foto = $request->foto;
        $linha->lixeira = $request->lixeira;
        if (isset($request->refer)) $linha->referencia = $request->refer;
        if (isset($request->tamanho)) $linha->tamanho = $request->tamanho;
        if (isset($request->consumo)) $linha->consumo = $request->consumo;
        if (isset($request->cod_fab)) $linha->cod_fab = $request->cod_fab;
        $linha->save();
        $letra_log = $request->id ? "E" : "C";
        if (intval($request->lixeira)) $letra_log = "D";
        $modelo = $this->log_inserir($letra_log, "produtos", $linha->id, true);
        if (isset($request->usu)) $modelo->nome = $request->usu;
        $modelo->save();
        $this->mov_estoque($linha->id, true, $request->usu);
        $consulta = DB::table("produtos")
                        ->select(
                            "id",
                            "descr",
                            "preco",
                            DB::raw("IFNULL(validade, 0) AS validade"),
                            DB::raw("IFNULL(ca, '') AS ca"),
                            DB::raw("IFNULL(foto, '') AS foto"),
                            "lixeira",
                            DB::raw("IFNULL(referencia, '') AS refer"),
                            DB::raw("IFNULL(tamanho, '') AS tamanho"),
                            "id_categoria AS idCategoria",
                            "cod_externo AS codExterno",
                            DB::raw("'123' AS usu"),
                            "consumo",
                            DB::raw("IFNULL (validade_ca, '') AS validade_ca"),
                            DB::raw("IFNULL (cod_fab, '') AS cod_fab")
                        )
                        ->where("id", $linha->id)
                        ->first();
        $consulta->preco = floatval($consulta->preco);
        $consulta->lixeira = intval($consulta->lixeira);
        return json_encode($consulta);
    }

    public function movimentar_estoque(Request $request) {
        for ($i = 0; $i < sizeof($request->idProduto); $i++) {
            $linha = new Estoque;
            $linha->es = $request->es[$i];
            $linha->descr = $request->descr[$i];
            $linha->qtd = $request->qtd[$i];
            $linha->id_mp = DB::table("maquinas_produtos")
                                ->where("id_produto", $request->idProduto[$i])
                                ->where("id_maquina", $request->idMaquina)
                                ->value("id");
            $linha->save();
            $modelo = $this->log_inserir("C", "estoque", $linha->id, true);
            if (isset($request->usu)) $modelo->nome = $request->usu;
            $modelo->save();
        }
        return 200;
    }

    public function gerenciar_estoque(Request $request) {
        $precoProd = floatval(DB::select("produtos")->where("id", $request->idProduto)->value("preco"));
        if (isset($request->preco)) {
            if (floatval($request->preco) > 0) $precoProd = floatval($request->preco);
        }
        $nome = "NULL";
        $where = "id_produto = ".$request->idProduto." AND id_maquina = ".$request->idMaquina;
        DB::statement("
            UPDATE maquinas_produtos SET
                minimo = ".$request->minimo.",
                maximo = ".$request->maximo.",
                preco = ".$precoProd."
            WHERE ".$where
        );
        if (isset($request->usu)) $nome = $request->usu;
        $this->log_inserir2("E", "maquinas_produtos", $where, $nome, true);
    }

    public function validar_app(Request $request) {
        return sizeof(
            DB::table("pessoas")
                ->where("cpf", $request->cpf)
                ->where("senha", $request->senha)
                ->where("lixeira", 0)
                ->get()
        ) ? 1 : 0;
    }

    public function ver_pessoa(Request $request) {
        return json_encode(
            DB::table("pessoas")
                ->where("cpf", $request->cpf)
                ->first()
        );
    }

    public function produtos_por_pessoa(Request $request) {
        $id_pessoa = DB::table("pessoas")->where("cpf", $request->cpf)->value("id");
        return json_encode(array_merge(
            $this->produtos_por_pessoa_main($id_pessoa, true),
            $this->produtos_por_pessoa_main($id_pessoa, false)
        ));
    }

    public function retirar(Request $request) {
        $resultado = new \stdClass;
        $cont = 0;
        $excluir = array();
        $produtos_ids = array();
        $produtos_refer = array();
        while (isset($request[$cont]["id_atribuicao"])) {
            $retirada = $request[$cont];
            $atribuicao = Atribuicoes::find($retirada["id_atribuicao"]);
            if ($atribuicao == null) {
                $resultado->code = 404;
                $resultado->msg = "Atribuição não encontrada";
                return json_encode($resultado);
            }
            $maquinas = DB::table("valores")
                            ->where("seq", $retirada["id_maquina"])
                            ->where("alias", "maquinas")
                            ->get();
            if (!sizeof($maquinas)) {
                $resultado->code = 404;
                $resultado->msg = "Máquina não encontrada";
                return json_encode($resultado);
            }
            $comodato = DB::table("comodatos")
                            ->select("id")
                            ->where("id_maquina", $maquinas[0]->id)
                            ->whereRaw("inicio <= CURDATE()")
                            ->whereRaw("fim >= CURDATE()")
                            ->get();
            if (!sizeof($comodato)) {
                $resultado->code = 404;
                $resultado->msg = "Máquina não comodatada para nenhuma empresa";
                return json_encode($resultado);
            }
            // if (!isset($retirada["id_supervisor"]) && !$this->retirada_consultar($retirada["id_atribuicao"], $retirada["qtd"])) {
            //     $resultado->code = 401;
            //     $resultado->msg = "Essa quantidade de produtos não é permitida para essa pessoa";
            //     return json_encode($resultado);
            // }
            if (floatval($retirada["qtd"]) > $this->retorna_saldo_mp($maquinas[0]->id, $retirada["id_produto"])) {
                $resultado->code = 500;
                $resultado->msg = "Essa quantidade de produtos não está disponível em estoque";
                return json_encode($resultado);
            }
            $salvar = array(
                "id_pessoa" => $retirada["id_pessoa"],
                "id_produto" => $retirada["id_produto"],
                "id_atribuicao" => $retirada["id_atribuicao"],
                "id_comodato" => $comodato[0]->id,
                "qtd" => $retirada["qtd"],
                "data" => date("Y-m-d")
            );
            if (isset($retirada["id_supervisor"])) {
                $salvar += [
                    "id_supervisor" => $retirada["id_supervisor"],
                    "obs" => $retirada["obs"]
                ];
            }
            if (isset($retirada["biometria_ou_senha"])) $salvar += ["biometria_ou_senha" => $retirada["biometria_ou_senha"]];
            $this->retirada_salvar($salvar);
            $linha = new Estoque;
            $linha->es = "S";
            $linha->descr = "RETIRADA";
            $linha->qtd = $retirada["qtd"];
            $linha->id_mp = DB::table("maquinas_produtos")
                                ->where("id_produto", $retirada["id_produto"])
                                ->where("id_maquina", $maquinas[0]->id)
                                ->value("id");
            $linha->save();
            array_push($excluir, $this->log_inserir("C", "estoque", $linha->id, true));
            array_push($produtos_ids, intval($retirada["id_produto"]));
            array_push($produtos_refer, strval(
                DB::table("produtos")
                    ->selectRaw("IFNULL(referencia, '') AS referencia")
                    ->where("id", $retirada["id_produto"])
                    ->value("referencia")
            ));
            $cont++;
        }
        
        $consulta = $this->info_atb($retirada["id_pessoa"], true, false);
        foreach ($consulta as $linha) {
            if (
                ($linha->produto_ou_referencia_chave == "R" && !in_array($linha->chave, $produtos_refer)) ||
                ($linha->produto_ou_referencia_chave == "P" && !in_array(intval($linha->chave), $produtos_ids))
            ) {
                foreach ($excluir as $aux) {
                    Estoque::find($aux->fk)->delete();
                    Log::find($aux->id)->delete();
                }
                $msg = "Há um produto obrigatório que não foi retirado: ";
                if ($linha->produto_ou_referencia_chave == "R") $msg .= "referência ";
                $msg .= $linha->nome;
                $resultado->code = 400;
                $resultado->msg = $msg; 
                return json_encode($resultado);
            }
        }

        $resultado->code = 201;
        $resultado->msg = "Sucesso";
        return json_encode($resultado);
    }

    public function validar_spv(Request $request) {
        return $this->supervisor_consultar($request);
    }

    public function marcar_gerou_pedido(Request $request) {
        foreach ($request->ids as $id) {
            $retirada = Retiradas::firstOrNew(["id" => $id]);
            $retirada->gerou_pedido = "S";
            $retirada->numero_ped = $request->numped;
            $retirada->save();
        }
        return "salvou";
    }

    public function associar_empresa(Request $request) {
        $empresa = Empresas::firstOrNew(["id" => $request->idemp]);
        $empresa->cod_externo = $request->cod_cli;
        $empresa->save();
        return $empresa->id;
    }

    public function pessoas_com_foto() {
        return json_encode(
            DB::table("pessoas")
                ->select(
                    "id",
                    "nome",
                    "foto64",
                    "cpf"
                )
                ->whereNotNull("foto64")
                ->get()
        );
    }

    public function biometria(Request $request) {
        $pessoa = Pessoas::find($request->id);
        if ($pessoa == null) return [];
        $pessoa->biometria = $request->biometria;
        $pessoa->save();
        $this->log_inserir("E", "pessoas", $pessoa->id, true);
        return 200;
    }

    public function validar_biometria(Request $request) {
        $pessoa = DB::table("pessoas")->where("biometria", $request->biometria)->value("id");
        if ($pessoa == null) return 0;
        return $pessoa;
    }
}