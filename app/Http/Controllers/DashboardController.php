<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Pessoas;

class DashboardController extends ControllerKX {
    private function formatar_data($data) {
        $arr = explode("-", $data);
        $resultado = $arr[0];
        for ($i = 1; $i < sizeof($arr); $i++) {
            $aux = "";
            if (strlen($arr[$i]) == 1) $aux = "0";
            $aux .= $arr[$i];
            $resultado .= "-".$aux;
        }
        return $resultado;
    }

    private function retorna_atrasos($resumo, $where) {
        $query = "SELECT";
        $query .= $resumo ? "
            pessoas.id,
            pessoas.nome,
            pessoas.foto,
            dashboard.qtd AS total

            FROM (
                SELECT
                    principal.id_pessoa,
                    ROUND(SUM(LEAST(principal.qtd, estq.qtd, estqgrp.qtd))) AS qtd
        " : "
            produtos.id,
            principal.validade,
            CASE
                WHEN principal.tipo = 'NORMAL' THEN ROUND(SUM(LEAST(principal.qtd, estq.qtd, estqgrp.qtd)))
                ELSE ROUND(SUM(LEAST(principal.qtd, estq.qtd, estqgrp.qtd)))
            END AS qtd,
            CASE
                WHEN principal.tipo = 'NORMAL' THEN
                    CASE
                        WHEN principal.produto_ou_referencia_chave = 'P' THEN produtos.descr
                        ELSE produtos.referencia
                    END
                ELSE
                    CASE
                        WHEN principal.produto_ou_referencia_chave = 'P' THEN produtosgrp.descr
                        ELSE produtosgrp.referencia
                    END
            END AS produto
        ";
        $query .= "
            FROM (
                SELECT
                    pessoas.id AS id_pessoa,
                    atribuicoes.qtd,
                    CONCAT('|', GROUP_CONCAT(DISTINCT produtos.id SEPARATOR '|'), '|') AS produtos,
                    atbgrp.produtos AS produtosgrp
        ";
        if (!$resumo) {
            $query .= ",
                CASE
                    WHEN (DATE_ADD(ret.data, INTERVAL atribuicoes.validade DAY) < CURDATE() OR ret.data IS NULL) THEN atribuicoes.validade
                    ELSE atbgrp.validade
                END AS validade,
                CASE
                    WHEN (DATE_ADD(ret.data, INTERVAL atribuicoes.validade DAY) < CURDATE() OR ret.data IS NULL) THEN 'NORMAL'
                    ELSE 'ASSOCIADO'
                END AS tipo,
                atribuicoes.produto_ou_referencia_chave
            ";
        }
        $query .= "
            FROM pessoas

            JOIN (
                SELECT atribuicoes_associadas.*
                FROM atribuicoes_associadas
                ".($resumo ? "
                    JOIN pessoas
                        ON pessoas.id = atribuicoes_associadas.id_pessoa
                    WHERE ".$where."
                " : "WHERE id_pessoa = ".$where)."
            ) AS aa ON aa.id_pessoa = pessoas.id

            JOIN atribuicoes
                ON atribuicoes.id = aa.id_atribuicao
                
            JOIN produtos
                ON (produtos.cod_externo = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'P')
                    OR (produtos.referencia = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'R')
                
            LEFT JOIN (
                SELECT
                    id_atribuicao,
                    id_pessoa,
                    MAX(data) AS data

                FROM retiradas

                WHERE id_supervisor IS NULL

                GROUP BY
                    id_atribuicao,
                    id_pessoa
            ) AS ret ON ret.id_atribuicao = atribuicoes.id AND ret.id_pessoa = pessoas.id

            LEFT JOIN (
                SELECT
                    tab.id_atribuicao,
                    tab.id_pessoa,
                    tab.associados,
                    CONCAT('|', GROUP_CONCAT(DISTINCT produtos.id SEPARATOR '|'), '|') AS produtos,
                    ".(!$resumo ? "MIN(atribuicoes.validade) AS validade," : "")."
                    MIN(ret.proxima_retirada) AS proxima_retirada
                
                FROM (
                    ".($resumo ? "SELECT aa.*" : "SELECT *")."
                    ".($resumo ? "FROM atribuicoes_associadas AS aa" : "FROM atribuicoes_associadas")."
                    ".($resumo ? "
                        JOIN pessoas
                            ON pessoas.id = aa.id_pessoa
                        WHERE ".$where."
                    " : "WHERE id_pessoa = ".$where)."
                ) AS tab
                
                JOIN atribuicoes
                    ON REPLACE(tab.associados, CONCAT('|', atribuicoes.id, '|'), '') <> tab.associados
                
                JOIN produtos
                    ON (produtos.cod_externo = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'P')
                        OR (produtos.referencia = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'R')

                LEFT JOIN (
                    SELECT
                        retiradas.id_atribuicao,
                        retiradas.id_pessoa,
                        DATE_ADD(MAX(retiradas.data), INTERVAL MIN(atribuicoes.validade) DAY) AS proxima_retirada

                    FROM retiradas

                    JOIN atribuicoes
                        ON atribuicoes.id = retiradas.id_atribuicao
                    
                    WHERE retiradas.id_supervisor IS NULL

                    GROUP BY
                        retiradas.id_atribuicao,
                        retiradas.id_pessoa
                ) AS ret ON ret.id_atribuicao = atribuicoes.id AND ret.id_pessoa = tab.id_pessoa

                GROUP BY
                    tab.id_atribuicao,
                    tab.id_pessoa,
                    tab.associados
            ) AS atbgrp ON atbgrp.id_atribuicao = atribuicoes.id AND atbgrp.id_pessoa = pessoas.id

            WHERE atribuicoes.obrigatorio = 1 AND ((DATE_ADD(ret.data, INTERVAL atribuicoes.validade DAY) < CURDATE() OR ret.data IS NULL) OR (atbgrp.proxima_retirada IS NULL OR (atbgrp.proxima_retirada < CURDATE())))

            GROUP BY
                pessoas.id,
                atribuicoes.qtd,
                atbgrp.produtos
        ";
        if (!$resumo) {
            $query .= ",
                atribuicoes.validade,
                ret.data,
                atbgrp.validade,
                atribuicoes.produto_ou_referencia_chave
            ";
        }
        $query .= ") AS principal
            JOIN (
                SELECT
                    minhas_empresas.id_pessoa,
                    comodatos.id_maquina

                FROM comodatos

                JOIN (
                    SELECT
                        id AS id_pessoa,
                        id_empresa
                    
                    FROM pessoas

                    UNION ALL (
                        SELECT
                            pessoas.id AS id_pessoa,
                            filiais.id AS id_empresa

                        FROM pessoas

                        JOIN empresas AS filiais
                            ON filiais.id_matriz = pessoas.id_empresa
                    )
                ) AS minhas_empresas ON minhas_empresas.id_empresa = comodatos.id_empresa

                WHERE ((DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01')) BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim))
            ) AS minhas_maquinas ON minhas_maquinas.id_pessoa = principal.id_pessoa

            JOIN vmp AS estq
                ON estq.id_maquina = minhas_maquinas.id_maquina AND REPLACE(principal.produtos, CONCAT('|', estq.id_produto, '|'), '') <> principal.produtos
        ";
        if (!$resumo) {
            $query .= "
                JOIN produtos
                    ON produtos.id = estq.id_produto
            ";
        }
        $query .= "
            LEFT JOIN vmp AS estqgrp
                ON estqgrp.id_maquina = minhas_maquinas.id_maquina AND REPLACE(principal.produtosgrp, CONCAT('|', estqgrp.id_produto, '|'), '') <> principal.produtosgrp
        ";
        $query .= !$resumo ? "
            LEFT JOIN produtos AS produtosgrp
                ON produtosgrp.id = estqgrp.id_produto

            GROUP BY
                produtos.id,
                principal.validade,
                principal.tipo,
                CASE
                    WHEN principal.tipo = 'NORMAL' THEN
                        CASE
                            WHEN principal.produto_ou_referencia_chave = 'P' THEN produtos.descr
                            ELSE produtos.referencia
                        END
                    ELSE
                        CASE
                            WHEN principal.produto_ou_referencia_chave = 'P' THEN produtosgrp.descr
                            ELSE produtosgrp.referencia
                        END
                END

            HAVING
                CASE
                    WHEN principal.tipo = 'NORMAL' THEN ROUND(SUM(LEAST(principal.qtd, estq.qtd)))
                    ELSE ROUND(SUM(LEAST(principal.qtd, estqgrp.qtd)))
                END > 0
        " : "
                GROUP BY principal.id_pessoa
            ) AS dashboard

            JOIN pessoas
                ON pessoas.id = dashboard.id_pessoa
                
            ORDER BY dashboard.qtd DESC
        ";
        return DB::select(DB::raw($query));
    }

    private function ultimas_retiradas_main($where, $inicio = "", $fim = "") {
        if (!$inicio) $inicio = date("Y-m")."-01";
        if (!$fim) $fim = date("Y-m-d");
        $ultimas_retiradas = DB::table("pessoas")
                                ->select(
                                    "pessoas.id",
                                    "pessoas.foto",
                                    "pessoas.nome"
                                )
                                ->joinsub(
                                    DB::table("retiradas")
                                        ->select(
                                            "id_pessoa",
                                            "id_empresa"
                                        )
                                        ->whereRaw("retiradas.data >= '".$inicio."'")
                                        ->whereRaw("retiradas.data <= '".$fim."'")
                                        ->groupby(
                                            "id_pessoa",
                                            "id_empresa"
                                        ),
                                    "ret",
                                    function($join) {
                                        $join->on("pessoas.id", "ret.id_pessoa")
                                             ->on("pessoas.id_empresa", "ret.id_empresa");
                                    }
                                )
                                ->whereRaw($where)
                                ->get();
        foreach ($ultimas_retiradas as $retirada) $retirada->foto = asset("storage/".$retirada->foto);
        return $ultimas_retiradas;
    }

    private function retiradas_por_setor_main($where, $inicio = "", $fim = "") {
        if (!$inicio) $inicio = date("Y-m")."-01";
        if (!$fim) $fim = date("Y-m-d");
        return collect(
            DB::table("retiradas")
                ->select(
                    "setores.id",
                    "setores.descr",
                    DB::raw("SUM(qtd) AS retirados"),
                    DB::raw("
                        CASE
                            WHEN mp.preco IS NOT NULL THEN (mp.preco * SUM(retiradas.qtd))
                            ELSE (produtos.preco * SUM(retiradas.qtd))
                        END AS valor
                    ")
                )
                ->join("pessoas", function($join) {
                    $join->on("pessoas.id", "retiradas.id_pessoa")
                         ->on("pessoas.id_empresa", "retiradas.id_empresa");
                })
                ->join("setores", "setores.id", "pessoas.id_setor")
                ->join("produtos", "produtos.id", "retiradas.id_produto")
                ->leftjoin("comodatos", "comodatos.id", "retiradas.id_comodato")
                ->leftjoin("maquinas_produtos AS mp", function($join) {
                    $join->on("mp.id_produto", "produtos.id")
                        ->on("mp.id_maquina", "comodatos.id_maquina");
                })
                ->whereRaw("retiradas.data >= '".$inicio."'")
                ->whereRaw("retiradas.data <= '".$fim."'")
                ->where("setores.lixeira", 0)
                ->whereRaw($where)
                ->groupby(
                    "setores.id",
                    "setores.descr",
                    "mp.preco",
                    "produtos.preco"
                )
                ->get()
        )->groupBy("id")->map(function($itens) {
            return [
                "id" => $itens[0]->id,
                "descr" => $itens[0]->descr,
                "retirados" => $itens->sum("retirados"),
                "valor" => $itens->sum("valor")
            ];
        })->values()->all();
    }

    private function retiradas_em_atraso_main($where) {
        $atrasos = $this->retorna_atrasos(true, $where);

        foreach ($atrasos as $pessoa) $pessoa->foto = asset("storage/".$pessoa->foto);
        return $atrasos;
    }

    public function iniciar() {
        return view("dashboard");
    }

    public function dados(Request $request) {
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $this->formatar_data($request->inicio);
        if (isset($request->fim)) $fim = $this->formatar_data($request->fim);
        $resultado = new \stdClass;

        $where = $this->obter_where(Auth::user()->id_pessoa);

        $retiradas_por_setor = new \stdClass;
        $aux = $this->retiradas_por_setor_main($where, $inicio, $fim);
        $total_val = 0;
        $total_qtd = 0;
        foreach ($aux as $rps) {
            $total_qtd += floatval($rps["retirados"]);
            $total_val += floatval($rps["valor"]);
        }
        $retiradas_por_setor->retiradas = $aux;
        $retiradas_por_setor->totalQtd = $total_qtd;
        $retiradas_por_setor->totalVal = $total_val;
        
        $ranking = DB::table("retiradas")
                    ->select(
                        "pessoas.id",
                        "pessoas.nome",
                        "pessoas.foto",
                        DB::raw("SUM(qtd) AS retirados")
                    )
                    ->join("pessoas", function($join) {
                        $join->on("pessoas.id", "retiradas.id_pessoa")
                             ->on("pessoas.id_empresa", "retiradas.id_empresa");
                    })
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa))
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->groupby(
                        "pessoas.id",
                        "pessoas.nome",
                        "pessoas.foto"
                    )
                    ->orderby("retirados", "desc")
                    ->orderby("pessoas.nome")
                    ->get();
        foreach ($ranking as $pessoa) $pessoa->foto = asset("storage/".$pessoa->foto);
        
        $resultado->atrasos = $inicio == date("Y-m")."-01" ? $this->retiradas_em_atraso_main($where) : [];
        $resultado->ultimasRetiradas = $inicio == date("Y-m")."-01" ? $this->ultimas_retiradas_main($where, $inicio, $fim) : [];
        $resultado->retiradasPorSetor = $retiradas_por_setor;
        $resultado->ranking = $ranking;
        $resultado->maquinas = DB::table("valores")
                                ->select(
                                    "id",
                                    "descr"
                                )
                                ->whereIn(
                                    "id",
                                    DB::table("comodatos")
                                        ->select(
                                            "minhas_empresas.id_pessoa",
                                            "comodatos.id_maquina"
                                        )
                                        ->joinsub(
                                            DB::table("pessoas")
                                                ->select(
                                                    "id AS id_pessoa",
                                                    "id_empresa"
                                                )
                                                ->unionAll(
                                                    DB::table("pessoas")
                                                        ->select(
                                                            "pessoas.id AS id_pessoa",
                                                            "filiais.id AS id_empresa"
                                                        )
                                                        ->join("empresas AS filiais", "filiais.id_matriz", "pessoas.id_empresa")
                                                ),
                                            "minhas_empresas",
                                            "minhas_empresas.id_empresa",
                                            "comodatos.id_empresa"
                                        )
                                        ->whereRaw("(('".$inicio."' BETWEEN comodatos.inicio AND comodatos.fim) OR ('".$fim."' BETWEEN comodatos.inicio AND comodatos.fim))")
                                        ->where("id_pessoa", Auth::user()->id_pessoa)
                                        ->pluck("id_maquina")
                                        ->toArray()
                                )
                                ->get();
        return json_encode($resultado);
    }

    public function det_retiradas_por_pessoa(Request $request) {
        $id_pessoa = $request->id_pessoa;
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $request->inicio;
        if (isset($request->fim)) $fim = $request->fim;
        return json_encode(
            collect(
                DB::table("retiradas")
                    ->select(
                        "produtos.id",
                        DB::raw("
                            CASE
                                WHEN atribuicoes.produto_ou_referencia_chave = 'P' THEN produtos.descr
                                ELSE produtos.referencia
                            END AS produto
                        "),
                        DB::raw("ROUND(SUM(retiradas.qtd)) AS qtd")
                    )
                    ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                    ->join("produtos", "produtos.id", "retiradas.id_produto")
                    ->where("retiradas.id_pessoa", $id_pessoa)
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->groupby(
                        "produtos.id",
                        DB::raw("
                            CASE
                                WHEN atribuicoes.produto_ou_referencia_chave = 'P' THEN produtos.descr
                                ELSE produtos.referencia
                            END
                        ")
                    )
                    ->get()
            )->sortByDesc("qtd")->values()->all()
        );
    }

    public function det_ultimas_retiradas(Request $request) {
        $id_pessoa = $request->id_pessoa;
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $request->inicio;
        if (isset($request->fim)) $fim = $request->fim;
        return json_encode(
            collect(
                DB::table("retiradas")
                    ->select(
                        "retiradas.id AS id_retirada",
                        "produtos.id",
                        DB::raw("
                            CASE
                                WHEN atribuicoes.produto_ou_referencia_chave = 'P' THEN produtos.descr
                                ELSE produtos.referencia
                            END AS produto
                        "),
                        DB::raw("ROUND(retiradas.qtd) AS qtd"),
                        DB::raw("DATE_FORMAT(retiradas.data, '%d/%m/%Y') AS data")
                    )
                    ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                    ->join("produtos", "produtos.id", "retiradas.id_produto")
                    ->where("retiradas.id_pessoa", $id_pessoa)
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->get()
            )->sortBy("id_retirada")->values()->all()
        );
    }

    public function det_retiradas_por_setor(Request $request) {
        $id_setor = $request->id_setor;
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $request->inicio;
        if (isset($request->fim)) $fim = $request->fim;
        return collect(
            DB::table("retiradas")
                    ->select(
                        "pessoas.id",
                        "pessoas.nome",
                        DB::raw("SUM(qtd) AS retirados"),
                        DB::raw("
                            CASE
                                WHEN mp.preco IS NOT NULL THEN (mp.preco * SUM(retiradas.qtd))
                                ELSE (produtos.preco * SUM(retiradas.qtd))
                            END AS valor
                        ")
                    )
                    ->join("pessoas", function($join) {
                        $join->on("pessoas.id", "retiradas.id_pessoa")
                             ->on("pessoas.id_empresa", "retiradas.id_empresa");
                    })
                    ->join("produtos", "produtos.id", "retiradas.id_produto")
                    ->leftjoin("comodatos", "comodatos.id", "retiradas.id_comodato")
                    ->leftjoin("maquinas_produtos AS mp", function($join) {
                        $join->on("mp.id_produto", "produtos.id")
                            ->on("mp.id_maquina", "comodatos.id_maquina");
                    })
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->where("pessoas.lixeira", 0)
                    ->where("pessoas.id_setor", $id_setor)
                    ->groupby(
                        "pessoas.id",
                        "pessoas.nome",
                        "mp.preco",
                        "produtos.preco"
                    )
                    ->get()
        )->groupby("id")->map(function($itens) {
            return [
                "id" => $itens[0]->id,
                "nome" => $itens[0]->nome,
                "retirados" => $itens->sum("retirados"),
                "valor" => $itens->sum("valor")
            ];
        })->values()->all();
    }

    // API
    public function produtos_em_atraso($id_pessoa) {
        return json_encode($this->retorna_atrasos(false, $id_pessoa));
    }

    public function ultimas_retiradas($id_pessoa) {
        return json_encode($this->ultimas_retiradas_main($this->obter_where($id_pessoa)));
    }

    public function retiradas_por_setor($id_pessoa) {
        return json_encode($this->retiradas_por_setor_main($this->obter_where($id_pessoa)));
    }

    public function retiradas_em_atraso($id_pessoa) {
        return json_encode($this->retiradas_em_atraso_main($this->obter_where($id_pessoa)));
    }
}
