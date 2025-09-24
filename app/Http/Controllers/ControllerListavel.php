<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class ControllerListavel extends Controller {
    public function listar(Request $request) {
        $filtro = trim($request->filtro);
        if ($filtro) {
            $busca = $this->busca("? LIKE '".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("? LIKE '%".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca("(? LIKE '%".implode("%' AND ? LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')");
        } else $busca = $this->busca("1");
        foreach($busca as $linha) {
            if (isset($linha->foto)) $linha->foto = asset("storage/".$linha->foto);
        }
        return json_encode($busca);
    }

    abstract protected function busca($param);
}