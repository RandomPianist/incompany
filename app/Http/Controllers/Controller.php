<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Log;
use App\Models\Pessoas;
use App\Models\Produtos;
use App\Models\Valores;
use App\Models\Retiradas;
use App\Models\Comodatos;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

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

    protected function maquinas_periodo($inicio, $fim) {
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
        } else if ($nome) $linha->nome = $linha->nome;
        $linha->data = date("Y-m-d");
        $linha->hms = date("H:i:s");
        $linha->save();
        return $linha;
    }

    protected function log_inserir_lote($acao, $origem, $tabela, $where, $nome = "") {
        $lista = DB::table($tabela)
                    ->whereRaw($where)
                    ->pluck("id")
                    ->toArray();
        foreach ($lista as $fk) $this->log_inserir($acao, $tabela, $fk, $origem, $nome);
    }

    protected function log_consultar($tabela, $param = "") {
        if (intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa)) return "";
        $query = "
            SELECT
                IFNULL(log.nome, log.origem) AS nome,
                CONCAT(DATE_FORMAT(log.data, '%d/%m/%Y'), CASE WHEN log.hms IS NOT NULL THEN CONCAT(' às ', log.hms) ELSE '' END) AS data

            FROM log

            LEFT JOIN pessoas
                ON pessoas.id = log.id_pessoa
        ";

        if ($tabela == "pessoas") {
            $param2 = str_replace("aux1", "aux2", $param);
            $param2 = str_replace("setores1", "setores2", $param2);
            $query .= "
                LEFT JOIN pessoas AS aux1
                    ON aux1.id = log.fk

                LEFT JOIN setores AS setores1
                    ON setores1.id = aux1.id_setor

                LEFT JOIN (
                    SELECT
                        id,
                        pessoa_ou_setor_valor
                    FROM atribuicoes
                    WHERE pessoa_ou_setor_chave = 'P'
                ) AS atb ON atb.id = log.fk

                LEFT JOIN pessoas AS aux2
                    ON aux2.id = atb.pessoa_ou_setor_valor

                LEFT JOIN setores AS setores2
                    ON setores2.id = aux2.id_setor

                LEFT JOIN retiradas
                    ON retiradas.id_atribuicao = atb.id AND retiradas.id_comodato = 0

                WHERE ((log.tabela = 'pessoas' AND ".$param.")
                    OR (".$param2." AND (log.tabela = 'atribuicoes' OR (log.tabela = 'retiradas' AND retiradas.id IS NOT NULL))))
            ";
        } else if ($tabela == "valores") {
            $query .= "
                LEFT JOIN (
                    SELECT id
                    FROM valores
                    WHERE alias = '".$param."'
                ) AS main ON main.id = log.fk

                LEFT JOIN maquinas_produtos AS mp
                    ON mp.id_maquina = main.id

                LEFT JOIN estoque
                    ON estoque.id_mp = mp.id

                WHERE ((log.tabela = 'valores' AND main.id IS NOT NULL)
                   OR (log.tabela = 'maquinas_produtos' AND mp.id IS NOT NULL)
                   OR (log.tabela = 'estoque' AND estoque.id IS NOT NULL))
            ";
        } else if ($tabela == "setores") {
            $query .= "
                LEFT JOIN (
                    SELECT id
                    FROM atribuicoes
                    WHERE pessoa_ou_setor_chave = 'S'
                ) AS atb ON atb.id = log.fk

                WHERE (log.tabela = 'setores'
                  OR (log.tabela = 'atribuicoes' AND atb.id IS NOT NULL))
            ";
        } else $query .= " WHERE log.tabela = '".$tabela."'";

        $query .= " AND log.origem IS NOT NULL ORDER BY log.data DESC, log.created_at DESC";

        $consulta = DB::select(DB::raw($query));
        return sizeof($consulta) ? "Última atualização feita por ".$consulta[0]->nome." em ".$consulta[0]->data : "Nenhuma atualização feita";
    }

    protected function retirada_consultar($id_atribuicao, $qtd, $id_pessoa) {
        $consulta = DB::table("vpendentes")
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
            DB::table("maquinas_produtos AS mp")
                ->select(
                    "produtos.ca",
                    DB::raw("IFNULL(mp.preco, produtos.preco) AS preco")
                )
                ->join("produtos", "produtos.id", "mp.id_produto")
                ->join("comodatos", "comodatos.id_maquina", "mp.id_maquina")
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
        if (isset($json["obs"])) $linha->obs = $json["obs"];
        if (isset($json["hora"])) $linha->hora = $json["hora"];
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
        return $linha;
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

    protected function criar_mp($id_produto, $id_maquina, $api = false, $nome = "") {
        $id_produto = strval($id_produto);
        $id_maquina = strval($id_maquina);
        $tabela = strpos(".", $id_maquina) !== false ? "valores" : "produtos";
        DB::statement("
            INSERT INTO maquinas_produtos (id_produto, id_maquina) (
                SELECT
                    ".$id_produto.",
                    ".$id_maquina."

                FROM ".$tabela."

                LEFT JOIN maquinas_produtos AS mp
                    ON mp.id_produto = ".$id_produto." AND mp.id_maquina = ".$id_maquina."
                
                WHERE mp.id IS NULL ".($tabela == "valores" ? " AND valores.alias = 'maquinas'" : "")."
            )
        ");
        DB::statement("
            UPDATE maquinas_produtos AS mp
            JOIN produtos
                ON produtos.id = mp.id_produto
            SET mp.preco = produtos.preco
            WHERE mp.preco IS NULL
        ");
        $id_pessoa = $api ? "NULL" : Auth::user()->id_pessoa;
        if (!$api) $nome = Pessoas::find($id_pessoa)->nome;
        DB::statement("
            INSERT INTO log (id_pessoa, nome, origem, acao, tabela, fk, data) (
                SELECT
                    ".$id_pessoa.",
                    ".($nome ? "'".$nome."'" : "NULL").",
                    '".($api ? "ERP" : "WEB")."',
                    'C',
                    'maquinas_produtos',
                    mp.id,
                    CURDATE()

                FROM maquinas_produtos AS mp

                LEFT JOIN log
                    ON log.tabela = 'maquinas_produtos' AND log.fk = mp.id

                WHERE log.id IS NULL
            )
        ");
    }

    protected function atribuicao_atualiza_ref($id, $antigo, $novo, $nome = "", $api = false) {
        if ($id && $this->comparar_texto($antigo, $novo)) {
            $novo = trim($novo);
            $where = "produto_ou_referencia_valor = '".$antigo."' AND produto_ou_referencia_chave = 'R'";
            DB::statement("
                UPDATE atribuicoes
                SET ".($novo ? "produto_ou_referencia_valor = '".$novo."'" : "lixeira = 1")."
                WHERE ".$where
            );
            $this->log_inserir_lote($novo ? "E" : "D", $api ? "ERP" : "WEB", "atribuicoes", $where, $nome);
            if (!$novo) {
                $lista = DB::table("atribuicoes")
                            ->selectRaw("DISTINCTROW pessoas.id")
                            ->join("pessoas", function($join) {
                                $join->on(function($sql) {
                                    $sql->on("pessoas.pessoa_ou_setor_valor", "pessoas.id")
                                        ->where("pessoas.pessoa_ou_setor_chave", "P");
                                })->orOn(function($sql) {
                                    $sql->on("pessoas.pessoa_ou_setor_valor", "pessoas.id_setor")
                                        ->where("pessoas.pessoa_ou_setor_chave", "S");
                                });
                            })
                            ->where("atribuicoes.produto_ou_referencia_chave", "R")
                            ->where("atribuicoes.produto_ou_referencia_valor", $antigo)
                            ->pluck("pessoas.id")
                            ->toArray();
                if (sizeof($lista)) {
                    DB::statement("DELETE FROM atribuicoes_associadas WHERE id_pessoa IN (".join(",", $lista).")");
                    DB::statement("INSERT INTO atribuicoes_associadas SELECT * FROM vatribuicoes WHERE id_pessoa IN (".join(",", $lista).")");
                }
            }
        }
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

    protected function retorna_saldo_mp($id_maquina, $id_produto) {
        return floatval(
            DB::table("maquinas_produtos AS mp")
                ->selectRaw("IFNULL(vestoque.qtd, 0) AS saldo")
                ->leftjoin("vestoque", "vestoque.id_mp", "mp.id")
                ->where("mp.id_maquina", $id_maquina)
                ->where("mp.id_produto", $id_produto)
                ->first()
                ->saldo
        );
    }

    protected function criar_comodato_main($id_maquina, $id_empresa, $inicio, $fim) {
        $dtinicio = Carbon::createFromFormat('d/m/Y', $inicio)->format('Y-m-d');
        $dtfim = Carbon::createFromFormat('d/m/Y', $fim)->format('Y-m-d');
        
        $linha = new Comodatos;
        $linha->id_maquina = $request->id_maquina;
        $linha->id_empresa = $request->id_empresa;
        $linha->inicio = $dtinicio;
        $linha->fim = $dtfim;
        $linha->fim_orig = $dtfim;
        $linha->save();
        $this->log_inserir("C", "comodatos", $linha->id);
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
        $tela->mostrar_giro = $tipo == "G";
        return $tela;
    }

    protected function obter_autor_da_solicitacao($solicitacao) {
        return DB::table("log")
                ->where("fk", $solicitacao)
                ->where("tabela", "solicitacoes")
                ->where("acao", "C")
                ->value("id_pessoa");
    }
}
