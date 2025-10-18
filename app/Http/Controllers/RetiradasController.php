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
        $data = "IFNULL(DATE_ADD(mat_vultretirada.data, INTERVAL vatbold.validade DAY), vatbold.data)";
        return json_encode(DB::select(DB::raw("
            SELECT
                produtos.descr,
                IFNULL(produtos.tamanho, 'UN') AS tamanho,
                IFNULL(produtos.cod_externo, produtos.id) AS id_produto,
                IFNULL(produtos.referencia, '') AS referencia,
                ".$this->retorna_calc_qtd()." AS qtd,
                DATE_FORMAT(
                    ".$data.",
                    '%d/%m/%Y'
                ) AS proxima_retirada,
                CASE
                    WHEN (
                        ((DATE_ADD(IFNULL(mat_vultretirada.data, '1900-01-01'), INTERVAL vatbold.validade DAY) <= CURDATE()))
                        AND ((vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))) > 0)
                    ) THEN
                        -ABS(DATEDIFF(".$data.", CURDATE()))
                    ELSE DATEDIFF(".$data.", CURDATE())
                END AS dias,
                CASE
                    WHEN (vatbold.pr_chave = 'P') THEN produtos.descr
                    ELSE CONCAT('REF: ', produtos.referencia)
                END AS nome_produto

            FROM ".$this->retorna_sql_pendentes(intval($id_pessoa))."

            WHERE atb.id_pessoa = ".$id_pessoa."
              AND vatbold.rascunho = 'S'
            
            ORDER BY vatbold.obrigatorio DESC, ".$data
        )));
    }
}