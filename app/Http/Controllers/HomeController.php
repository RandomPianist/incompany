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
                    if ($request->filter_col) $where .= " AND cria_usuario = 0";
                    break;
                case "produtos":
                    $pode_retornar = array();
                    $mmtexto = $this->minhas_maquinas()
                        ->where("id_pessoa", Auth::user()->id_pessoa)
                        ->pluck("id_maquina")
                        ->toArray();
                    $pode_retornar = DB::table("produtos")
                                        ->select("produtos.id")
                                        ->join("maquinas_produtos AS mp", "mp.id_produto", "produtos.id")
                                        ->join("vestoque", "vestoque.id_mp", "mp.id")
                                        ->where("vestoque.qtd", ">", 0)
                                        ->whereRaw("mp.id_maquina IN (".join(",", $mmtexto).")")
                                        ->groupby("produtos.id")
                                        ->pluck("id")
                                        ->toArray();
                    $where .= " AND produtos.id IN (".join(",", $pode_retornar).")";
                    break;
            }
        }

        if ($request->filter_col) {
            if ($request->table == "setores") {
                $colunas = explode(",", $request->filter_col);
                $filtros = explode(",", $request->filter);
                for ($i = 0; $i < sizeof($colunas); $i++) {
                    if (($colunas[$i] == "cria_usuario" && $id_emp) || ($colunas[$i] != "cria_usuario")) $where .= " AND ".$colunas[$i]." = ".$filtros[$i];
                }
            } else {
                $where .= $request->column != "referencia" ? " AND ".$request->filter_col." = '".$request->filter."'" : " AND referencia NOT IN (
                    SELECT produto_ou_referencia_valor
                    FROM atribuicoes
                    WHERE pessoa_ou_setor_valor = ".$request->filter."
                      AND lixeira = 0
                )";
            }
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