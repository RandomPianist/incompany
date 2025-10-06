<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Permissoes;
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
                    } elseif ($char == ">") {
                        $insideTag = false;
                        $result .= $char;
                    } elseif (!$insideTag) {
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

    public function iniciar() {
        if (Permissoes::where("id_usuario", Auth::user()->id)->financeiro) return view("dashboard");
        if (Pessoas::find(Auth::user()->id_pessoa)) return redirect("/colaboradores/pagina/A");
        return redirect("/colaboradores/pagina/F");
    }

    public function autocomplete(Request $request) {
        $tabela = str_replace("_todos", "", $request->table);
        $tabela = str_replace("_lixeira", "", $tabela);
        $where = " AND ".$request->column." LIKE '%".$request->search."%' AND ";
        $filter_col = $request->filter_col != "v_maquina" ? $request->filter_col : "";
        
        if (in_array($tabela, ["empresas", "pessoas", "setores"])) {
            $where .= $this->obter_where(Auth::user()->id_pessoa, $tabela, true); // App\Http\Controllers\Controller.php
            if ($request->filter_col == "v_maquina") {
                $where .= " AND id IN ".($tabela == "setores" ? "(
                    SELECT pessoas.id_setor
                    FROM mat_vcomodatos
                    JOIN pessoas
                        ON pessoas.id = mat_vcomodatos.id_pessoa
                    WHERE mat_vcomodatos.id_maquina = ".$request->filter."
                )" : "(
                    SELECT id_pessoa
                    FROM mat_vcomodatos
                    WHERE id_maquina = ".$request->filter."
                )");
            }
        } elseif ($tabela == "produtos") {
            $tabela = "vprodaux";
            $where .= "id IN (
                SELECT id_produto
                FROM vprodutosgeral
                WHERE id_pessoa = ".Auth::user()->id_pessoa.
            ")";
        } elseif ($tabela == "maquinas") {
            $where .= "id IN (
                SELECT id_maquina
                FROM mat_vcomodatos
            )";
        } else $where .= "1";

        if ($filter_col) {
            if ($request->column == "referencia") {
                $filtro = explode("|", $request->filter);
                $where .= " AND referencia NOT IN (
                    SELECT pr_valor
                    FROM vatbold
                    WHERE psm_valor = '".$filtro[1]."'
                      AND psm_chave = '".$filtro[0]."'
                      AND pr_chave = 'R'
                )";
            } else $where .= " AND ".$filter_col." = '".$request->filter."'";
        }

        $query = "SELECT ";
        if ($request->column == "referencia") $query .= "MIN(id) AS ";
        $query .= "id, ".$request->column;
        $query .= " FROM ".$tabela;
        $query .= " WHERE ";
        if (strpos($request->table, "todos") !== false) $query .= "1";
        elseif (strpos($request->table, "lixeira") !== false) $query .= "lixeira = 1";
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

    public function obter_descr(Request $request) {
        $coluna = "";
        if ($request->tabela == "pessoas") $coluna .= "nome AS ";
        $coluna .= "descr";
        if ($request->tabela == "empresas") {
            if (
                DB::table("empresas")
                    ->where("id_matriz", $request->id)
                    ->where("lixeira", 0)
                    ->exists()
            ) return $request->id;
            return DB::table("empresas")
                        ->where("id", $request->id)
                        ->value("id_matriz");
        }
        return DB::table($request->tabela)
                    ->select($coluna)
                    ->where("id", $request->id)
                    ->value($coluna);
    }

    public function consultar_geral(Request $request) {
        $coluna = "descr";
        switch ($request->tabela) {
            case "empresas":
                $coluna = "nome_fantasia";
                break;
            case "pessoas":
                $coluna = "nome";
                break;
        }
        $tabela = $request->tabela;
        if ($tabela == "produtos") $tabela = "vprodaux";
        return DB::table($tabela)
                    ->where("id", $request->id)
                    ->where($coluna, $request->filtro)
                    ->where("lixeira", 0)
                    ->exists() ? "1" : "0";
    }

    public function permissoes() {
        return json_encode(Permissoes::where("id_usuario", Auth::user()->id)->first());
    }

    public function pode_abrir($tabela, $id) {
        return json_encode($this->pode_abrir_main($tabela, $id, "editar")); // App\Http\Controllers\Controller.php
    }

    public function descartar(Request $request) {
        $this->alterar_usuario_editando($request->tabela, $request->id, true); // App\Http\Controllers\Controller.php
    }
}