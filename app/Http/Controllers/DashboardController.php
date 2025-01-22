<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Pessoas;

class DashboardController extends ControllerKX {
    private function consulta($select, $where, $groupby, $maquinas) {
        return DB::table("pessoas")
                    ->select(DB::raw($select))
                    ->join("atribuicoes", function($join) {
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
                    })
                    ->joinsub(
                        $maquinas,
                        "minhas_maquinas",
                        "minhas_maquinas.id_pessoa",
                        "pessoas.id"
                    )
                    ->joinsub(
                        DB::table("maquinas_produtos AS mp")
                            ->select(
                                "mp.id_produto",
                                "mp.id_maquina",
                                DB::raw("
                                    IFNULL(SUM(
                                        CASE
                                            WHEN estoque.es = 'E' THEN estoque.qtd
                                            ELSE estoque.qtd * -1
                                        END
                                    ), 0) AS quantidade
                                ")
                            )
                            ->leftjoin("estoque", "estoque.id_mp", "mp.id")
                            ->groupby(
                                "id_produto",
                                "id_maquina"
                            ),
                        "estq",
                        function($join) {
                            $join->on("estq.id_maquina", "minhas_maquinas.id_maquina")
                                 ->on("estq.id_produto", "produtos.id");
                        }
                    )
                    ->leftjoinsub(
                        DB::table("retiradas")
                            ->select(
                                "id_pessoa",
                                "id_atribuicao",
                                DB::raw("MAX(data) AS data")
                            )
                            ->groupby(
                                "id_pessoa",
                                "id_atribuicao"
                            ),
                            "ret",
                            function($join) {
                                $join->on("ret.id_pessoa", "pessoas.id")
                                     ->on("ret.id_atribuicao", "atribuicoes.id");
                            }
                    )
                    ->whereRaw("(ret.id_pessoa IS NULL OR (DATE_ADD(ret.data, INTERVAL atribuicoes.validade DAY) >= CURDATE()))")
                    ->whereRaw($where)
                    ->where("atribuicoes.obrigatorio", 1)
                    ->where("produtos.lixeira", 0)
                    ->where("atribuicoes.lixeira", 0)
                    ->groupby(DB::raw($groupby))
                    ->havingRaw("SUM(estq.quantidade) > ?", [0])
                    ->get();
    }

    private function produtos_main($id_pessoa, $maquinas) {
        return $this->consulta("
            produtos.id,
            atribuicoes.validade,
            CASE
                WHEN atribuicoes.qtd < SUM(estq.quantidade) THEN atribuicoes.qtd
                ELSE SUM(estq.quantidade)
            END AS qtd,
            CASE
                WHEN atribuicoes.produto_ou_referencia_chave = 'P' THEN produtos.descr
                ELSE produtos.referencia
            END AS produto
        ", "pessoas.id = ".$id_pessoa, "
            produtos.id,
            atribuicoes.validade,
            atribuicoes.qtd,
            CASE
                WHEN atribuicoes.produto_ou_referencia_chave = 'P' THEN produtos.descr
                ELSE produtos.referencia
            END
        ", $maquinas);
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
                                        ->select("id_pessoa")
                                        ->whereRaw("retiradas.data >= '".$inicio."'")
                                        ->whereRaw("retiradas.data <= '".$fim."'")
                                        ->groupby("id_pessoa"),
                                    "ret",
                                    "ret.id_pessoa",
                                    "pessoas.id"
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
                ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
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

    private function retiradas_em_atraso_main($where, $maquinas) {
        $atrasos = $this->consulta("
            pessoas.id,
            pessoas.nome,
            pessoas.foto,
            SUM(atribuicoes.qtd) AS total
        ", $where, "
            pessoas.id,
            pessoas.nome,
            pessoas.foto 
        ", $maquinas);
        foreach ($atrasos as $pessoa) {
            $total = 0;
            $aux = $this->produtos_main($pessoa->id, $maquinas);
            if ($aux !== null) {
                foreach ($aux as $linha) $total += $linha->qtd;
                $pessoa->total = number_format($total, 0);
            }
            $pessoa->total = number_format($pessoa->total, 0);
            $pessoa->foto = asset("storage/".$pessoa->foto);
        }
        return $atrasos;
    }

    public function iniciar() {
        return view("dashboard");
    }

    public function dados(Request $request) {
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $request->inicio;
        if (isset($request->fim)) $fim = $request->fim;
        $resultado = new \stdClass;

        $where = $this->obter_where(Auth::user()->id_pessoa);

        $maquinas = $this->minhas_maquinas($inicio, $fim);
        
        $atrasos = $this->retiradas_em_atraso_main($where, $maquinas);

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
                    ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
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

        $resultado->atrasos = $atrasos;
        $resultado->ultimasRetiradas = $this->ultimas_retiradas_main($where, $inicio, $fim);
        $resultado->retiradasPorSetor = $retiradas_por_setor;
        $resultado->ranking = $ranking;
        $resultado->maquinas = DB::table("valores")
                                ->select(
                                    "id",
                                    "descr"
                                )
                                ->whereIn(
                                    "id",
                                    $maquinas
                                         ->where("id_pessoa", Auth::user()->id_pessoa)
                                         ->pluck("id_maquina")
                                         ->toArray()
                                )
                                ->get();
        return json_encode($resultado);
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

    public function det_retiradas_por_setor(Request $request) {
        $id_setor = $request->id_setor;
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $request->inicio;
        if (isset($request->fim)) $fim = $request->fim;
        return DB::table("retiradas")
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
                    ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
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
                    ->get();
    }

    // API
    public function produtos_em_atraso($id_pessoa) {
        return json_encode($this->produtos_main($id_pessoa, $this->minhas_maquinas()));
    }

    public function ultimas_retiradas($id_pessoa) {
        return json_encode($this->ultimas_retiradas_main($this->obter_where($id_pessoa)));
    }

    public function retiradas_por_setor($id_pessoa) {
        return json_encode($this->retiradas_por_setor_main($this->obter_where($id_pessoa)));
    }

    public function retiradas_em_atraso($id_pessoa) {
        return json_encode($this->retiradas_em_atraso_main($this->obter_where($id_pessoa), $this->minhas_maquinas()));
    }
}