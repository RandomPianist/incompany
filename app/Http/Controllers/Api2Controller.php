<?php

namespace App\Http\Controllers;

use DB;
use App\Models\Empresas;
use App\Models\Valores;
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
                        ->orWhere("cod_externo", $request->emp_cod)
                        ->value("id");
        $continua = false;
        $empresa = null;
        if ($id_empresa !== null) {
            $empresa = Empresas::find($id_empresa);
            if (intval($empresa->lixeira)) return "EXCLUIDO";
            if ($this->comparar($empresa->cnpj, $cnpj)) $continua = true;
            if ($this->comparar($empresa->razao_social, $request->emp_razao)) $continua = true;
            if ($this->comparar($empresa->nome_fantasia, $request->emp_fantasia)) $continua = true;   
        } else $empresa = new Empresas;
        if ($continua) {
            $empresa->cnpj = $cnpj;
            $empresa->razao_social = $request->emp_razao;
            $empresa->nome_fantasia = $request->emp_fantasia;
            $empresa->cod_externo = $request->emp_cod;
            $empresa->save();
            $this->log_inserir($id_empresa !== null ? "E" : "C", "empresas", $empresa->id, "ERP", $request->usu);
        }
        $maquina = new Valores;
        $maquina->descr = mb_strtoupper($request->maq);
        $maquina->alias = "maquinas";
        $maquina->seq = intval(
            DB::table("valores")
                ->selectRaw("IFNULL(MAX(seq), 0) AS ultimo")
                ->where("alias", "maquinas")
                ->value("ultimo")
        ) + 1;
        $maquina->save();
        $this->log_inserir("C", "valores", $maquina->id);
        $this->criar_mp("produtos.id", $maquina->id, true, $request->usu);
        $this->criar_comodato_main($maquina->id, $empresa->id, $request->ini, $request->fim);
        return $empresa->id;
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