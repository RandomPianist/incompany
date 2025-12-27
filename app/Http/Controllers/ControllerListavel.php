<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class ControllerListavel extends Controller {
    public function listar(Request $request) {
        $filtro = trim($request->filtro);
        
        $poss_cpf = str_replace([".", "-"], "", $filtro);
        
        if ($filtro) {
            $filtro = str_replace("  ", " ", $filtro);
            $campo = $poss_cpf == preg_replace("/[^0-9]/", "", $poss_cpf) ? "!" : "?";
            if ($campo == "!") $filtro = $poss_cpf;
            $busca = $this->busca("$campo LIKE ?", [$filtro . '%'], $request->tipo);

            if (count($busca) < 3) {
                $busca = $this->busca("$campo LIKE ?", ['%' . $filtro . '%'], $request->tipo);
            }

            if (count($busca) < 3) {
                $palavras = explode(" ", str_replace("  ", " ", $filtro));
                $sql_parts = [];
                $bindings = [];

                foreach ($palavras as $palavra) {
                    if (trim($palavra) !== '') {
                        $sql_parts[] = "$campo LIKE ?";
                        $bindings[] = '%' . $palavra . '%';
                    }
                }

                if (count($sql_parts) > 0) {
                    $sql_final = "(" . implode(" AND ", $sql_parts) . ")";
                    $busca = $this->busca($sql_final, $bindings, $request->tipo);
                }
            }
        } else {
            $busca = $this->busca("1 = 1", [], $request->tipo);
        }

        foreach($busca as $linha) {
            if (isset($linha->foto)) $linha->foto = asset("storage/".$linha->foto);
        }
        return json_encode($busca);
    }

    abstract protected function busca($sql, $bindings = [], $tipo = "");
}