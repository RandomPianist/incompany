<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Pessoas;

class DashboardController extends Controller {
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

    private function ultimas_retiradas_main($id_pessoa, $inicio = "", $fim = "") {
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
                                        ->selectRaw("DISTINCTROW id_pessoa")
                                        ->whereRaw($this->obter_where($id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
                                        ->whereRaw("retiradas.data >= '".$inicio."'")
                                        ->whereRaw("retiradas.data <= '".$fim."'"),
                                    "ret",
                                    "ret.id_pessoa",
                                    "pessoas.id"
                                )
                                ->whereRaw($this->obter_where($id_pessoa, "pessoas", true)) // App\Http\Controllers\Controller.php
                                ->get();
        foreach ($ultimas_retiradas as $retirada) $retirada->foto = asset("storage/".$retirada->foto);
        return $ultimas_retiradas;
    }

    private function retiradas_por_setor_main($id_pessoa, $inicio = "", $fim = "") {
        if (!$inicio) $inicio = date("Y-m")."-01";
        if (!$fim) $fim = date("Y-m-d");
        return collect(
            DB::table("retiradas")
                ->select(
                    "setores.id",
                    "setores.descr",
                    DB::raw("SUM(retiradas.qtd) AS retirados"),
                    DB::raw("SUM(retiradas.preco) AS valor")
                )
                ->join("setores", "setores.id", "retiradas.id_setor")
                ->whereRaw("retiradas.data >= '".$inicio."'")
                ->whereRaw("retiradas.data <= '".$fim."'")
                ->whereRaw($this->obter_where($id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
                ->whereRaw($this->obter_where($id_pessoa, "setores")) // App\Http\Controllers\Controller.php
                ->groupby(
                    "setores.id",
                    "setores.descr"
                )
                ->get()
        )->groupBy("id")->map(function($itens) {
            return [
                "id" => $itens[0]->id,
                "descr" => $itens[0]->descr,
                "retirados" => $itens->sum("retirados"),
                "valor" => $itens->sum("valor")
            ];
        })->sortByDesc("valor")->values()->all();
    }

    private function maquinas_main($inicio, $fim) {
        return DB::table("maquinas")
                    ->select(
                        "maquinas.id",
                        "maquinas.descr"
                    )
                    ->whereIn("id", $this->maquinas_periodo($inicio, $fim)) // App\Http\Controllers\Controller.php
                    ->get();
    }

    private function retiradas_em_atraso_main($id_pessoa) {
        // CORREÇÃO: Passamos 0 para não pré-filtrar por uma única pessoa.
        // A função agora buscará as atribuições para TODOS, e o filtro de
        // empresa/contexto será aplicado apenas no final.
        $atribuicoesQuery = $this->retorna_atb_aux("T", "0", false, 0); // App\Http\Controllers\Controller.php
    
        // Passo 2: Construir a subquery que efetivamente substitui a `mat_vatribuicoes`.
        // (Lógica inalterada, agora operando sobre o conjunto de dados correto)
    
        // Subquery A: Encontra a 'grandeza' mínima (maior prioridade) para cada par pessoa/produto.
        $prioridades = DB::table(DB::raw("({$atribuicoesQuery}) AS sub_atb"))
            ->select(
                "sub_atb.id_pessoa",
                "sub_atb.id_produto",
                DB::raw("MIN(sub_atb.grandeza) as min_grandeza")
            )
            ->where("sub_atb.lixeira", 0)
            ->groupBy("sub_atb.id_pessoa", "sub_atb.id_produto");
    
        // Subquery B: Filtra as atribuições, pegando apenas aquelas com a maior prioridade.
        $atribuicoesPriorizadas = DB::table(DB::raw("({$atribuicoesQuery}) AS atb_bruto"))
            ->select(
                'atb_bruto.id_pessoa',
                'atb_bruto.id_atribuicao'
            )
            ->joinSub($prioridades, 'prioridades', function ($join) {
                $join->on('atb_bruto.id_pessoa', '=', 'prioridades.id_pessoa')
                     ->on('atb_bruto.id_produto', '=', 'prioridades.id_produto')
                     ->on('atb_bruto.grandeza', '=', 'prioridades.min_grandeza');
            })
            ->groupBy('atb_bruto.id_pessoa', 'atb_bruto.id_atribuicao');
    
        // Passo 3: Recriar a lógica da vpendentesgeral. (Lógica inalterada)
        $pendentesQuery = DB::table('vatbold')
            ->select(
                'mat_vatribuicoes.id_pessoa',
                'vatbold.id as id_atribuicao',
                DB::raw("
                    CASE
                        WHEN ((DATE_ADD(IFNULL(mat_vultretirada.data, '1900-01-01'), INTERVAL vatbold.validade DAY) <= CURDATE())) THEN
                            ROUND(
                                CASE
                                    WHEN (vprodutos.travar_estq = 1) THEN
                                        CASE
                                            WHEN (vprodutos.qtd >= (vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))))
                                            THEN (vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0)))
                                            ELSE vprodutos.qtd
                                        END
                                    ELSE (vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0)))
                                END
                            )
                        ELSE 0
                    END AS qtd_pendente
                ")
            )
            ->joinSub($atribuicoesPriorizadas, 'mat_vatribuicoes', function ($join) {
                $join->on('mat_vatribuicoes.id_atribuicao', '=', 'vatbold.id');
            })
            ->join('produtos', function ($join) {
                $join->on('produtos.cod_externo', '=', 'vatbold.cod_produto')
                     ->orOn('produtos.referencia', '=', 'vatbold.referencia');
            })
            ->leftJoin(DB::raw("(
                SELECT id_produto, id_pessoa, COUNT(id) AS qtd
                FROM pre_retiradas
                GROUP BY id_produto, id_pessoa
            ) AS prev"), function ($join) {
                $join->on('prev.id_produto', '=', 'produtos.id')
                     ->on('prev.id_pessoa', '=', 'mat_vatribuicoes.id_pessoa');
            })
            ->join('vprodutosgeral AS vprodutos', function ($join) {
                $join->on('vprodutos.id_pessoa', '=', 'mat_vatribuicoes.id_pessoa')
                     ->on('vprodutos.id_produto', '=', 'produtos.id');
            })
            ->leftJoin('mat_vretiradas', function ($join) {
                $join->on('mat_vretiradas.id_atribuicao', '=', 'vatbold.id')
                     ->on('mat_vretiradas.id_pessoa', '=', 'mat_vatribuicoes.id_pessoa');
            })
            ->leftJoin('mat_vultretirada', function ($join) {
                $join->on('mat_vultretirada.id_atribuicao', '=', 'vatbold.id')
                     ->on('mat_vultretirada.id_pessoa', '=', 'mat_vatribuicoes.id_pessoa');
            })
            ->where('vatbold.rascunho', '=', 'S')
            ->where(DB::raw("
                CASE
                    WHEN (((DATE_ADD(IFNULL(mat_vultretirada.data, '1900-01-01'), INTERVAL vatbold.validade DAY) <= CURDATE())) AND ((vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))) > 0))
                    THEN 1
                    ELSE 0
                END
            "), '=', 1);
    
        // Passo 4: Query final, que agrega e aplica o filtro de contexto. (Lógica inalterada)
        $atrasos = DB::table("pessoas")
            ->select(
                "pessoas.id",
                "pessoas.nome",
                "pessoas.foto",
                DB::raw("ROUND(pendente.total_qtd) AS total")
            )
            ->joinSub(
                $pendentesQuery->select('id_pessoa', DB::raw("SUM(qtd_pendente) as total_qtd"))->groupBy('id_pessoa'),
                "pendente",
                "pendente.id_pessoa",
                "=",
                "pessoas.id"
            )
            ->whereRaw($this->obter_where($id_pessoa)) // O filtro de contexto é aplicado aqui, como deve ser.
            ->where('pendente.total_qtd', '>', 0)
            ->orderBy("pendente.total_qtd", "DESC")
            ->get();
    
        foreach ($atrasos as $pessoa) {
            $pessoa->foto = asset("storage/".$pessoa->foto);
        }
        return $atrasos;
    }

    public function maquinas(Request $request) {
        return json_encode($this->maquinas_main($request->inicio, $request->fim));
    }

    public function dados(Request $request) {
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $this->formatar_data($request->inicio);
        if (isset($request->fim)) $fim = $this->formatar_data($request->fim);
        $resultado = new \stdClass;

        $id_pessoa = Auth::user()->id_pessoa;
        $retiradas_por_setor = new \stdClass;
        $aux = $this->retiradas_por_setor_main($id_pessoa, $inicio, $fim);
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
                        DB::raw("SUM(retiradas.qtd) AS retirados")
                    )
                    ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                    ->whereRaw($this->obter_where($id_pessoa, "pessoas", true)) // App\Http\Controllers\Controller.php
                    ->whereRaw($this->obter_where($id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->groupby(
                        "pessoas.id",
                        "pessoas.nome",
                        "pessoas.foto"
                    )
                    ->orderby("retirados", "DESC")
                    ->orderby("pessoas.nome")
                    ->get();
        foreach ($ranking as $pessoa) $pessoa->foto = asset("storage/".$pessoa->foto);
        
        $resultado->atrasos = []; // $inicio == date("Y-m")."-01" ? $this->retiradas_em_atraso_main($id_pessoa) : [];
        $resultado->ultimasRetiradas = $inicio == date("Y-m")."-01" ? $this->ultimas_retiradas_main($id_pessoa, $inicio, $fim) : [];
        $resultado->retiradasPorSetor = $retiradas_por_setor;
        $resultado->ranking = $ranking;
        $resultado->maquinas = $this->maquinas_main($inicio, $fim);
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
                        "produtos.descr AS produto",
                        DB::raw("ROUND(SUM(retiradas.qtd)) AS qtd")
                    )
                    ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                    ->join("produtos", "produtos.id", "retiradas.id_produto")
                    ->where("retiradas.id_pessoa", $id_pessoa)
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->groupby(
                        "produtos.id",
                        "produtos.descr"
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
                        "produtos.descr AS produto",
                        DB::raw("ROUND(retiradas.qtd) AS qtd"),
                        DB::raw("DATE_FORMAT(retiradas.data, '%d/%m/%Y') AS data")
                    )
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
                        DB::raw("SUM(retiradas.qtd) AS retirados"),
                        DB::raw("SUM(retiradas.preco) AS valor")
                    )
                    ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa)) // App\Http\Controllers\Controller.php
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->where("retiradas.id_setor", $id_setor)
                    ->groupby(
                        "pessoas.id",
                        "pessoas.nome"
                    )
                    ->get()
        )->groupby("id")->map(function($itens) {
            return [
                "id" => $itens[0]->id,
                "nome" => $itens[0]->nome,
                "retirados" => $itens->sum("retirados"),
                "valor" => $itens->sum("valor")
            ];
        })->sortByDesc("valor")->values()->all();
    }

    // API
    public function produtos_em_atraso($id_pessoa) {
        // --- Início do Bloco de Otimização ---
    
        // Passo 1: Obter a query base de atribuições para a pessoa específica.
        $atribuicoesQuery = $this->retorna_atb_aux('P', $id_pessoa, false, $id_pessoa); // App\Http\Controllers\Controller.php
    
        // Passo 2: Calcular as atribuições priorizadas, ignorando as da lixeira.
        $prioridades = DB::table(DB::raw("({$atribuicoesQuery}) AS sub_atb"))
            ->select(
                "sub_atb.id_pessoa",
                "sub_atb.id_produto",
                DB::raw("MIN(sub_atb.grandeza) as min_grandeza")
            )
            ->where('sub_atb.lixeira', '=', 0) // Regra de negócio: atribuições na lixeira não são prioritárias.
            ->groupBy("sub_atb.id_pessoa", "sub_atb.id_produto");
    
        $atribuicoesPriorizadas = DB::table(DB::raw("({$atribuicoesQuery}) AS atb_bruto"))
            ->select('atb_bruto.id_pessoa', 'atb_bruto.id_atribuicao')
            ->joinSub($prioridades, 'prioridades', function ($join) {
                $join->on('atb_bruto.id_pessoa', '=', 'prioridades.id_pessoa')
                     ->on('atb_bruto.id_produto', '=', 'prioridades.id_produto')
                     ->on('atb_bruto.grandeza', '=', 'prioridades.min_grandeza');
            })
            ->groupBy('atb_bruto.id_pessoa', 'atb_bruto.id_atribuicao');
    
        // Passo 3: Construir a "query base" que substitui a 'vpendentesgeral'.
        $baseQuery = DB::table('vatbold')
            ->joinSub($atribuicoesPriorizadas, 'mat_vatribuicoes', function ($join) {
                $join->on('mat_vatribuicoes.id_atribuicao', '=', 'vatbold.id');
            })
            ->join('produtos', function ($join) {
                $join->on('produtos.cod_externo', '=', 'vatbold.cod_produto')
                     ->orOn('produtos.referencia', '=', 'vatbold.referencia');
            })
            ->leftJoin(DB::raw("(
                SELECT id_produto, id_pessoa, COUNT(id) AS qtd
                FROM pre_retiradas
                GROUP BY id_produto, id_pessoa
            ) AS prev"), function ($join) {
                $join->on('prev.id_produto', '=', 'produtos.id')
                     ->on('prev.id_pessoa', '=', 'mat_vatribuicoes.id_pessoa');
            })
            ->join('vprodutosgeral AS vprodutos', function ($join) {
                $join->on('vprodutos.id_pessoa', '=', 'mat_vatribuicoes.id_pessoa')
                     ->on('vprodutos.id_produto', '=', 'produtos.id');
            })
            ->leftJoin('mat_vretiradas', function ($join) {
                $join->on('mat_vretiradas.id_atribuicao', '=', 'vatbold.id')
                     ->on('mat_vretiradas.id_pessoa', '=', 'mat_vatribuicoes.id_pessoa');
            })
            ->leftJoin('mat_vultretirada', function ($join) {
                $join->on('mat_vultretirada.id_atribuicao', '=', 'vatbold.id')
                     ->on('mat_vultretirada.id_pessoa', '=', 'mat_vatribuicoes.id_pessoa');
            })
            ->where('vatbold.rascunho', '=', 'S')
            ->where('mat_vatribuicoes.id_pessoa', '=', $id_pessoa);
    
        // --- Fim do Bloco de Otimização ---
    
        // Passo 4: Aplicar os filtros e seleções finais da função original.
        $produtosEmAtraso = $baseQuery
            ->select(
                'produtos.id AS id',
                'vatbold.validade',
                // Lógica de cálculo da quantidade pendente
                DB::raw("
                    ROUND(
                        CASE
                            WHEN (vprodutos.travar_estq = 1) THEN
                                CASE
                                    WHEN (vprodutos.qtd >= (vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))))
                                    THEN (vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0)))
                                    ELSE vprodutos.qtd
                                END
                            ELSE (vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0)))
                        END
                    ) AS qtd
                "),
                'vatbold.pr_valor AS produto',
                // Lógica de formatação do nome do produto
                DB::raw("
                    CASE
                        WHEN vatbold.pr_chave = 'P' THEN produtos.descr
                        ELSE CONCAT('REF: ', produtos.referencia)
                    END AS nome_produto
                ")
            )
            // Aplicar o filtro de "pendente"
            ->where(DB::raw("
                CASE
                    WHEN (((DATE_ADD(IFNULL(mat_vultretirada.data, '1900-01-01'), INTERVAL vatbold.validade DAY) <= CURDATE())) AND ((vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))) > 0))
                    THEN 1
                    ELSE 0
                END
            "), '=', 1)
            ->orderBy('qtd', 'DESC')
            ->get();
    
        // A lógica de priorização já garante uma linha por produto, tornando o GROUP BY desnecessário.
    
        // Se a função estiver em um controller, o ideal é retornar uma resposta JSON.
        // return response()->json($produtosEmAtraso);
        // Mas para manter a exata funcionalidade original:
        return json_encode($produtosEmAtraso);
    }

    public function ultimas_retiradas($id_pessoa) {
        return json_encode($this->ultimas_retiradas_main($id_pessoa));
    }

    public function retiradas_por_setor($id_pessoa) {
        return json_encode($this->retiradas_por_setor_main($id_pessoa));
    }

    public function retiradas_em_atraso($id_pessoa) {
        return json_encode($this->retiradas_em_atraso_main($id_pessoa));
    }
}