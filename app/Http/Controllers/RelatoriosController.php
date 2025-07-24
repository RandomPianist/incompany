<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Empresas;

class RelatoriosController extends ControllerKX {
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

    private function consultar_pessoa(Request $request) {
        return (!sizeof(
            DB::table("pessoas")
                ->where("id", $request->id_pessoa)
                ->where("nome", $request->pessoa)
                ->where("lixeira", 0)
                ->get()
        ) && trim($request->pessoa)) || (!trim($request->pessoa) && trim($request->id_pessoa));
    }

    private function comum($select) {
        return DB::table("comodatos")
                    ->join("valores", "valores.id", "comodatos.id_maquina")
                    ->join("empresas", "empresas.id", "comodatos.id_empresa")
                    ->select(DB::raw($select))
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
                            $sql->whereDate("retiradas.data", ">=", $inicio);
                            $periodo .= " de ".$request->inicio;
                        }
                        if ($request->fim) {
                            $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
                            $sql->whereDate("retiradas.data", "<=", $fim);
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
                        "produto"     => $retirada->produto,
                        "data"        => $retirada->data,
                        "obs"         => $retirada->obs,
                        "ca"          => $retirada->ca,
                        "validade_ca" => $retirada->validade_ca,
                        "qtd"         => $retirada->qtd,
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
                    DB::raw("IFNULL(mp.preco, produtos.preco) AS preco"),

                    // DETALHES
                    DB::raw("DATE_FORMAT(log.created_at, '%d/%m/%Y %H:%i') AS data"),
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
                    DB::raw("
                        IFNULL(pessoas.nome, CONCAT(
                            'API',
                            IFNULL(CONCAT(' - ', log.nome), '')
                        )) AS autor
                    ")
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
                    if ($request->inicio || $request->fim) {
                        $periodo = "Período";
                        if ($request->inicio) {
                            $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
                            $sql->whereRaw("DATE(log.created_at) >= '".$inicio."'");
                            $periodo .= " de ".$request->inicio;
                        }
                        if ($request->fim) {
                            $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
                            $sql->whereRaw("DATE(log.created_at) <= '".$fim."'");
                            $periodo .= " até ".$request->fim;
                        }
                        array_push($criterios, $periodo);
                    }
                    if ($request->id_maquina) {
                        $maquina = DB::table("valores")
                                        ->where("id", $request->id_maquina)
                                        ->value("descr");
                        array_push($criterios, "Máquina: ".$maquina);
                        $sql->where("mp.id_maquina", $request->id_maquina);
                    }
                    if ($request->id_produto) {
                        $produto = DB::table("produtos")
                                        ->where("id", $request->id_produto)
                                        ->value("descr");
                        array_push($criterios, "Produto: ".$produto);
                        $sql->where("mp.id_produto", $request->id_produto);
                    }
                })
                ->where("log.tabela", "estoque")
                ->where("produtos.lixeira", 0)
                ->where("valores.lixeira", 0)
                ->orderby("log.id")
                ->get()
        )->groupBy("id_maquina")->map(function($itens1) {
            return [
                "maquina" => [
                    "descr" => $itens1[0]->maquina,
                    "produtos" => collect($itens1)->groupBy("id_produto")->map(function($itens2) use($request) {
                        $sugeridos = 0;
                        $minimo = 0;
                        $giro = 0;
                        if ($request->inicio && $request->fim) {
                            $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio);
                            $fim = Carbon::createFromFormat('d/m/Y', $request->fim);
                            $giro = floatval(
                                        DB::table("retiradas")
                                            ->select(DB::raw("IFNULL(SUM(qtd), 0) AS qtd"))
                                            ->leftjoin("comodatos", "comodatos.id", "retiradas.id_comodato")
                                            ->whereDate("retiradas.data", ">=", $inicio->format('Y-m-d'))
                                            ->whereDate("retiradas.data", "<=", $fim->format('Y-m-d'))
                                            ->where(function($sql) use($request) {
                                                if ($request->id_maquina) $sql->where("comodatos.id_maquina", $request->id_maquina);
                                            })->value("qtd")
                                        );
                            $sugeridos = intval((($giro / $inicio->diffInDays($fim)) * 30) - ($saldo_ant + $itens2->sum("qtd")));
                        } else {
                            $minimo = intval($itens2[0]->minimo);
                            $saldo_ant = intval($itens2[0]->saldo);
                            $saldo_res = $saldo_ant + $itens2->sum("qtd");
                            $sugeridos = $minimo - $saldo_res;
                            if ($sugeridos < 0) $sugeridos = 0;
                        }
                        return [
                            "descr" => $itens2[0]->produto,
                            "preco" => $itens2[0]->preco,
                            "saldo_ant" => $saldo_ant,
                            "saldo_res" => $saldo_res,
                            "entradas" => $itens2->sum("entradas"),
                            "saidas" => $itens2->sum("saidas"),
                            "sugeridos" => $sugeridos,
                            "minimo" => ($request->inicio && $request->fim) ? intval($giro) : $sugeridos,
                            "movimentacao" => $itens2->map(function($movimento) {
                                $qtd = floatval($movimento->qtd);
                                return [
                                    "data"  => $movimento->data,
                                    "es"    => $movimento->es,
                                    "descr" => $movimento->estoque_descr,
                                    "qtd"   => ($qtd < 0 ? ($qtd * -1) : $qtd),
                                    "autor" => $movimento->autor
                                ];
                            })->values()->all()
                        ];
                    })->sortBy("descr")->values()->all()
                ]
            ];
        })->sortBy("descr")->values()->all();
        if ($lm && $resumo) array_push($criterios, "Apenas produtos cuja compra é sugerida");
        $criterios = join(" | ", $criterios);
        $mostrar_giro = $request->inicio && $request->fim;
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
        return $this->consultar_pessoa($request) ? "erro" : "";
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
                    DB::raw("
                        CASE
                            WHEN mp.preco IS NOT NULL THEN (mp.preco * SUM(retiradas.qtd))
		                    ELSE (produtos.preco * SUM(retiradas.qtd))
                        END AS valor
                    ")
                )
                ->join("pessoas", function($join) {
                    $join->on(function($sql) {
                        $sql->on("pessoas.id", "retiradas.id_pessoa")
                            ->where("pessoas.id_empresa", 0);
                    })->orOn(function($sql) {
                        $join->on("pessoas.id", "retiradas.id_pessoa")
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
                    if ($request->inicio || $request->fim) {
                        $periodo = "Período";
                        if ($request->inicio) {
                            $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
                            $sql->whereDate("retiradas.data", ">=", $inicio);
                            $periodo .= " de ".$request->inicio;
                        }
                        if ($request->fim) {
                            $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
                            $sql->whereDate("retiradas.data", "<=", $fim);
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
                })
                ->groupby(
                    "retiradas.id_pessoa",
                    "pessoas.id_setor",
                    "setores.descr",
                    "retiradas.data",
                    "produtos.descr",
                    "pessoas.nome",
                    "mp.preco",
                    "produtos.preco"
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
        if ($this->consultar_pessoa($request)) return "pessoa";
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
                            $join->on("pessoas.id", "retiradas.id_pessoa")
                                ->on("pessoas.id_empresa", "retiradas.id_empresa");
                        });
                    })
                    ->join("setores", "setores.id", "pessoas.id_setor")
                    ->where(function($sql) use($request, &$criterios) {
                        if ($request->inicio || $request->fim) {
                            $periodo = "Período";
                            if ($request->inicio) {
                                $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
                                $sql->whereDate("retiradas.data", ">=", $inicio);
                                $periodo .= " de ".$request->inicio;
                            }
                            if ($request->fim) {
                                $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
                                $sql->whereDate("retiradas.data", "<=", $fim);
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
                    ->orderby("retirados", "desc")
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
