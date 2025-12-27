<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Permissoes;
use App\Models\Pessoas;
use Illuminate\Http\Request;

class HomeController extends Controller {
    private function obter_permissao() {
        return Permissoes::where("id_usuario", Auth::user()->id)->first();
    }

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
        if ($this->obter_permissao()->financeiro) return view("dashboard");
        if (!$this->obter_empresa()) return redirect("/colaboradores/pagina/A"); // App\Http\Traits\GlobaisTrait.php
        return redirect("/colaboradores/pagina/F");
    }

    public function autocomplete(Request $request) {
        if (
            !preg_match('/^[a-zA-Z0-9_]+$/', $request->table) ||
            !preg_match('/^[a-zA-Z0-9_]+$/', $request->column) ||
            !preg_match('/^[a-zA-Z0-9_]+$/', $request->filter_col)
        ) return "[]";
        $tabela = str_replace("_todos", "", $request->table);
        $tabela = str_replace("_lixeira", "", $tabela);
        $tabela = str_replace("_maq", "", $tabela);
        $where = "1";
        $filter_col = $request->filter_col != "v_maquina" ? $request->filter_col : "";
        
        if (in_array($tabela, ["empresas", "pessoas", "setores"])) {
            $where .= " AND ".$this->obter_where(Auth::user()->id_pessoa, $tabela, true); // App\Http\Controllers\Controller.php
            if ($request->filter_col == "v_maquina") {
                $id_maquina = intval($request->filter);
                $where .= " AND id IN ".($tabela == "setores" ? "(
                    SELECT pessoas.id_setor
                    FROM mat_vcomodatos
                    JOIN pessoas
                        ON pessoas.id = mat_vcomodatos.id_pessoa
                    WHERE mat_vcomodatos.id_maquina = ".$id_maquina."
                )" : "(
                    SELECT id_pessoa
                    FROM mat_vcomodatos
                    WHERE id_maquina = ".$id_maquina."
                )");
            }
            if ($request->atribuicao) {
                $atribuicao = intval($request->id_atribuicao);
                $where .= " AND id NOT IN (
                    SELECT id_pessoa
                    FROM excecoes
                    WHERE id_atribuicao = ".$atribuicao."
                ) AND id_setor NOT IN (
                    SELECT id_setor
                    FROM excecoes
                    WHERE id_atribuicao = ".$atribuicao."
                )";
            }
        } elseif ($tabela == "produtos") {
            $tabela = "vprodaux";
            $where .= " AND id IN (
                SELECT id_produto
                FROM vprodutosgeral
                WHERE id_pessoa = ".Auth::user()->id_pessoa."
            )";
            if (strpos($request->table, "_maq") !== false) {
                $id_maquina = intval($request->filter);
                $where .= " AND id IN (
                    SELECT cp.id_produto
                    FROM comodatos_produtos AS cp
                    JOIN comodatos
                        ON comodatos.id = cp.id_comodato
                    WHERE comodatos.id_maquina = ".$id_maquina."
                      AND comodatos.inicio <= CURDATE()
                      AND comodatos.fim > CURDATE()
                )";
            }
        } elseif ($tabela == "maquinas") {
            $where .= " AND id IN (
                SELECT id_maquina
                FROM mat_vcomodatos
            )";
        } else $where .= " AND 1";

        if ($filter_col) {
            $connection = DB::connection();
            if ($request->column == "referencia") {
                $filtro = explode("|", $request->filter);
                $where .= " AND referencia NOT IN (
                    SELECT pr_valor
                    FROM vatbold
                    WHERE psm_valor = ".$connection->getPdo()->quote($filtro[1])."
                      AND psm_chave = ".$connection->getPdo()->quote($filtro[0])."
                      AND pr_chave = 'R'
                )";
            } else $where .= " AND ".$filter_col." = ".$connection->getPdo()->quote($request->filter);
        }
        
        $resultado = array();

        $consulta = DB::table($tabela)
                        ->select(
                            $request->column,
                            DB::raw($request->column == "referencia" ? "MIN(id) AS id" : "id")
                        )
                        ->whereRaw($where)
                        ->where(function($sql) use ($request) {
                            if (strpos($request->table, "todos") === false) $sql->where("lixeira", (strpos($request->table, "lixeira") !== false) ? 1 : 0);
                        })
                        ->where($request->column, "LIKE", "%".$request->search."%")
                        ->groupby(DB::raw($request->column == "referencia" ? "referencia" : "id,".$request->column))
                        ->orderby($request->column)
                        ->take(30)
                        ->get();

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
        return $this->consultar_geral_main($request->tabela, $request->id, $request->filtro); // App\Http\Controllers\Controller.php
    }

    public function consultar_simples(Request $request) {
        return !intval($this->consultar_geral($request)) && $request->filtro ? "erro" : "ok";
    }

    public function permissoes() {
        return json_encode($this->obter_permissao());
    }

    public function pode_abrir($tabela, $id) {
        return json_encode($this->pode_abrir_main($tabela, $id, "editar")); // App\Http\Controllers\Controller.php
    }

    public function descartar(Request $request) {
        $this->alterar_usuario_editando($request->tabela, $request->id, true); // App\Http\Controllers\Controller.php
    }
}