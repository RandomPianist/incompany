<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Pessoas;

class DashboardController extends ControllerKX {
    private function minhas_maquinas() {
        return DB::table("comodatos")
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
                    ->whereRaw("comodatos.inicio <= CURDATE()")
                    ->whereRaw("comodatos.fim >= CURDATE()");
    }

    private function consulta($select, $where, $groupby) {
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
                        $this->minhas_maquinas(),
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

    private function ultimas_retiradas_main($where) {
        $ultimas_retiradas = DB::table("pessoas")
                                ->select(
                                    "pessoas.id",
                                    "pessoas.foto",
                                    "pessoas.nome"
                                )
                                ->joinsub(
                                    DB::table("retiradas")
                                        ->select("id_pessoa")
                                        ->whereRaw("MONTH(data) = MONTH(CURDATE())")
                                        ->whereRaw("YEAR(data) = YEAR(CURDATE())")
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

    private function retiradas_por_setor_main($where) {
        return DB::table("retiradas")
                    ->select(
                        "setores.id",
                        "setores.descr",
                        DB::raw("SUM(qtd) AS retirados")
                    )
                    ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                    ->join("setores", "setores.id", "pessoas.id_setor")
                    ->whereRaw("MONTH(data) = MONTH(CURDATE())")
                    ->whereRaw("YEAR(data) = YEAR(CURDATE())")
                    ->where("setores.lixeira", 0)
                    ->whereRaw($where)
                    ->groupby(
                        "setores.id",
                        "setores.descr"
                    )
                    ->get();
    }
    
    private function produtos_main($id_pessoa) {
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
        ");
    }

    public function produtos($id_pessoa) {
        return json_encode($this->produtos_main($id_pessoa));
    }

    public function pagina() {
        $where = $this->obter_where(Auth::user()->id_pessoa);
        $pessoas = $this->consulta("
            pessoas.id,
            pessoas.nome,
            pessoas.foto,
            SUM(atribuicoes.qtd) AS total
        ", $where, "
            pessoas.id,
            pessoas.nome,
            pessoas.foto 
        ");
        foreach ($pessoas as $pessoa) {
            $total = 0;
            $aux = $this->produtos_main($pessoa->id);
            if ($aux !== null) {
                foreach ($aux as $linha) $total += $linha->qtd;
                $pessoa->total = number_format($total, 0);
            }
            $pessoa->total = number_format($pessoa->total, 0);
            $pessoa->foto = asset("storage/".$pessoa->foto);
        }
        $ultimas_retiradas = $this->ultimas_retiradas_main($where);
        $retiradas_por_setor = $this->retiradas_por_setor_main($where);
        $total = 0;
        foreach ($retiradas_por_setor as $rps) $total += floatval($rps->retirados);
        $minhas_maquinas = DB::table("valores")
                                ->select(
                                    "id",
                                    "descr"
                                )
                                ->whereIn(
                                    "id",
                                    $this->minhas_maquinas()
                                         ->where("id_pessoa", Auth::user()->id_pessoa)
                                         ->pluck("id_maquina")
                                         ->toArray()
                                )
                                ->get();
        $ranking = DB::table("retiradas")
                        ->select(
                            "pessoas.id",
                            "pessoas.nome",
                            "pessoas.foto",
                            DB::raw("SUM(qtd) AS retirados")
                        )
                        ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                        ->whereRaw($this->obter_where(Auth::user()->id_pessoa))
                        ->whereRaw("MONTH(data) = MONTH(CURDATE())")
                        ->whereRaw("YEAR(data) = YEAR(CURDATE())")
                        ->groupby(
                            "pessoas.id",
                            "pessoas.nome",
                            "pessoas.foto"
                        )
                        ->orderby("retirados", "desc")
                        ->orderby("pessoas.nome")
                        ->get();
        return view("dashboard", compact("pessoas", "ultimas_retiradas", "retiradas_por_setor", "total", "minhas_maquinas", "ranking"));
    }

    public function retiradas_por_setor($id_pessoa) {
        return json_encode($this->retiradas_por_setor_main($this->obter_where($id_pessoa)));
    }

    public function retiradas_em_atraso($id_pessoa) {
        $where = $this->obter_where($id_pessoa);
        $pessoas = $this->consulta("
            pessoas.id,
            pessoas.nome,
            pessoas.foto,
            SUM(atribuicoes.qtd) AS total
        ", $where, "
            pessoas.id,
            pessoas.nome,
            pessoas.foto 
        ");
        foreach ($pessoas as $pessoa) {
            $pessoa->foto = asset("storage/".$pessoa->foto);
            $pessoa->total = number_format($pessoa->total, 0);
        }
        return json_encode($pessoas);
    }

    public function ultimas_retiradas($id_pessoa) {
        return json_encode($this->ultimas_retiradas_main($this->obter_where($id_pessoa)));
    }

    public function ultimas_retiradas_prod($id_pessoa) {
        return json_encode(
            DB::table("retiradas")
                ->select(
                    DB::raw("
                        CASE
                            WHEN atribuicoes.produto_ou_referencia_chave = 'P' THEN produtos.descr
                            ELSE produtos.referencia
                        END AS produto
                    "),
                    "retiradas.qtd"
                )
                ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                ->join("produtos", "produtos.id", "retiradas.id_produto")
                ->where("retiradas.id_pessoa", $id_pessoa)
                ->whereRaw("MONTH(retiradas.data) = MONTH(CURDATE())")
                ->whereRaw("YEAR(retiradas.data) = YEAR(CURDATE())")
                ->get()
        );
    }
}