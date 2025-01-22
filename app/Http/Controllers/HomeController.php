<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Pessoas;
use Illuminate\Http\Request;

class HomeController extends ControllerKX {
    public function index() {
        if (intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa)) return redirect("/colaboradores/pagina/F");
        return redirect("/valores/categorias");
    }

    private function retorna_saldo_mp($id_maquina, $id_produto) {
        return floatval(
            DB::table("maquinas_produtos AS mp")
                ->selectRaw("IFNULL(tab.saldo, 0) AS saldo")
                ->leftjoinSub(DB::table(DB::raw("(
                    SELECT
                        CASE
                            WHEN (es = 'E') THEN qtd
                            ELSE qtd * -1
                        END AS qtd,
                        id_mp
                    
                    FROM estoque
                ) AS estq"))->select(
                    DB::raw("IFNULL(SUM(qtd), 0) AS saldo"),
                    "id_mp"
                )->groupBy("id_mp"), "tab", "tab.id_mp", "mp.id")
                ->where("mp.id_maquina", $id_maquina)
                ->where("mp.id_produto", $id_produto)
                ->first()
                ->saldo
        );
    }

    public function autocomplete(Request $request) {        
        $where = " AND ".$request->column." LIKE '%".$request->search."%'";
        
        $id_emp = intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
        if ($id_emp) {
            switch ($request->table) {
                case "empresas":
                    $where .= " AND (id = ".$id_emp;
                    if (sizeof(
                        DB::table("empresas")
                            ->where("id_matriz", $id_emp)
                            ->where("lixeira", 0)
                            ->get()
                    ) > 0) $where .= " OR id_matriz = ".$id_emp;
                    $where .= ")";
                    break;
                case "pessoas":
                    $where .= " AND (id_empresa = ".$id_emp." OR id_empresa IN (
                        SELECT id_matriz
                        FROM empresas
                        WHERE id = ".$id_emp."
                    ) OR id_empresa IN (
                        SELECT id
                        FROM empresas
                        WHERE id_matriz = ".$id_emp."
                    ))";
                    break;
                case "setores":
                    if ($request->filter_col) $where .= " AND ".$request->filter_col." = 0";
                    break;
                case "produtos":
                    $pode_retornar = array();
                    $produtos = DB::table("produtos")->where("lixeira", 0)->pluck("id")->toArray();
                    $minhas_maquinas = $this->minhas_maquinas()->get();
                    foreach ($minhas_maquinas as $maquina) {
                        foreach ($produtos as $produto) {
                            if ($this->retorna_saldo_mp($maquina->id_maquina, $produto) > 0) array_push($pode_retornar, $produto);
                        }
                    }
                    $where .= " AND produtos.id IN (".join(",", $pode_retornar).")";
                    break;
            }
        }

        if ($request->filter_col && $request->table != "setores") {
            $where .= $request->column != "referencia" ? " AND ".$request->filter_col." = '".$request->filter."'" : " AND referencia NOT IN (
                SELECT produto_ou_referencia_valor
                FROM atribuicoes
                WHERE pessoa_ou_setor_valor = ".$request->filter."
                  AND lixeira = 0
            )";
        }

        $query = "SELECT ";
        if ($request->column == "referencia") $query .= "MIN(id) AS ";
        $query .= "id, ".$request->column;
        $query .= " FROM ".$request->table;
        $query .= " WHERE lixeira = 0".$where;
        if ($request->column == "referencia") $query .= " GROUP BY referencia";
        $query .= " ORDER BY ".$request->column;
        $query .= " LIMIT 30";
        
        return json_encode(DB::select(DB::raw($query)));
    }
}