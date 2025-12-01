<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class ControllerListavel extends Controller {
    public function listar(Request $request) {
        $filtro = trim($request->filtro);
        $poss_cpf = str_replace(".", "", $filtro);
        $poss_cpf = str_replace("-", "", $poss_cpf);
        
        if ($filtro) {
            $campo = $poss_cpf == preg_replace("/[^0-9]/", "", $poss_cpf) ? "!" : "?";
            if ($campo == "!") $filtro = $poss_cpf;
            $busca = $this->busca($campo." LIKE '".$filtro."%'", $request->tipo);
            if (sizeof($busca) < 3) $busca = $this->busca($campo." LIKE '%".$filtro."%'", $request->tipo);
            if (sizeof($busca) < 3) $busca = $this->busca("(".$campo." LIKE '%".implode("%' AND ".$campo." LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')", $request->tipo);
        } else $busca = $this->busca("1", $request->tipo);
        foreach($busca as $linha) {
            if (isset($linha->foto)) $linha->foto = asset("storage/".$linha->foto);
        }
        return json_encode($busca);
    }

    abstract protected function busca($param, $tipo = "");
}