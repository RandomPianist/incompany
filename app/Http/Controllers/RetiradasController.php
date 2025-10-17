<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use App\Models\Pessoas;
use App\Models\Empresas;
use App\Models\Retiradas;
use App\Models\Permissoes;
use Illuminate\Http\Request;

class RetiradasController extends Controller {
    private function permitir(Request $request) {
        if (!Permissoes::where("id_usuario", Auth::user()->id)->first()->retiradas) return 401;
        $emp = $this->obter_empresa(); // App\Http\Traits\GlobaisTrait.php
        $pessoa = Pessoas::find($request->id_pessoa);
        $ok = true;
        if ($emp) $ok = ($pessoa->id_empresa == $emp || $pessoa->id_empresa == Empresas::find($emp)->id_matriz);
        if (!$ok) return 401;
        return 200;
    }

    public function consultar(Request $request) {
        return $this->retirada_consultar($request->atribuicao, $request->qtd, $request->pessoa); // App\Http\Controllers\Controller.php
    }

    public function salvar(Request $request) {
        $resultado = new \stdClass;
        $resultado->icon = "success";
        if ($this->permitir() == 401) {
            $resultado->icon = "error";
            $resultado->msg = "Operação não autorizada";
            return json_encode($resultado);
        }
        $json = array(
            "id_pessoa" => $request->pessoa,
            "id_atribuicao" => $request->atribuicao,
            "id_produto" => $request->produto,
            "id_comodato" => 0,
            "qtd" => $request->quantidade,
            "data" => Carbon::createFromFormat('d/m/Y', $request->data)->format('Y-m-d')
        );
        if (intval($request->supervisor)) $json["id_supervisor"] = $request->supervisor;
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            $this->retirada_salvar($json); // App\Http\Controllers\Controller.php
            $this->atualizar_mat_vretiradas_vultretirada("P", $request->pessoa, "R", false); // App\Http\Controllers\Controller.php
            $this->atualizar_mat_vretiradas_vultretirada("P", $request->pessoa, "U", false); // App\Http\Controllers\Controller.php
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $resultado->icon = "error";
            $resultado->msg = $e->getMessage();
        }
        return json_encode($resultado);
    }

    public function desfazer(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Traits\GlobaisTrait.php
        if ($this->permitir() == 401) return 401;
        $where = "id_pessoa = ".$request->id_pessoa;
        $this->log_inserir_lote("D", "retiradas", $where); // App\Http\Controllers\Controller.php
        Retiradas::whereRaw($where)->delete();
        return 200;
    }

    public function proximas($id_pessoa) {
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
            ->where('sub_atb.lixeira', '=', 0) // Regra de negócio.
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
    
        // Passo 4: Executar a consulta final, movendo toda a lógica do `foreach` para o SQL.
        $resultado = $baseQuery
            ->select(
                DB::raw("IFNULL(produtos.cod_externo, produtos.id) AS id_produto"),
                'produtos.descr',
                DB::raw("IFNULL(produtos.referencia, '') AS referencia"),
                'produtos.tamanho',
                // Lógica de 'qtd' da antiga VIEW
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
                // Lógica de 'proxima_retirada' formatada
                DB::raw("
                    DATE_FORMAT(
                        IFNULL(DATE_ADD(mat_vultretirada.data, INTERVAL vatbold.validade DAY), vatbold.data),
                        '%d/%m/%Y'
                    ) AS proxima_retirada
                "),
                // Lógica de 'dias' calculada diretamente no SQL
                DB::raw("
                    CASE
                        WHEN ( -- Início da lógica 'esta_pendente'
                            ((DATE_ADD(IFNULL(mat_vultretirada.data, '1900-01-01'), INTERVAL vatbold.validade DAY) <= CURDATE()))
                            AND ((vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))) > 0)
                        )
                        THEN DATEDIFF(IFNULL(DATE_ADD(mat_vultretirada.data, INTERVAL vatbold.validade DAY), vatbold.data), CURDATE()) * -1
                        ELSE DATEDIFF(IFNULL(DATE_ADD(mat_vultretirada.data, INTERVAL vatbold.validade DAY), vatbold.data), CURDATE())
                    END AS dias
                ")
            )
            ->orderBy('vatbold.obrigatorio', 'DESC')
            ->orderByRaw("IFNULL(DATE_ADD(mat_vultretirada.data, INTERVAL vatbold.validade DAY), vatbold.data)")
            ->get();
    
        // A lógica de priorização garante uma linha por produto, tornando o GROUP BY desnecessário.
        // O loop foreach foi completamente substituído pela consulta SQL.
    
        return json_encode($resultado);
    }
}