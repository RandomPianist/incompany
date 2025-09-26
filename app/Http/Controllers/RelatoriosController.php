<?php

namespace App\Http\Controllers;

use DB;
use PDF;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Empresas;
use App\Models\Maquinas;
use App\Models\Solicitacoes;
use App\Models\SolicitacoesProdutos;
use App\Models\Produtos;

class RelatoriosController extends Controller {
    private function consultar_empresa(Request $request) {
        return ($this->empresa_consultar($request) && trim($request->empresa)) || (trim($request->id_empresa) && !trim($request->empresa)); // App\Http\Controllers\Controller.php
    }

    private function consultar_pessoa(Request $request, $considerar_lixeira) {
        return ((
            !DB::table("pessoas")
                ->where("id", $request->id_pessoa)
                ->where("nome", $request->pessoa)
                ->where(function($sql) use($considerar_lixeira) {
                    if ($considerar_lixeira) $sql->where("lixeira", 0);
                })
                ->exists()
        ) && trim($request->pessoa)) || (!trim($request->pessoa) && trim($request->id_pessoa));
    }

    private function comum($select) {
        return DB::table("comodatos")
                    ->join("maquinas", "maquinas.id", "comodatos.id_maquina")
                    ->join("empresas", "empresas.id", "comodatos.id_empresa")
                    ->select(DB::raw($select))
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "empresas")) // App\Http\Controllers\Controller.php
                    ->where("maquinas.lixeira", 0);
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
                maquinas.descr AS col2
            ")->whereRaw($filtro."
                AND CURDATE() >= inicio
                AND CURDATE() < fim
            ")->orderby("maquinas.descr")->get()
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
        return sizeof($resultado) ? view("reports/bilateral", compact("resultado", "criterios", "titulo")) : $this->view_mensagem("warning", "Não há nada para exibir");
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
        return sizeof($resultado) ? view("reports/bilateral", compact("resultado", "criterios", "titulo")) : $this->view_mensagem("warning", "Não há nada para exibir");
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
                    DB::raw("
                        DATE_FORMAT(
                            CASE 
                                WHEN (retiradas.hms IS NULL) THEN DATE_SUB(retiradas.created_at, INTERVAL 3 HOUR)
                                ELSE CONCAT(retiradas.data, ' ', retiradas.hms)
                            END,
                            '%d/%m/%Y %H:%i:%s'
                        ) AS data_hora
                    "),
                    DB::raw("IFNULL(CONCAT('Liberado por ', supervisor.nome, IFNULL(CONCAT(' - ', retiradas.observacao), '')), '') AS obs")
                )
                ->join("produtos", "produtos.id", "retiradas.id_produto")
                ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                ->leftjoin("comodatos", "comodatos.id", "retiradas.id_comodato")
                ->leftjoin("maquinas", "maquinas.id", "comodatos.id_maquina")
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
                    $id_emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
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
                        "data" => $retirada->data_hora, 
                        "obs" => $retirada->obs,
                        "ca" => $retirada->ca,
                        "validade_ca" => $retirada->validade_ca,
                        "qtd" => intval($retirada->qtd)
                    ];
                })->values()->all()
            ];
        })->sortBy("nome")->values()->all();
        $retorno->criterios = join(" | ", $criterios);
        $retorno->cidade = "Barueri";
        $retorno->data_extenso = ucfirst(strftime("%d de %B de %Y"));
        return $retorno;
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

    public function bilateral(Request $request) {
        if ($this->bilateral_consultar($request)) return 401;
        if ($request->rel_grupo == "empresas-por-maquina") return $this->empresas_por_maquina($request);
        return $this->maquinas_por_empresa($request);
    }

    public function comodatos() {
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php
        $resultado = $this->comum("
            maquinas.descr AS maquina,
            empresas.nome_fantasia AS empresa,
            DATE_FORMAT(comodatos.inicio, '%d/%m/%Y') AS inicio,
            DATE_FORMAT(comodatos.fim, '%d/%m/%Y') AS fim
        ")->orderby("comodatos.inicio")->get();
        return sizeof($resultado) ? view("reports/comodatos", compact("resultado")) : $this->view_mensagem("warning", "Não há nada para exibir"); // App\Http\Controllers\Controller.php
    }

    public function extrato_consultar(Request $request) {
        return json_encode($this->extrato_consultar_main($request)); // App\Http\Controllers\Controller.php
    }

    public function sugestao(Request $request) {
        if ($this->extrato_consultar_main($request)->el) return 401; // App\Http\Controllers\Controller.php
        $tela = $this->sugestao_main($request); // App\Http\Controllers\Controller.php
        $resultado = $tela->resultado;
        $criterios = $tela->criterios;
        if (sizeof($resultado)) return view("reports/saldo", compact("resultado", "criterios"));
        return $this->view_mensagem("warning", "Não há nada para exibir"); // App\Http\Controllers\Controller.php
    }

    public function extrato(Request $request) {
        $consulta = $this->extrato_consultar_main($request); // App\Http\Controllers\Controller.php
        $el_erro = $consulta->el;
        if (in_array($el_erro, ["maquina", "produto"])) return 401;
        $dados = $this->dados_comodato($request); // App\Http\Controllers\Controller.php

        $r_inicio = $request->inicio;
        if ($r_inicio) {
            if (strpos($el_erro, "inicio") !== false) $r_inicio = $consulta->inicio_correto;
        } else $r_inicio = Carbon::parse($dados->inicio)->format("d/m/Y");
        
        $r_fim = $request->fim;
        if ($r_fim) {
            if (strpos($el_erro, "fim") !== false) $r_fim = $consulta->fim_correto;
        } else $r_fim = Carbon::parse($dados->fim)->format("d/m/Y");

        $inicio = Carbon::createFromFormat('d/m/Y', $r_inicio)->format('Y-m-d');
        $fim = Carbon::createFromFormat('d/m/Y', $r_fim)->format('Y-m-d');

        $criterios = ["Período de ".$r_inicio." até ".$r_fim];
        $resultado = collect(
            DB::table("comodatos_produtos AS cp")
                ->select(
                    // GRUPO 1
                    "maquinas.id AS id_maquina",
                    "maquinas.descr AS maquina",

                    // GRUPO 2
                    "vprodaux.id AS id_produto",
                    "vprodaux.descr AS produto",
                    DB::raw("IFNULL(tot.qtd, 0) AS saldo"),
                    "cp.preco",

                    // DETALHES
                    DB::raw("CONCAT(DATE_FORMAT(estoque.data, '%d/%m/%Y'), CASE WHEN estoque.hms IS NOT NULL THEN CONCAT(' ', estoque.hms) ELSE '' END) AS data"),
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
                ->join("comodatos", "comodatos.id", "cp.id_comodato")
                ->join("maquinas", "maquinas.id", "comodatos.id_maquina")
                ->join("vprodaux", "vprodaux.id", "cp.id_produto")
                ->leftjoin("estoque", "estoque.id_cp", "cp.id")
                ->leftjoin("log", function($join) {
                    $join->on("log.fk", "estoque.id")
                        ->where("log.tabela", "estoque");
                })
                ->leftjoinsub(
                    DB::table("estoque")
                        ->select(
                            "id_cp",
                            DB::raw("
                                SUM(CASE
                                    WHEN (es = 'E') THEN qtd
                                    ELSE qtd * -1
                                END) AS qtd
                            ")
                        )
                        ->whereRaw("DATE(created_at) < '".$inicio."'")
                        ->groupby("id_cp"),
                    "tot", "tot.id_cp", "cp.id"
                )
                ->where(function($sql) use($request, $inicio, $fim, &$criterios) {
                    if ($request->id_maquina) {
                        $maquina = Maquinas::find($request->id_maquina);
                        array_push($criterios, "Máquina: ".$maquina->descr);
                        $sql->where("maquinas.id", $maquina->id);
                    }
                    if ($request->id_produto) {
                        $produto = Produtos::find($request->id_produto);
                        array_push($criterios, "Produto: ".$produto->descr);
                        $sql->where("cp.id_produto", $produto->id);
                    }
                    if ($request->lm == "S") {
                        $sql->whereNotNull("estoque.id")
                            ->whereRaw("estoque.data >= '".$inicio."'")
                            ->whereRaw("estoque.data < '".$fim."'");
                    }
                    if ($this->obter_empresa()) $sql->whereIn("maquinas.id", $this->maquinas_periodo($inicio, $fim)); // App\Http\Controllers\Controller.php
                })
                ->whereRaw("CURDATE() >= comodatos.inicio")
                ->whereRaw("CURDATE() < comodatos.fim")
                ->where("vprodaux.lixeira", 0)
                ->where("maquinas.lixeira", 0)
                ->orderby("estoque.data")
                ->get()
        )->groupBy("id_maquina")->map(function($itens1) {
            return [
                "maquina" => [
                    "descr" => $itens1[0]->maquina,
                    "produtos" => collect($itens1)->groupBy("id_produto")->map(function($itens2) {
                        $saldo_ant = intval($itens2[0]->saldo);
                        $saldo_res = $saldo_ant + $itens2->sum("qtd");
                        return [
                            "descr" => $itens2[0]->produto,
                            "preco" => $itens2[0]->preco,
                            "saldo_ant" => $saldo_ant,
                            "saldo_res" => $saldo_res,
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
        $criterios = join(" | ", $criterios);
        if (sizeof($resultado)) return view("reports/extrato".($request->lm == "S" ? "A" : "S"), compact("resultado", "criterios"));
        return $this->view_mensagem("warning", "Não há nada para exibir"); // App\Http\Controllers\Controller.php
    }

    public function controle(Request $request) {
        $principal = $this->controleMain($request);

        $resultado = $principal->resultado;
        $criterios = $principal->criterios;
        $cidade = $principal->cidade;
        $data_extenso = $principal->data_extenso;

        $pdf = \PDF::loadView('reports/controle', [
            "resultado" =>    $principal->resultado, 
            "criterios" =>    $principal->criterios,
            "cidade" =>       $principal->cidade,
            "data_extenso" => $principal->data_extenso
        ])
            ->setOption('page-size', 'A4')
            ->setOption('margin-top', '20mm')
            ->setOption('margin-bottom', '20mm')
            ->setOption('print-media-type', true);
        return sizeof($principal->resultado) ? $pdf->inline('termos-de-retirada-'.(date("YmdHis")).'.pdf') : $this->view_mensagem("warning", "Não há nada para exibir");
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
                    $id_emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
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

    public function retiradas_consultar(Request $request) {
        if ($this->consultar_empresa($request)) return "empresa";
        if ($this->consultar_pessoa($request, false)) return "pessoa";
        if (((
            !DB::table("setores")
                ->where("id", $request->id_setor)
                ->where("descr", $request->setor)
                ->where("lixeira", 0)
                ->exists()
        ) && trim($request->setor)) || (!trim($request->setor) && trim($request->id_setor))) return "setor";
        return "";
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
                ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                ->join("setores", "setores.id", "retiradas.id_setor")
                ->join("produtos", "produtos.id", "retiradas.id_produto")
                ->leftjoin("empresas", "empresas.id", "retiradas.id_empresa")
                ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
                ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "pessoas", true)) // App\Http\Controllers\Controller.php
                ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "setores", true)) // App\Http\Controllers\Controller.php
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
                        if (DB::table("empresas")->where("id_matriz", $request->id_empresa)->exists()) $empresa .= " e filiais";
                        array_push($criterios, "Empresa: ".$empresa);
                    }
                    if ($request->consumo != "todos") {
                        $sql->where("produtos.consumo", $request->consumo == "epi" ? 0 : 1);
                        array_push($criterios, "Apenas ".($request->consumo == "epi" ? "EPI" : "produtos de consumo"));
                    }
                    if ($request->rel_grupo != "pessoa") {
                        $sql->where("pessoas.lixeira", 0)
                            ->where("setores.lixeira", 0);
                    } else if ($request->tipo_colab != "todos") {
                        $sql->where("pessoas.lixeira", $request->tipo_colab == "ativos" ? 0 : 1);
                        array_push($criterios, "Apenas colaboradores ".$request->tipo_colab);
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
        return sizeof($resultado) ? view("reports/retiradas".$request->tipo, compact("resultado", "criterios", "quebra", "val_total", "qtd_total", "titulo")) : $this->view_mensagem("warning", "Não há nada para exibir"); // App\Http\Controllers\Controller.php
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
                    ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                    ->join("setores", "setores.id", "retiradas.id_setor")
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
                        if ($request->tipo != "todos") {
                            if ($request->tipo == "inativos") {
                                $sql->where(function($w) use($request) {
                                    $w->where("pessoas.lixeira", 1)
                                        ->orWhere("setores.lixeira", 1);
                                });
                            } else {
                                $sql->where("pessoas.lixeira", 0)
                                    ->where("setores.lixeira", 0);
                            }
                            array_push($criterios, "Apenas ".$request->tipo);
                        }
                    })
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "pessoas", true)) // App\Http\Controllers\Controller.php
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "setores", true)) // App\Http\Controllers\Controller.php
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
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
        return sizeof($resultado) ? view("reports/ranking", compact("resultado", "criterios", "qtd_total")) : $this->view_mensagem("warning", "Não há nada para exibir"); // App\Http\Controllers\Controller.php
    }

    public function solicitacao($id) {
        $consulta = SolicitacoesProdutos::whereRaw("IFNULL(obs, '') <> ''")
                                        ->where("id_solicitacao", $id)
                                        ->pluck("obs");
        $solicitacao = Solicitacoes::find($id);
        if ($this->obter_autor_da_solicitacao($solicitacao->id) != Auth::user()->id_pessoa) return 401; // App\Http\Controllers\Controller.php
        $resultado = array();
        foreach ($consulta as $obs) {
            $aux = explode("|", $obs);
            $linha = new \stdClass;
            $linha->inconsistencia = $aux[0];
            $linha->justificativa = $aux[1];
            if (($aux[1] == config("app.msg_inexistente") && $solicitacao->status == "A") || $aux[1] != config("app.msg_inexistente")) array_push($resultado, $linha);
        }
        return view("reports.solicitacao", compact("resultado"));
    }
}
