<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Empresas;
use App\Models\Valores;
use App\Models\Produtos;

class RelatoriosController extends ControllerKX {
    private function maquinas_periodo($inicio, $fim) {
        $where = "";
        if ($inicio) $where .= "('".$inicio."' BETWEEN comodatos.inicio AND comodatos.fim)";
        if ($fim) {
            if ($where) $where .= " OR ";
            $where .= "('".$fim."' BETWEEN comodatos.inicio AND comodatos.fim)";
        }
        $where = $where ? "(".$where.")" : "1";
        return DB::table("comodatos")
                    ->selectRaw("DISTINCTROW comodatos.id_maquina")
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
                    ->whereRaw($where)
                    ->where("minhas_empresas.id_empresa", Pessoas::find(Auth::user()->id_pessoa)->id_empresa)                    
                    ->pluck("comodatos.id_maquina")
                    ->toArray();
    }

    private function consultar_maquina(Request $request) {
        return ((!sizeof(
            DB::table("valores")
                ->where("id", $request->id_maquina)
                ->where("descr", $request->maquina)
                ->where("lixeira", 0)
                ->get()
        ) && trim($request->maquina)) || (trim($request->id_maquina) && !trim($request->maquina)));
    }

    private function consultar_empresa(Request $request) {
        return ($this->empresa_consultar($request) && trim($request->empresa)) || (trim($request->id_empresa) && !trim($request->empresa));
    }

    private function consultar_pessoa(Request $request, $considerar_lixeira) {
        return (!sizeof(
            DB::table("pessoas")
                ->where("id", $request->id_pessoa)
                ->where("nome", $request->pessoa)
                ->where(function($sql) use($considerar_lixeira) {
                    if ($considerar_lixeira) $sql->where("lixeira", 0);
                })
                ->get()
        ) && trim($request->pessoa)) || (!trim($request->pessoa) && trim($request->id_pessoa));
    }

    private function comum($select) {
        return DB::table("comodatos")
                    ->join("valores", "valores.id", "comodatos.id_maquina")
                    ->join("empresas", "empresas.id", "comodatos.id_empresa")
                    ->select(DB::raw($select))
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "empresas.id"))
                    ->where("valores.lixeira", 0)
                    ->where("empresas.lixeira", 0);
    }

    private function bilateral_construtor(Request $request, $grupo) {
        $filtro = array();
        if ($request->id_empresa) array_push($filtro, "id_empresa = ".$request->id_empresa);
        if ($request->id_maquina) array_push($filtro, "id_maquina = ".$request->id_maquina);
        $filtro = join(" AND ", $filtro);
        if (!$filtro) $filtro = "1";
        return collect(
            $this->comum("
                empresas.nome_fantasia AS col1,
                valores.descr AS col2
            ")->whereRaw($filtro."
                AND CURDATE() >= inicio
                AND CURDATE() < fim
            ")->orderby("valores.descr")->get()
        )->groupBy($grupo);
    }

    private function maquinas_por_empresa(Request $request) {
        $resultado = $this->bilateral_construtor($request, "col1")->map(function($itens) {
            return [
                "col1" => $itens[0]->col1,
                "col2" => $itens->map(function($col2) {
                    return $col2->col2;
                })->values()->all()
            ];
        })->sortBy("col1")->values()->all();
        $criterios = array();
        if ($request->id_maquina) array_push($criterios, "Máquina: ".$request->maquina);
        if ($request->id_empresa) array_push($criterios, "Empresa: ".$request->empresa);
        $criterios = join(" | ", $criterios);
        $titulo = "Máquinas por empresa";
        return sizeof($resultado) ? view("reports/bilateral", compact("resultado", "criterios", "titulo")) : view("nada");
    }

    private function empresas_por_maquina(Request $request) {
        $resultado = $this->bilateral_construtor($request, "col2")->map(function($itens) {
            return [
                "col1" => $itens[0]->col2,
                "col2" => $itens->map(function($col2) {
                    return $col2->col1;
                })->values()->all()
            ];
        })->sortBy("col1")->values()->all();
        $criterios = array();
        if ($request->id_maquina) array_push($criterios, "Máquina: ".$request->maquina);
        if ($request->id_empresa) array_push($criterios, "Empresa: ".$request->empresa);
        $criterios = join(" | ", $criterios);
        $titulo = "Empresas por máquina";
        return sizeof($resultado) ? view("reports/bilateral", compact("resultado", "criterios", "titulo")) : view("nada");
    }

    private function controleMain(Request $request) {
        $retorno = new \stdClass;
        $criterios = array();
        $retorno->resultado = collect(
            DB::table("retiradas")
                ->select(
                    "retiradas.id_pessoa",
                    "pessoas.nome",
                    "pessoas.cpf",
                    "pessoas.admissao",
                    "pessoas.funcao",
                    "setores.descr AS setor",
                    "produtos.descr AS produto",
                    "produtos.ca",
                    "empresas.razao_social",
                    "empresas.cnpj",
                    "produtos.validade_ca",
                    "retiradas.qtd",
                    DB::raw("DATE_FORMAT(retiradas.data, '%d/%m/%Y') AS data"),
                    DB::raw("IFNULL(CONCAT('Liberado por ', supervisor.nome, IFNULL(CONCAT(' - ', retiradas.observacao), '')), '') AS obs")
                )
                ->join("produtos", "produtos.id", "retiradas.id_produto")
                ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                ->leftjoin("comodatos", "comodatos.id", "retiradas.id_comodato")
                ->leftjoin("valores", "valores.id", "comodatos.id_maquina")
                ->leftjoin("pessoas AS supervisor", "supervisor.id", "retiradas.id_supervisor")
                ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                ->leftjoin("setores", "setores.id", "pessoas.id_setor")
                ->where(function($sql) use($request, &$criterios) {
                    if ($request->inicio || $request->fim) {
                        $periodo = "Período";
                        if ($request->inicio) {
                            $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
                            $sql->whereRaw("retiradas.data >= '".$inicio."'");
                            $periodo .= " de ".$request->inicio;
                        }
                        if ($request->fim) {
                            $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
                            $sql->whereRaw("retiradas.data <= '".$fim."'");
                            $periodo .= " até ".$request->fim;
                        }
                        array_push($criterios, $periodo);
                    }
                    $id_emp = intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
                    if ($request->id_pessoa) {
                        array_push($criterios, "Colaborador: ".Pessoas::find($request->id_pessoa)->nome);
                        $sql->where("retiradas.id_pessoa", $request->id_pessoa);
                    } else if ($id_emp) {
                        $sql->where(function($query) use($id_emp) {
                            $query->where("pessoas.id_empresa", $id_emp)
                                ->orWhere("empresas.id_matriz", $id_emp)
                                ->orWhere("empresas.id", $id_emp);
                        });
                    }
                    if ($request->consumo != "todos") {
                        $sql->where("produtos.consumo", $request->consumo == "epi" ? 0 : 1);
                        array_push($criterios, "Apenas ".($request->consumo == "epi" ? "EPI" : "produtos de consumo"));
                    }
                })
                ->orderby("retiradas.id")
                ->get()
        )->groupBy("id_pessoa")->map(function($itens) {
            return [
                "nome" => $itens[0]->nome,
                "cpf" => $itens[0]->cpf,
                "admissao" => $itens[0]->admissao,
                "funcao" => $itens[0]->funcao,
                "setor" => $itens[0]->setor,
                "empresa" => $itens[0]->razao_social,
                "cnpj" => $itens[0]->cnpj,
                "retiradas" => $itens->map(function($retirada) {
                    return [
                        "produto" => $retirada->produto,
                        "data" => $retirada->data,
                        "obs" => $retirada->obs,
                        "ca" => $retirada->ca,
                        "validade_ca" => $retirada->validade_ca,
                        "qtd" => $retirada->qtd,
                    ];
                })->values()->all()
            ];
        })->sortBy("nome")->values()->all();
        $retorno->criterios = join(" | ", $criterios);
        $retorno->cidade = "Barueri";
        $retorno->data_extenso = ucfirst(strftime("%d de %B de %Y"));
        return $retorno;
    }

    public function bilateral(Request $request) {
        if ($request->rel_grupo == "empresas-por-maquina") return $this->empresas_por_maquina($request);
        return $this->maquinas_por_empresa($request);
    }

    public function bilateral_consultar(Request $request) {
        $erro = "";
        if ($request->prioridade == "empresas") {
            if ($this->consultar_empresa($request)) $erro = "empresa";
            if (!$erro && $this->consultar_maquina($request)) $erro = "maquina";
        } else {
            if ($this->consultar_maquina($request)) $erro = "maquina";
            if (!$erro && $this->consultar_empresa($request)) $erro = "empresa";
        }
        return $erro;
    }

    public function comodatos() {
        $resultado = $this->comum("
            valores.descr AS maquina,
            empresas.nome_fantasia AS empresa,
            DATE_FORMAT(comodatos.inicio, '%d/%m/%Y') AS inicio,
            DATE_FORMAT(comodatos.fim, '%d/%m/%Y') AS fim
        ")->orderby("comodatos.inicio")->get();
        return sizeof($resultado) ? view("reports/comodatos", compact("resultado")) : view("nada");
    }

    public function sugestao(Request $request) {
        $criterios = array();
        array_push($criterios, "Período de ".$request->inicio." até ".$request->fim);
        $lm = $request->lm == "S";
        $tipo = $request->tipo;
        $dias = intval($request->dias);
        $dtinicio = Carbon::createFromFormat('d/m/Y', $request->inicio);
        $dtfim = Carbon::createFromFormat('d/m/Y', $request->fim);
        $diferenca = $dtinicio->diffInDays($dtfim);
        $inicio = $dtinicio->format('Y-m-d');
        $fim = $dtfim->format('Y-m-d');
        if (!$diferenca) $diferenca = 1;
        
        $resultado = collect(
            DB::table("maquinas_produtos AS mp")
                ->select(
                    // GRUPO
                    "mq.id AS id_maquina",
                    "mq.descr AS maquina",

                    // DETALHES
                    "produtos.id AS id_produto",
                    "produtos.descr AS produto",
                    "mp.minimo",

                    DB::raw("
                        SUM(
                            CASE
                                WHEN (estq.data >= cm.inicio AND estq.data < '".$inicio."') THEN
                                    CASE
                                        WHEN estq.es = 'E' THEN estq.qtd
                                        ELSE estq.qtd * -1
                                    END
                                ELSE 0
                            END
                        ) AS saldo_ant
                    "),
                    DB::raw("
                        SUM(
                            CASE
                                WHEN (estq.data >= '".$inicio."' AND estq.data <= '".$fim."') THEN
                                    CASE
                                        WHEN estq.es = 'E' THEN estq.qtd
                                        ELSE 0
                                    END
                                ELSE 0
                            END
                        ) AS entradas
                    "),
                    DB::raw("
                        SUM(
                            CASE
                                WHEN (estq.data >= '".$inicio."' AND estq.data <= '".$fim."' AND estq.origem = 'ERP') THEN
                                    CASE
                                        WHEN estq.es = 'S' THEN estq.qtd
                                        ELSE 0
                                    END
                                ELSE 0
                            END
                        ) AS saidas_avulsas
                    "),
                    DB::raw("
                        SUM(
                            CASE
                                WHEN (estq.data >= '".$inicio."' AND estq.data <= '".$fim."' AND estq.origem <> 'ERP') THEN
                                    CASE
                                        WHEN estq.es = 'S' THEN estq.qtd
                                        ELSE 0
                                    END
                                ELSE 0
                            END
                        ) AS retiradas
                    ")
                )
                ->join("produtos", "produtos.id", "mp.id_produto")
                ->joinSub(
                    DB::table("valores")
                        ->select(
                            "id",
                            "descr"
                        )
                        ->where(function($sql) use($request, $inicio, $fim, &$criterios) {
                            if (intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa)) $sql->whereIn("id", $this->maquinas_periodo($inicio, $fim));
                            if ($request->id_maquina) {
                                $maquina = Valores::find($request->id_maquina);
                                array_push($criterios, "Máquina: ".$maquina->descr);
                                $sql->where("id", $maquina->id);
                            }
                        })
                        ->where("lixeira", 0),
                    "mq",
                    "mq.id",
                    "mp.id_maquina"
                )
                ->joinSub(
                    DB::table("comodatos")
                        ->select(
                            "id_maquina",
                            "inicio"
                        )
                        ->whereRaw("('".$inicio."' BETWEEN comodatos.inicio AND comodatos.fim) OR ('".$fim."' BETWEEN comodatos.inicio AND comodatos.fim)"),
                    "cm",
                    "cm.id_maquina",
                    "mp.id_maquina"
                )
                ->joinSub(
                    DB::table("estoque")
                        ->select(
                            "estoque.id_mp",
                            "estoque.es",
                            "estoque.qtd",
                            "log.data",
                            "log.origem"
                        )
                        ->join("log", function($join) {
                            $join->on("log.fk", "estoque.id")
                                ->where("log.tabela", "estoque");
                        }),
                    "estq",
                    "estq.id_mp",
                    "mp.id"
                )
                ->where(function($sql) use($request, &$criterios) {
                    if ($request->id_produto) {
                        $produto = Produtos::find($request->id_produto);
                        array_push($criterios, "Produto: ".$produto->descr);
                        $sql->where("produtos.id", $produto->id);
                    }
                })
                ->where("produtos.lixeira", 0)
                ->groupby(
                    "mq.id",
                    "mq.descr",
                    "produtos.id",
                    "produtos.descr",
                    "mp.minimo"
                )
                ->get()
        )->groupBy("id_maquina")->map(function($maquinas) use($dias, $diferenca, $tipo, $lm) {
            $produtos = $maquinas->map(function($produto) use($dias, $diferenca, $tipo) {
                $saldo_ant = floatval($produto->saldo_ant);
                $entradas = floatval($produto->entradas);
                $saidas_avulsas = floatval($produto->saidas_avulsas);
                $retiradas = floatval($produto->retiradas);
                $minimo = floatval($produto->minimo);
                $saidas_totais = $saidas_avulsas + $retiradas;
                $saldo_res = $saldo_ant + $entradas - $saidas_totais;
                $giro = $retiradas / $diferenca;
                $sugeridos = $tipo == "G" ? (($giro * $dias) - $saldo_res) : ($minimo - $saldo_res);
                if ($sugeridos < 0) $sugeridos = 0;
                return [
                    "descr" => $produto->produto,
                    "saldo_ant" => number_format($saldo_ant, 0),
                    "entradas" => number_format($entradas, 0),
                    "saidas_avulsas" => number_format($saidas_avulsas, 0),
                    "retiradas" => number_format($retiradas, 0),
                    "minimo" => number_format($minimo, 0),
                    "saidas_totais" => number_format($saidas_totais, 0),
                    "saldo_res" => number_format($saldo_res, 0),
                    "giro" => number_format($giro, 2),
                    "sugeridos" => ceil($sugeridos)
                ];
            })->filter(function($produto) use ($lm) {
                return !$lm || intval($produto["sugeridos"]);
            })->sortBy("descr")->values();
            if ($produtos->isEmpty()) return null;
            return [
                "maquina" => [
                    "descr" => $maquinas[0]->maquina,
                    "produtos" => $produtos->all()
                ]
            ];
        })->filter()->sortBy(fn($m) => $m["maquina"]["descr"])->values()->all();
        if ($tipo == "G") array_push($criterios, "Compra sugerida para ".$dias." dia".($dias > 1 ? "s" : ""));
        if ($lm) array_push($criterios, "Apenas produtos cuja compra é sugerida");
        $criterios = join(" | ", $criterios);
        $mostrar_giro = $tipo == "G";
        if (sizeof($resultado)) return view("reports/saldo", compact("resultado", "criterios", "mostrar_giro"));
        return view("nada");
    }

    public function extrato(Request $request) {
        $criterios = array();
        $lm = $request->lm == "S";
        $resumo = $request->resumo == "S";
        $resultado = collect(
            DB::table("log")
                ->select(
                    // GRUPO 1
                    "valores.id AS id_maquina",
                    "valores.descr AS maquina",

                    // GRUPO 2
                    "produtos.id AS id_produto",
                    "produtos.descr AS produto",
                    DB::raw("IFNULL(tot.qtd, 0) AS saldo"),
                    DB::raw("estoque.preco"),

                    // DETALHES
                    DB::raw("CONCAT(DATE_FORMAT(log.data, '%d/%m/%Y'), CASE WHEN log.hms IS NOT NULL THEN CONCAT(' ', log.hms) ELSE '' END) AS data"),
                    "mp.minimo",
                    "estoque.es",
                    "estoque.descr AS estoque_descr",
                    DB::raw("
                        CASE
                            WHEN (es = 'E') THEN estoque.qtd
                            ELSE estoque.qtd * -1
                        END AS qtd
                    "),
                    DB::raw("
                        CASE
                            WHEN (es = 'E') THEN estoque.qtd
                            ELSE 0
                        END AS entradas
                    "),
                    DB::raw("
                        CASE
                            WHEN (es = 'S') THEN estoque.qtd
                            ELSE 0
                        END AS saidas
                    "),
                    DB::raw("IFNULL(log.nome, IFNULL(log.origem, 'DESCONHECIDO')) AS autor")
                )
                ->join("estoque", "estoque.id", "log.fk")
                ->join("maquinas_produtos AS mp", "mp.id", "estoque.id_mp")
                ->join("produtos", "produtos.id", "mp.id_produto")
                ->join("valores", "valores.id", "mp.id_maquina")
                ->leftjoin("pessoas", "pessoas.id", "log.id_pessoa")
                ->leftjoinsub(
                    DB::table("estoque")
                        ->select(
                            "id_mp",
                            DB::raw($request->inicio ? "
                                SUM(CASE
                                    WHEN (es = 'E') THEN qtd
                                    ELSE qtd * -1
                                END) AS qtd
                            " : "0 AS qtd")
                        )
                        ->where(function($sql) use($request) {
                            if ($request->inicio){
                                $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
                                $sql->whereRaw("DATE(created_at) < '".$inicio."'");
                            }
                        })
                        ->groupby("id_mp"),
                    "tot", "tot.id_mp", "mp.id"
                )
                ->where(function($sql) use($request, &$criterios) {
                    $inicio = "";
                    $fim = "";
                    if ($request->inicio) $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
                    if ($request->fim) $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
                    
                    if ($request->inicio || $request->fim) {
                        $periodo = "Período";
                        if ($request->inicio) {
                            $sql->whereRaw("log.data >= '".$inicio."'");
                            $periodo .= " de ".$request->inicio;
                        }
                        if ($request->fim) {
                            $sql->whereRaw("log.data <= '".$fim."'");
                            $periodo .= " até ".$request->fim;
                        }
                        array_push($criterios, $periodo);
                    }
                    if ($request->id_maquina) {
                        $maquina = Valores::find($request->id_maquina);
                        array_push($criterios, "Máquina: ".$maquina->descr);
                        $sql->where("mp.id_maquina", $maquina->id);
                    }
                    if ($request->id_produto) {
                        $produto = Produtos::find($request->id_produto);
                        array_push($criterios, "Produto: ".$produto->descr);
                        $sql->where("mp.id_produto", $produto->id);
                    }
                    if (intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa)) $sql->whereIn("mp.id_maquina", $this->maquinas_periodo($inicio, $fim));
                })
                ->where("log.tabela", "estoque")
                ->where("produtos.lixeira", 0)
                ->where("valores.lixeira", 0)
                ->orderby("log.data")
                ->get()
        )->groupBy("id_maquina")->map(function($itens1) use($request) {
            return [
                "maquina" => [
                    "descr" => $itens1[0]->maquina,
                    "produtos" => collect($itens1)->groupBy("id_produto")->map(function($itens2) use($request) {
                        $sugeridos = 0;
                        $giro = 0;
                        $minimo = intval($itens2[0]->minimo);
                        $saldo_ant = intval($itens2[0]->saldo);
                        $saldo_res = $saldo_ant + $itens2->sum("qtd");
                        if ($request->tipo == "G" && $request->resumo == "S") {
                            $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio);
                            $fim = Carbon::createFromFormat('d/m/Y', $request->fim);
                            $diferenca = $inicio->diffInDays($fim);
                            if (!$diferenca) $diferenca = 1;
                            $giro = $itens2->sum("saidas") / $diferenca;
                            $sugeridos = intval(($giro * intval($request->dias)) - $saldo_res);
                        } else $sugeridos = $minimo - $saldo_res;
                        if ($sugeridos < 0) $sugeridos = 0;
                        return [
                            "descr" => $itens2[0]->produto,
                            "preco" => $itens2[0]->preco,
                            "saldo_ant" => $saldo_ant,
                            "saldo_res" => $saldo_res,
                            "entradas" => $itens2->sum("entradas"),
                            "saidas" => $itens2->sum("saidas"),
                            "sugeridos" => $sugeridos,
                            "minimo" => ($request->tipo == "G" && $request->resumo == "S") ? number_format($giro, 2) : $minimo,
                            "movimentacao" => $itens2->map(function($movimento) {
                                $qtd = floatval($movimento->qtd);
                                return [
                                    "data" => $movimento->data,
                                    "es" => $movimento->es,
                                    "descr" => $movimento->estoque_descr,
                                    "qtd" => ($qtd < 0 ? ($qtd * -1) : $qtd),
                                    "autor" => $movimento->autor
                                ];
                            })->values()->all()
                        ];
                    })->sortBy("descr")->values()->all()
                ]
            ];
        })->sortBy("descr")->values()->all();
        if ($resumo) {
            if ($request->tipo == "G") array_push($criterios, "Compra sugerida para ".$request->dias." dia".(intval($request->dias) > 1 ? "s" : ""));
            if ($lm) array_push($criterios, "Apenas produtos cuja compra é sugerida");
        }
        $criterios = join(" | ", $criterios);
        $mostrar_giro = $request->tipo == "G";
        if (sizeof($resultado)) return view("reports/".($resumo ? "saldo" : "extrato"), compact("resultado", "lm", "criterios", "mostrar_giro"));
        return view("nada");
    }

    public function extrato_consultar(Request $request) {
        if ($this->consultar_maquina($request)) return "maquina";
        if (((trim($request->produto) && !sizeof(
            DB::table("produtos")
                ->where("id", $request->id_produto)
                ->where("descr", $request->produto)
                ->where("lixeira", 0)
                ->get()
        )) || (trim($request->id_produto) && !trim($request->produto)))) return "produto";
        return "";
    }

    public function controle(Request $request) {
        $principal = $this->controleMain($request);
        $resultado = $principal->resultado;
        $criterios = $principal->criterios;
        $cidade = $principal->cidade;
        $data_extenso = $principal->data_extenso;
        return sizeof($resultado) ? view("reports/controle", compact("resultado", "criterios", "cidade", "data_extenso")) : view("nada");
    }

    public function controle_consultar(Request $request) {
        return $this->consultar_pessoa($request, true) ? "erro" : "";
    }

    public function controle_existe(Request $request) {
        return sizeof($this->controleMain($request)->resultado) ? "1" : "0";
    }

    public function controle_pessoas() {
        return json_encode(
            DB::table("pessoas")
                ->where(function($sql) {
                    $id_emp = intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
                    if ($id_emp) {
                        $sql->where(function($query) use($id_emp) {
                            $query->where(function($query2) use($id_emp) {
                                $query2->where("id_empresa", Empresas::find($id_emp)->id_matriz);
                            })->orWhere("id_empresa", $id_emp);
                        })->orWhere(function($query) use($id_emp) {
                            $query->whereIn("id_empresa", DB::table("empresas")->where("id_matriz", $id_emp)->pluck("id")->toArray());
                        });
                    }
                })
                ->pluck("id")
        );
    }

    public function retiradas(Request $request) {
        $criterios = array();
        $qtd_total = 0;
        $val_total = 0;
        $resultado = collect(
            DB::table("retiradas")
                ->select(
                    // GRUPO
                    "retiradas.id_pessoa",
                    "pessoas.id_setor",
                    "setores.descr AS setor",

                    // DETALHES
                    DB::raw("DATE_FORMAT(retiradas.data, '%d/%m/%Y') AS data"),
                    "produtos.descr AS produto",
                    "pessoas.nome",
                    DB::raw("SUM(retiradas.qtd) AS qtd"),
                    DB::raw("SUM(retiradas.preco) AS valor")
                )
                ->join("pessoas", function($join) {
                    $join->on(function($sql) {
                        $sql->on("pessoas.id", "retiradas.id_pessoa")
                            ->where("pessoas.id_empresa", 0);
                    })->orOn(function($sql) {
                        $sql->on("pessoas.id", "retiradas.id_pessoa")
                            ->on("pessoas.id_empresa", "retiradas.id_empresa");
                    });
                })
                ->join("setores", "setores.id", "pessoas.id_setor")
                ->join("produtos", "produtos.id", "retiradas.id_produto")
                ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                ->leftjoin("comodatos", "comodatos.id", "retiradas.id_comodato")
                ->leftjoin("maquinas_produtos AS mp", function($join) {
                    $join->on("mp.id_produto", "produtos.id")
                         ->on("mp.id_maquina", "comodatos.id_maquina");
                })
                ->where(function($sql) use($request, &$criterios) {
                    $inicio = "";
                    $fim = "";
                    if ($request->inicio) $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
                    if ($request->fim) $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
                    
                    if ($request->inicio || $request->fim) {
                        $periodo = "Período";
                        if ($request->inicio) {
                            $sql->whereRaw("retiradas.data >= '".$inicio."'");
                            $periodo .= " de ".$request->inicio;
                        }
                        if ($request->fim) {
                            $sql->whereRaw("retiradas.data <= '".$fim."'");
                            $periodo .= " até ".$request->fim;
                        }
                        array_push($criterios, $periodo);
                    }
                    if ($request->id_pessoa) {
                        array_push($criterios, "Colaborador: ".Pessoas::find($request->id_pessoa)->nome);
                        $sql->where("pessoas.id", $request->id_pessoa);
                    }
                    if ($request->id_setor) {
                        array_push($criterios, "Centro de custo: ".$request->setor);
                        $sql->where("setores.id", $request->id_setor);
                    }
                    if ($request->id_empresa) {
                        $sql->where(function($query) use($request) {
                            $query->where("empresas.id", $request->id_empresa)
                                ->orWhere("empresas.id_matriz", $request->id_empresa);
                        });
                        $empresa = Empresas::find($request->id_empresa)->razao_social;
                        if (sizeof(
                            DB::table("empresas")
                                ->where("id_matriz", $request->id_empresa)
                                ->get()
                        )) $empresa .= " e filiais";
                        array_push($criterios, "Empresa: ".$empresa);
                    }
                    if ($request->consumo != "todos") {
                        $sql->where("produtos.consumo", $request->consumo == "epi" ? 0 : 1);
                        array_push($criterios, "Apenas ".($request->consumo == "epi" ? "EPI" : "produtos de consumo"));
                    }
                    if ($request->tipo_colab != "todos") $sql->where("pessoas.lixeira", $request->tipo_colab == "ativos" ? 0 : 1);

                    if (intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa)) {
                        $sql->where(function($where) use($inicio, $fim) {
                            $where->where(function($w) use($inicio, $fim) {
                                $w->whereIn("comodatos.id_maquina", $this->maquinas_periodo($inicio, $fim));
                            })->orWhere(function($w) {
                                $w->whereNull("comodatos.id_maquina");
                            });
                        });
                    }
                })
                ->groupby(
                    "retiradas.id_pessoa",
                    "pessoas.id_setor",
                    "setores.descr",
                    "retiradas.data",
                    "produtos.descr",
                    "pessoas.nome"
                )
                ->orderby("retiradas.data")
                ->get()
        )->groupBy("id_".$request->rel_grupo)->map(function($itens) use($request, &$qtd_total, &$val_total) {
            $qtd_total += $itens->sum("qtd");
            $val_total += $itens->sum("valor");
            return [
                "grupo" => $request->rel_grupo == "pessoa" ? $itens[0]->nome : $itens[0]->setor,
                "total_valor" => $itens->sum("valor"),
                "total_qtd" => $itens->sum("qtd"),
                "retiradas" => $itens->map(function($retirada) {
                    return [
                        "data" => $retirada->data,
                        "produto" => $retirada->produto,
                        "pessoa" => $retirada->nome,
                        "qtd" => $retirada->qtd,
                        "valor" => $retirada->valor
                    ];
                })->values()->all()
            ];
        })->values()->all();
        $criterios = join(" | ", $criterios);
        $quebra = $request->rel_grupo;
        $titulo = $request->rel_grupo == "pessoa" ? "Consumo por colaborador" : "Consumo por setor";
        if ($request->json == "S") {
            $retorno = new \stdClass;
            $retorno->json = $resultado;
            $retorno->qtd_total = $qtd_total;
            $retorno->val_total = $val_total;
            return json_encode($retorno);
        }
        return sizeof($resultado) ? view("reports/retiradas".$request->tipo, compact("resultado", "criterios", "quebra", "val_total", "qtd_total", "titulo")) : view("nada");
    }

    public function retiradas_consultar(Request $request) {
        if ($this->consultar_empresa($request)) return "empresa";
        if ($this->consultar_pessoa($request, false)) return "pessoa";
        if ((!sizeof(
            DB::table("setores")
                ->where("id", $request->id_setor)
                ->where("descr", $request->setor)
                ->where("lixeira", 0)
                ->get()
        ) && trim($request->setor)) || (!trim($request->setor) && trim($request->id_setor))) return "setor";
        return "";
    }

    public function ranking(Request $request) {
        $qtd_total = 0;
        $criterios = array();
        $resultado = collect(
            DB::table("retiradas")
                    ->select(
                        "pessoas.id_setor",
                        "pessoas.id",
                        "pessoas.nome",
                        "setores.descr AS setor",
                        DB::raw("SUM(qtd) AS retirados")
                    )
                    ->join("pessoas", function($join) {
                        $join->on(function($sql) {
                            $sql->on("pessoas.id", "retiradas.id_pessoa")
                                ->where("pessoas.id_empresa", 0);
                        })->orOn(function($sql) {
                            $sql->on("pessoas.id", "retiradas.id_pessoa")
                                ->on("pessoas.id_empresa", "retiradas.id_empresa");
                        });
                    })
                    ->join("setores", "setores.id", "pessoas.id_setor")
                    ->where(function($sql) use($request, &$criterios) {
                        if ($request->inicio || $request->fim) {
                            $periodo = "Período";
                            if ($request->inicio) {
                                $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
                                $sql->whereRaw("retiradas.data >= '".$inicio."'");
                                $periodo .= " de ".$request->inicio;
                            }
                            if ($request->fim) {
                                $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
                                $sql->whereRaw("retiradas.data <= '".$fim."'");
                                $periodo .= " até ".$request->fim;
                            }
                            array_push($criterios, $periodo);
                        }
                    })
                    ->where("setores.lixeira", 0)
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa))
                    ->groupby(
                        "pessoas.id_setor",
                        "pessoas.id",
                        "pessoas.nome",
                        "setores.descr",
                    )
                    ->orderby("retirados", "DESC")
                    ->orderby("pessoas.nome")
                    ->get()
        )->groupBy("id_setor")->map(function($itens) use($request, &$qtd_total) {
            $qtd_total += $itens->sum("retirados");
            return [
                "setor" => $itens[0]->setor,
                "total_qtd" => $itens->sum("retirados"),
                "pessoas" => $itens->map(function($pessoa) {
                    return [
                        "nome" => $pessoa->nome,
                        "retirados" => $pessoa->retirados
                    ];
                })->values()->all()
            ];
        })->values()->all();
        $criterios = join(" | ", $criterios);
        return sizeof($resultado) ? view("reports/ranking", compact("resultado", "criterios", "qtd_total")) : view("nada");
    }
}
