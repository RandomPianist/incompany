<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Pessoas;
use Illuminate\Http\Request;

class HomeController extends Controller {
    public function index() {
        if (intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa)) return redirect("/colaboradores/pagina/F");
        return redirect("/valores/categorias");
    }

    public function autocomplete(Request $request) {        
        $where = " AND ".$request->column." LIKE '".$request->search."%'";
        
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