<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Log;
use App\Models\Atbbkp;
use App\Models\Pessoas;
use App\Models\Produtos;
use App\Models\Maquinas;
use App\Models\Retiradas;
use App\Models\Comodatos;
use App\Models\Atribuicoes;
use App\Services\GlobaisService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function obter_empresa() {
        $servico = new GlobaisService;
        return $servico->srv_obter_empresa(); // App\Services\GlobaisService.php
    }

    protected function comparar_num($a, $b) {
        if ($a === null) $a = 0;
        if ($b === null) $b = 0;
        return floatval($a) != floatval($b);
    }

    protected function comparar_texto($a, $b) {
        if ($a === null) $a = "";
        if ($b === null) $b = "";
        return mb_strtoupper(trim($a)) != mb_strtoupper(trim($b));
    }

    protected function verifica_vazios(Request $request, $chaves) {
        $arr_req = (array) $request;
        $erro = false;
        foreach ($arr_req as $chave => $valor) {
            if (in_array($chave, $chaves) && !trim($valor)) $erro = true;
        }
        return $erro;
    }

    protected function obter_comodato($id_maquina) {
        return Comodatos::find(
            DB::table("comodatos")
                ->whereRaw("CURDATE() >= inicio AND CURDATE() < fim")
                ->where("id_maquina", $id_maquina)
                ->value("id")
        );
    }

    protected function maquinas_periodo($inicio, $fim) {
        $where = "";
        if ($inicio) $where .= "('".$inicio."' >= comodatos.inicio AND '".$inicio."' < comodatos.fim)";
        if ($fim) {
            if ($where) $where .= " OR ";
            $where .= "('".$fim."' >= comodatos.inicio AND '".$fim."' < comodatos.fim)";
        }
        $where = $where ? "(".$where.")" : "1";
        return DB::table("comodatos")
                    ->selectRaw("DISTINCTROW comodatos.id_maquina")
                    ->join(
                        DB::raw("(
                            SELECT
                                pessoas.id AS id_pessoa,
                                pessoas.id_empresa
                            FROM pessoas
                            JOIN empresas
                                ON pessoas.id_empresa IN (empresas.id, empresas.id_matriz)
                            WHERE pessoas.lixeira = 0
                              AND empresas.lixeira = 0
                        ) AS minhas_empresas"),
                        function ($join) {
                            $join->on("minhas_empresas.id_empresa", "comodatos.id_empresa");
                        }
                    )
                    ->whereRaw($where)
                    ->where("minhas_empresas.id_empresa", $this->obter_empresa())                    
                    ->pluck("id_maquina")
                    ->toArray();
    }

    protected function empresa_consultar(Request $request) {
        return (!sizeof(
            DB::table("empresas")
                ->where("id", $request->id_empresa)
                ->where("nome_fantasia", $request->empresa)
                ->where("lixeira", 0)
                ->get()
        ));
    }

    protected function log_inserir($acao, $tabela, $fk, $origem = "WEB", $nome = "") {
        $linha = new Log;
        $linha->acao = $acao;
        $linha->origem = $origem;
        $linha->tabela = $tabela;
        $linha->fk = $fk;
        if ($origem == "WEB") {
            $linha->id_pessoa = Auth::user()->id_pessoa;
            $linha->nome = Pessoas::find($linha->id_pessoa)->nome;
        } else if ($nome) $linha->nome = $nome;
        $linha->data = date("Y-m-d");
        $linha->hms = date("H:i:s");
        $linha->save();
        return $linha;
    }

    protected function log_inserir_lote($acao, $query, $where, $origem = "WEB", $nome = "", $tabela = "") {
        $id_pessoa = "NULL";
        if ($origem == "WEB") {
            $id_pessoa = Auth::user()->id_pessoa;
            $nome = Pessoas::find($id_pessoa)->nome;
        }
        DB::statement("
            INSERT INTO log (acao, origem, tabela, fk, id_pessoa, nome, data, hms) (
                SELECT
                    '".$acao."',
                    '".$origem."',
                    '".($tabela ? $tabela : $query)."',
                    id,
                    ".$id_pessoa.",
                    ".($nome ? "'".$nome."'" : "NULL").",
                    '".date("Y-m-d")."',
                    '".date("H:i:s")."'
                
                FROM ".$query."

                WHERE ".$where."
            )
        ");
    }

    protected function log_consultar($tabela, $param = "") {
        $servico = new GlobaisService;
        return $servico->srv_log_consultar($tabela, $param); // App\Services\GlobaisService.php
    }

    protected function retirada_consultar($id_atribuicao, $qtd, $id_pessoa) {
        $consulta = DB::table("vpendentesgeral")
                        ->where("esta_pendente", 1)
                        ->where("id_atribuicao", $id_atribuicao)
                        ->where("id_pessoa", $id_pessoa)
                        ->value("qtd");
        if ($consulta === null) return 0;
        return floatval($consulta) > floatval($qtd) ? 0 : 1;
    }

    protected function retirada_salvar($json) {
        $comodato = intval($json["id_comodato"]);
        $api = $comodato > 0;

        $consulta = $api ?
            DB::table("comodatos_produtos AS cp")
                ->select(
                    "produtos.ca",
                    DB::raw("IFNULL(cp.preco, produtos.preco) AS preco")
                )
                ->join("produtos", "produtos.id", "cp.id_produto")
                ->join("comodatos", "comodatos.id", "cp.id_comodato")
                ->where("comodatos.id", $comodato)
        :
            DB::table("produtos")
                ->select(
                    "ca",
                    "preco"
                )
        ;
        $consulta_produto = $consulta->where("produtos.id", $json["id_produto"])->first();

        $pessoa = Pessoas::find($json["id_pessoa"]);
        $linha = new Retiradas;
        if (isset($json["obs"])) $linha->observacao = $json["obs"];
        if (isset($json["hora"])) $linha->hms = $json["hora"];
        if (isset($json["biometria_ou_senha"])) $linha->biometria_ou_senha = $json["biometria_ou_senha"];
        if (isset($json["id_supervisor"])) {
            if (intval($json["id_supervisor"])) $linha->id_supervisor = $json["id_supervisor"];
        }
        $linha->id_pessoa = $pessoa->id;
        $linha->id_atribuicao = $json["id_atribuicao"];
        $linha->id_produto = $json["id_produto"];
        $linha->id_comodato = $comodato;
        $linha->qtd = $json["qtd"];
        $linha->data = $json["data"];
        $linha->id_empresa = $pessoa->id_empresa;
        $linha->id_setor = $pessoa->id_setor;
        $linha->preco = $consulta_produto->preco;
        $linha->ca = $consulta_produto->ca;
        $linha->save();
        
        $reg_log = $this->log_inserir("C", "retiradas", $linha->id, $api ? "APP" : "WEB");
        if ($api) {
            $reg_log->id_pessoa = $pessoa->id;
            $reg_log->nome = $pessoa->nome;
            $reg_log->save();
        }
    }

    protected function atualizar_tudo($valor, $chave = "M", $completo = false) {
        $valor_ant = $valor;
        if (is_iterable($valor)) $valor = implode(",", $valor);
        $valor = "'(".$valor.")'";
        if ($completo) {
            if (is_iterable($valor_ant)) {
                foreach ($valor_ant as $maq) DB::statement("CALL atualizar_mat_vcomodatos(".$maq.")");
            } else DB::statement("CALL atualizar_mat_vcomodatos(".$valor_ant.")");
        }
        DB::statement("CALL atualizar_mat_vatbaux('".$chave."', ".$valor.", 'N')");
        DB::statement("CALL atualizar_mat_vatribuicoes('".$chave."', ".$valor.", 'N')");
        DB::statement("CALL atualizar_mat_vretiradas_vultretirada('".$chave."', ".$valor.", 'R', 'N')");
        DB::statement("CALL atualizar_mat_vretiradas_vultretirada('".$chave."', ".$valor.", 'U', 'N')");
        if ($completo) DB::statement("CALL excluir_atribuicao_sem_retirada()");
    }

    protected function atualizar_atribuicoes($consulta) {
        foreach ($consulta as $linha) $this->atualizar_tudo($linha->psm_valor, $linha->psm_chave);
        DB::statement("CALL excluir_atribuicao_sem_retirada()");
    }

    protected function supervisor_consultar(Request $request) {
        $consulta = DB::table("pessoas")
                        ->where("cpf", $request->cpf)
                        ->where("senha", $request->senha)
                        ->where("supervisor", 1)
                        ->where("lixeira", 0)
                        ->get();
        return sizeof($consulta) ? $consulta[0]->id : 0;
    }

    protected function setor_mostrar($id) {
        if (intval($id)) {
            return DB::table("setores")
                        ->leftjoin("empresas", "empresas.id", "setores.id_empresa")
                        ->select(
                            "setores.descr",
                            "setores.cria_usuario",
                            "setores.id_empresa",
                            "empresas.nome_fantasia AS empresa"
                        )
                        ->where("setores.id", $id)
                        ->first();
        }
        $resultado = new \stdClass;
        $resultado->cria_usuario = 0;
        return $resultado;
    }

    protected function atribuicao_atualiza_ref($id, $antigo, $novo, $nome = "", $api = false) {
        if ($id && $this->comparar_texto($antigo, $novo)) {
            $novo = trim($novo);
            $where = "referencia = '".$antigo."'";
            $lista = DB::table("vatbold")
                        ->select(
                            "psm_chave",
                            "psm_valor"
                        )
                        ->whereRaw($where)
                        ->groupby(
                            "psm_chave",
                            "psm_valor"
                        )
                        ->get();
            DB::statement("
                UPDATE atribuicoes
                SET ".($novo ? "referencia = '".$novo."'" : "lixeira = 1")."
                WHERE ".$where
            );
            $this->log_inserir_lote($novo ? "E" : "D", "atribuicoes", $where, $api ? "ERP" : "WEB", $nome);
            $this->atualizar_atribuicoes($lista);
        }
    }

    protected function atribuicao_listar($consulta) {
        $resultado = array();
        $aux = DB::table("pessoas")
                    ->select(
                        DB::raw("IFNULL(empresas.id, 0) AS id_empresa"),
                        DB::raw("IFNULL(empresas.id_matriz, 0) AS id_matriz")
                    )
                    ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                    ->where("pessoas.id", Auth::user()->id_pessoa)
                    ->first();
        foreach ($consulta as $linha) {
            $linha->pode_editar = 1;
            $mostrar = true;
            // $mostrar = $linha->psm_chave == "P";
            // if (!$mostrar) {
            //     $empresa_atribuicao = intval($linha->id_empresa);
            //     $empresa_logada = intval($aux->id_empresa);
            //     $mostrar = in_array($empresa_atribuicao, [0, $empresa_logada, intval($aux->id_matriz)]);
            //     $linha->pode_editar = $empresa_atribuicao == $empresa_logada ? 1 : 0;
            // }
            if ($mostrar) array_push($resultado, $linha);
        }
        return $resultado;
    }

    protected function gerar_atribuicoes(Comodatos $comodato) {
        $ret = false;
        $where = "lixeira = 0 AND id_maquina = ".$comodato->id_maquina." AND id_empresa = ".$comodato->id_empresa;
        $where_g = $where." AND gerado = 1";
        if (!intval($comodato->atb_todos)) {
            $ret = sizeof(
                DB::table("atribuicoes")
                    ->whereRaw($where_g)
                    ->get()
            ) > 0;
            if ($ret) {
                $this->log_inserir_lote("D", "atribuicoes", $where_g);
                DB::statement("
                    UPDATE atribuicoes
                    SET lixeira = 1
                    WHERE ".$where_g
                );
                DB::statement("CALL excluir_atribuicao_sem_retirada()");
            }
            return $ret;
        }
        $lista_itens = DB::table("produtos")
                            ->select(
                                DB::raw("IFNULL(produtos.cod_externo, '') AS cod_externo"),
                                DB::raw("IFNULL(produtos.referencia, '') AS referencia")
                            )
                            ->join("comodatos_produtos AS cp", "cp.id_produto", "produtos.id")
                            ->where("cp.id_comodato", $comodato->id)
                            ->where("cp.lixeira", 0)
                            ->where("produtos.lixeira", 0)
                            ->get();
        foreach ($lista_itens as $item) {
            $modelo = null;
            $letra_log = "E";
            $continua = true;
            $atb = DB::table("atribuicoes")
                        ->select(
                            "id",
                            "gerado"
                        )
                        ->whereRaw($where)
                        ->where("referencia", $item->referencia)
                        ->first();
            if ($atb !== null) {
                if (intval($atb->gerado)) $modelo = Atribuicoes::find($atb->id);
                else $continua = false;
            }
            if ($continua) {
                $atb = DB::table("atribuicoes")
                            ->select(
                                "id",
                                "gerado"
                            )
                            ->whereRaw($where)
                            ->where("cod_produto", $item->cod_externo)
                            ->first();
                if ($atb !== null) {
                    if (intval($atb->gerado)) $modelo = Atribuicoes::find($atb->id);
                    else $continua = false;
                }
            }
            if ($continua && $modelo === null) {
                $modelo = new Atribuicoes;
                $letra_log = "C";
            }
            if ($modelo !== null && (
                $this->comparar_num($comodato->qtd, $modelo->qtd) ||
                $this->comparar_num($comodato->validade, $modelo->validade) ||
                $this->comparar_num($comodato->obrigatorio, $modelo->obrigatorio)
            )) {
                $modelo->gerado = 1;
                $modelo->qtd = $comodato->qtd;
                $modelo->validade = $comodato->validade;
                $modelo->obrigatorio = $comodato->obrigatorio;
                $modelo->id_maquina = $comodato->id_maquina;
                $modelo->id_empresa = $comodato->id_empresa;
                $modelo->referencia = $item->referencia ? $item->referencia : null;
                $modelo->cod_produto = $item->referencia ? null : $item->cod_externo;
                $linha->id_empresa_autor = $this->obter_empresa();
                $linha->data = date("Y-m-d");
                $modelo->save();
                $this->log_inserir($letra_log, "atribuicoes", $modelo->id);
                if ($letra_log == "C") $ret = true;
            }
        }
        return $ret;
    }

    protected function backup_atribuicao(Atribuicoes $atribuicao) {
        $bkp = new Atbbkp;
        $bkp->qtd = $atribuicao->qtd;
        $bkp->data = $atribuicao->data;
        $bkp->validade = $atribuicao->validade;
        $bkp->obrigatorio = $atribuicao->obrigatorio;
        $bkp->gerado = $atribuicao->gerado;
        $bkp->id_usuario = $atribuicao->id_usuario;
        $bkp->id_atribuicao = $atribuicao->id;
        $bkp->id_usuario_editando = Auth::user()->id;
        $bkp->save();
    }

    protected function obter_where($id_pessoa, $tabela = "pessoas", $inclusive_excluidos = false) {
        $id_emp = Pessoas::find($id_pessoa)->id_empresa;
        $where = !in_array($tabela, ["comodatos", "retiradas"]) && !$inclusive_excluidos ? $tabela.".lixeira = 0" : "1";
        if (intval($id_emp)) {
            $where .= " AND ".($tabela != "empresas" ? $tabela.".id_empresa" : "empresas.id")." IN (
                SELECT id
                FROM empresas
                WHERE empresas.id = ".$id_emp."
                UNION ALL (
                    SELECT filiais.id
                    FROM empresas AS filiais
                    WHERE filiais.id_matriz = ".$id_emp."
                )
            )";
        }
        return $where;
    }

    protected function retorna_saldo_cp($id_comodato, $id_produto) {
        return floatval(
            DB::table("comodatos_produtos AS cp")
                ->selectRaw("IFNULL(vestoque.qtd, 0) AS saldo")
                ->leftjoin("vestoque", "vestoque.id_cp", "cp.id")
                ->where("cp.id_comodato", $id_comodato)
                ->where("cp.id_produto", $id_produto)
                ->first()
                ->saldo
        );
    }

    protected function dados_comodato(Request $request) {
        return DB::table("comodatos")
                    ->select(
                        DB::raw("MIN(inicio) AS inicio"),
                        DB::raw("MAX(fim) AS fim")
                    )
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "comodatos"))
                    ->where(function($sql) use($request) {
                        if ($request->id_maquina) $sql->where("id_maquina", $request->id_maquina);
                    })
                    ->first();
    }

    protected function consultar_maquina(Request $request) {
        return ((!sizeof(
            DB::table("maquinas")
                ->where("id", $request->id_maquina)
                ->where("descr", $request->maquina)
                ->where("lixeira", 0)
                ->get()
        ) && trim($request->maquina)) || (trim($request->id_maquina) && !trim($request->maquina)));
    }

    protected function extrato_consultar_main(Request $request) {
        $resultado = new \stdClass;
        if (isset($request->maquina)) {
            if ($this->consultar_maquina($request)) {
                $resultado->el = "maquina";
                return $resultado;
            }
        }
        if (((trim($request->produto) && !sizeof(
            DB::table("vprodaux")
                ->where("id", $request->id_produto)
                ->where("descr", $request->produto)
                ->where("lixeira", 0)
                ->get()
        )) || (trim($request->id_produto) && !trim($request->produto)))) {
            $resultado->el = "produto";
            return $resultado;
        }
        if ($request->inicio || $request->fim) {
            $consulta = $this->dados_comodato($request);
            $elementos = array();
            if ($request->inicio) {
                $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->startOfDay();
                $consulta_inicio = Carbon::parse($consulta->inicio)->startOfDay();
                if ($inicio->lessThan($consulta_inicio)) {
                    $resultado->inicio_correto = $consulta_inicio->format("d/m/Y");
                    array_push($elementos, "inicio");
                }
            }
            if ($request->fim) {
                $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->startOfDay();
                $consulta_fim = Carbon::parse($consulta->fim)->startOfDay();
                if ($fim->greaterThan($consulta_fim)) {
                    $resultado->fim_correto = $consulta_fim->format("d/m/Y");
                    array_push($elementos, "fim");
                }
            }
            $resultado->varias_maquinas = $request->id_maquina ? "N" : "S";
            $resultado->el = join(",", $elementos);
            return $resultado;
        }
        $resultado->el = "";
        return $resultado;
    }

    protected function sugestao_main(Request $request) {
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
            DB::table("comodatos_produtos AS cp")
                ->select(
                    // GRUPO
                    "mq.id AS id_maquina",
                    "mq.descr AS maquina",

                    // DETALHES
                    "vprodaux.id AS id_produto",
                    "vprodaux.descr AS produto",
                    "cp.minimo",

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
                                WHEN (estq.data >= '".$inicio."' AND estq.data < '".$fim."') THEN
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
                                WHEN (estq.data >= '".$inicio."' AND estq.data < '".$fim."' AND estq.origem = 'ERP') THEN
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
                                WHEN (estq.data >= '".$inicio."' AND estq.data < '".$fim."' AND estq.origem <> 'ERP') THEN
                                    CASE
                                        WHEN estq.es = 'S' THEN estq.qtd
                                        ELSE 0
                                    END
                                ELSE 0
                            END
                        ) AS retiradas
                    ")
                )
                ->join("vprodaux", "vprodaux.id", "cp.id_produto")
                ->joinSub(
                    DB::table("comodatos")
                        ->select(
                            "id",
                            "id_maquina",
                            "inicio"
                        )
                        ->whereRaw("('".$inicio."' BETWEEN comodatos.inicio AND comodatos.fim) OR ('".$fim."' BETWEEN comodatos.inicio AND comodatos.fim)"),
                    "cm",
                    "cm.id",
                    "cp.id_comodato"
                )
                ->joinSub(
                    DB::table("maquinas")
                        ->select(
                            "id",
                            "descr"
                        )
                        ->where(function($sql) use($request, $inicio, $fim, &$criterios) {
                            if ($this->obter_empresa()) $sql->whereIn("id", $this->maquinas_periodo($inicio, $fim));
                            if ($request->id_maquina) {
                                $maquina = Maquinas::find($request->id_maquina);
                                array_push($criterios, "Máquina: ".$maquina->descr);
                                $sql->where("id", $maquina->id);
                            }
                        })
                        ->where("lixeira", 0),
                    "mq",
                    "mq.id",
                    "cm.id_maquina"
                )
                ->joinSub(
                    DB::table("estoque")
                        ->select(
                            "estoque.id_cp",
                            "estoque.es",
                            "estoque.qtd",
                            "log.data",
                            "log.origem"
                        )
                        ->leftjoin("log", function($join) {
                            $join->on("log.fk", "estoque.id")
                                ->where("log.tabela", "estoque");
                        }),
                    "estq",
                    "estq.id_cp",
                    "cp.id"
                )
                ->where(function($sql) use($request, &$criterios) {
                    if ($request->id_produto) {
                        $produto = Produtos::find($request->id_produto);
                        array_push($criterios, "Produto: ".$produto->descr);
                        $sql->where("vprodaux.id", $produto->id);
                    }
                })
                ->where("vprodaux.lixeira", 0)
                ->groupby(
                    "mq.id",
                    "mq.descr",
                    "vprodaux.id",
                    "vprodaux.descr",
                    "cp.minimo"
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
                    "id" => $produto->id_produto,
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
                    "id" => $maquinas[0]->id_maquina,
                    "descr" => $maquinas[0]->maquina,
                    "produtos" => $produtos->all()
                ]
            ];
        })->filter()->sortBy(fn($m) => $m["maquina"]["descr"])->values()->all();
        if ($tipo == "G") array_push($criterios, "Compra sugerida para ".$dias." dia".($dias > 1 ? "s" : ""));
        if ($lm) array_push($criterios, "Apenas produtos cuja compra é sugerida");
        $tela = new \stdClass;
        $tela->resultado = $resultado;
        $tela->criterios = join(" | ", $criterios);
        return $tela;
    }

    protected function obter_autor_da_solicitacao($solicitacao) {
        return DB::table("log")
                ->where("fk", $solicitacao)
                ->where("tabela", "solicitacoes")
                ->where("acao", "C")
                ->value("id_pessoa");
    }

    protected function view_mensagem($icon, $text) {
        return view("mensagem", compact("icon", "text"));
    }

    protected function obter_cp($id_comodato, $id_produto) {
        return DB::table("comodatos_produtos")
                    ->where("id_produto", $id_produto)
                    ->where("id_comodato", $id_comodato)
                    ->value("id");
    }
}
