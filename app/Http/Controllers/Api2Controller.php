<?php

namespace App\Http\Controllers;

use DB;
use App\Models\Produtos;
use App\Models\Pessoas;
use App\Models\Dedos;
use Illuminate\Http\Request;

class Api2Controller extends Controller {
    public function enviar_previas(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $id_pessoa = Pessoas::where("cpf", $request->cpf)->value("id");
        return $this->produtos_por_pessoa_main($id_pessoa, [
            ["seq", "desc"],
            ["obrigatorio", "desc"],
            ["nome", "asc"]
        ]); // App\Http\Controllers\Controller.php
    }

    public function receber_previa(Request $request) {
        if ($request->token != config("app.key")) return '{"status":401}';
        $id_produto = Produtos::where("cod_externo", $request->codbar)->value("id");
        $id_pessoa = Pessoas::where("cpf", $request->cpf)->value("id");
        if (!$id_produto || !$id_pessoa) return '{"status":404}';
        $base = $this->retorna_atb_aux("P", $id_pessoa, false, $id_pessoa); // App\Http\Controllers\Controller.php
        $id_atribuicao = DB::table(DB::raw("(".$base.") AS atb"))
                            ->where("atb.id_produto", $id_produto)
                            ->where("atb.lixeira", 0)
                            ->orderby("atb.grandeza")
                            ->value("id_atribuicao");
        if (!$id_atribuicao) return '{"status":403}';
        $pre_retiradas = DB::table('pre_retiradas')
                            ->where("id_pessoa", $id_pessoa)
                            ->where("id_produto", $id_produto)
                            ->count();
        $isPendente = DB::table("vatbold")
                        ->selectRaw(1)
                        ->where("vatbold.id", $id_atribuicao)
                        ->leftJoin("mat_vretiradas", function($join) use ($id_pessoa) {
                            $join->on("mat_vretiradas.id_atribuicao", "vatbold.id")
                                ->where("mat_vretiradas.id_pessoa", $id_pessoa);
                        })
                        // ->leftJoin("mat_vultretirada", function($join) use ($id_pessoa) {
                            // $join->on("mat_vultretirada.id_atribuicao", "vatbold.id")
                                // ->where("mat_vultretirada.id_pessoa", $id_pessoa);
                        // })
                        // ->whereRaw("DATE_ADD(IFNULL(mat_vultretirada.data, '1900-01-01'), INTERVAL vatbold.validade DAY) <= CURDATE()")
                        ->whereRaw("(vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + ?)) > 0", [$pre_retiradas])
                        ->exists();
    
        if (!$isPendente) return '{"status":403}';
    
        $previa = new PreRetiradas;
        $previa->id_pessoa = $id_pessoa;
        $previa->id_produto = $id_produto;
        $previa->save();
        return '{"status":201}';
    }

    public function limpar_previas(Request $request) {
        if ($request->token != config("app.key")) return 401;
        PreRetiradas::whereIn(
            "id_pessoa",
            Pessoas::where("cpf", $request->cpf)
                    ->pluck("id")
                    ->toArray()
        )->delete();
        return 200;
    }

    public function dedos(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return json_encode(
            DB::table("dedos")
                ->select(
                    "dedos.id",
                    "dedos.hash"
                )
                ->join("pessoas", "pessoas.id", "dedos.id_pessoa")
                ->where("pessoas.lixeira", 0)
                ->get()
        );
    }

    public function dedos_pessoa(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $id_pessoa = Pessoas::where("cpf", $request->cpf)->value("id");
        return json_encode(Dedos::where("id_pessoa", $id_pessoa)->get());
    }

    public function salvar_dedos(Request $request) {
        if ($request->token != config("app.key")) return 401;
        $id_pessoa = Pessoas::where("cpf", $request->cpf)->value("id");
        $letra_log = Dedos::where("id_pessoa", $id_pessoa)
                            ->where("dedo", $request->dedo)
                            ->exists() ? "E" : "C";
        Dedos::updateOrCreate(
            [
                "id_pessoa" => $id_pessoa,
                "dedo" => $request->dedo
            ],
            [
                "imagem" => $request->imagem,
                "hash" => $request->hash
            ]
        );
        $id = Dedos::where("id_pessoa", $id_pessoa)->where("dedo", $request->dedo)->value("id");
        $reg_log = $this->log_inserir($letra_log, "dedos", $id, "APP"); // App\Http\Controllers\Controller.php
        $reg_log->id_pessoa = $id_pessoa;
        $reg_log->nome = Pessoas::find($id_pessoa)->nome;
        $reg_log->save();
    }

    public function dedos_cpf(Request $request) {
        if ($request->token != config("app.key")) return 401;
        return json_encode(array(
            "cpf" => Dedos::find($request->id)->pessoa->cpf
        ));
    }
}