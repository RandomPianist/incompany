<?php

namespace App\Http\Controllers;

use DB;
use App\Models\Empresas;
use Illuminate\Http\Request;

class Api2Controller extends ControllerKX {
    private function comparar($a, $b) {
        return mb_strtoupper($a) == mb_strtoupper($b);
    }

    private function maquinas($cft) {
        DB::table("valores")
                    ->select(
                        "valores.id",
                        "valores.seq",
                        "valores.descr",
                        DB::raw("
                            CASE
                                WHEN ((CURDATE() BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim)) THEN 'S'
                                ELSE 'N'
                            END AS ativo
                        ")
                    )
                    ->join("comodatos", "comodatos.id_maquina", "valores.id")
                    ->whereIn(
                        "comodatos.id_empresa",
                        DB::table("empresas")
                            ->select("id")
                            ->where("cod_externo", $cft)
                            ->unionAll(
                                DB::table("empresas")
                                    ->select("filiais.id")
                                    ->join("empresas AS filiais", "filiais.id_matriz", "empresas.id")
                                    ->where("empresas.cod_externo", $cft)
                            )
                            ->pluck("id")
                            ->toArray()
                    )
                    ->where("valores.lixeira", 0);
    }

    public function maquinas_por_cliente(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return $this->maquinas($request->cft)->get();
    }

    public function consultar_maquina(Request $request) {
        if ($request->token != config("app.key")) return 401;
        if (sizeof(
            $this->maquinas($request->cft)
                ->where("valores.descr", $request->maq)
                ->get()
        )) return "CLIENTE";
        if (sizeof(
            DB::table("valores")
                ->where("descr", $request->maq)
                ->where("lixeira", 0)
                ->get()
        )) return "MAQUINA";
        return "OK";
    }

    public function criar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $cnpj = filter_var($str, $request->cnpj);
        $id_empresa = DB::table("empresas")
                        ->where("cnpj", $cnpj)
                        ->value("id");
        if ($id_empresa !== null) {
            $empresa = Empresas::find($id_empresa);
            $alterou = false;
            if ($this->comparar($empresa->nome_fantasia, $request->emp_fantasia)) $alterou = true;
            if ($this->comparar($empresa->razao_social, $request->razao)) $alterou = true;
            
        }
    }

    public function produtos(Request $request) {
        if ($request->token != config("app.key")) return 401;
    }

    public function sincronizar(Request $request) {
        if ($request->token != config("app.key")) return 401;
    }

    public function pode_faturar(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return sizeof(
            $this->maquinas($request->cft)
                        ->whereRaw("((CURDATE() BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim))")
                        ->where("valores.id", $request->maq)
                        ->get()
        ) ? "OK" : "ERRO";
    }
}