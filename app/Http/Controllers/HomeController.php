<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Pessoas;
use Illuminate\Http\Request;

class HomeController extends Controller {
    private function str_ireplace2($search, $replace, $subject) {
        $len = strlen($search);
        $result = "";
        $i = 0;
    
        while ($i < strlen($subject)) {
            if (strtolower(substr($subject, $i, $len)) === strtolower($search)) {
                $j = 0;
                $insideTag = false;
                foreach (str_split($replace) as $char) {
                    if ($char == "<") {
                        $insideTag = true;
                        $result .= $char;
                    } else if ($char == ">") {
                        $insideTag = false;
                        $result .= $char;
                    } else if (!$insideTag) {
                        $result .= ctype_upper($subject[$i + $j]) ? strtoupper($char) : strtolower($char);
                        $j++;
                    } else $result .= $char;
                }
                $i += $len;
            } else {
                $result .= $subject[$i];
                $i++;
            }
        }
        return $result;
    }

    public function index() {
        if (intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa)) return redirect("/colaboradores/pagina/F");
        return redirect("/valores/categorias");
    }

    public function autocomplete(Request $request) {
        $tabela = str_replace("_todos", "", $request->table);
        $tabela = str_replace("_lixeira", "", $tabela);
        $where = " AND ".$request->column." LIKE '%".$request->search."%' AND ";
        
        if ($tabela == "produtos") {
            $where .= "produtos.id IN (
                SELECT id_produto
                FROM vprodutos
                WHERE id_pessoa = ".Auth::user()->id_pessoa.
            ")";
        } else if (in_array($tabela, ["empresas", "pessoas", "setores"])) $where .= $this->obter_where(Auth::user()->id_pessoa, $tabela, true);
        else $where .= "1";

        if ($request->filter_col) {
            $where .= $request->column != "referencia" ? " AND ".$request->filter_col." = '".$request->filter."'" : " AND referencia NOT IN (
                SELECT produto_ou_referencia_valor
                FROM atribuicoes
                WHERE pessoa_ou_setor_valor = ".$request->filter."
                    AND produto_ou_referencia_chave = 'R'
                    AND lixeira = 0
            )";
        }

        $query = "SELECT ";
        if ($request->column == "referencia") $query .= "MIN(id) AS ";
        $query .= "id, ".$request->column;
        $query .= " FROM ".$tabela;
        $query .= " WHERE ";
        if (strpos("todos", $request->table) !== false) $query .= "1";
        else if (strpos("lixeira", $request->table) !== false) $query .= "lixeira = 1";
        else $query .= "lixeira = 0";
        $query .= $where;
        if ($request->column == "referencia") $query .= " GROUP BY referencia";
        $query .= " ORDER BY ".$request->column;
        $query .= " LIMIT 30";
        
        $resultado = array();
        $consulta = DB::select(DB::raw($query));
        foreach ($consulta as $linha) {
            $linha = (array) $linha;
            $aux = array(
                "id" => $linha["id"],
                $request->column => $this->str_ireplace2($request->search, "<b>".$request->search."</b>", $linha[$request->column])
            );
            array_push($resultado, $aux);
        }
        return json_encode($resultado);
    }
}