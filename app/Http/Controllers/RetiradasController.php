<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use App\Models\Pessoas;
use App\Models\Empresas;
use App\Models\Retiradas;
use App\Models\Permissoes;
use Illuminate\Http\Request;

class RetiradasController extends Controller {
    private function permitir(Request $request) {
        if (!Permissoes::where("id_usuario", Auth::user()->id)->retiradas) return 401;
        $emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
        $pessoa = Pessoas::find($request->id_pessoa);
        $ok = true;
        if ($emp) $ok = ($pessoa->id_empresa == $emp || $pessoa->id_empresa == Empresas::find($emp)->id_matriz);
        if (!$ok) return 401;
        return 200;
    }

    public function consultar(Request $request) {
        return $this->retirada_consultar($request->atribuicao, $request->qtd, $request->pessoa); // App\Http\Controllers\Controller.php
    }

    public function salvar(Request $request) {
        if ($this->permitir() == 401) return 401;
        $json = array(
            "id_pessoa" => $request->pessoa,
            "id_atribuicao" => $request->atribuicao,
            "id_produto" => $request->produto,
            "id_comodato" => 0,
            "qtd" => $request->quantidade,
            "data" => Carbon::createFromFormat('d/m/Y', $request->data)->format('Y-m-d')
        );
        if (intval($request->supervisor)) $json["id_supervisor"] = $request->supervisor;
        $this->retirada_salvar($json); // App\Http\Controllers\Controller.php
        DB::statement("CALL atualizar_mat_vretiradas_vultretirada('P', ".$request->pessoa.", 'R', 'N')");
        DB::statement("CALL atualizar_mat_vretiradas_vultretirada('P', ".$request->pessoa.", 'U', 'N')");
    }

    public function desfazer(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php
        if ($this->permitir() == 401) return 401;
        $where = "id_pessoa = ".$request->id_pessoa;
        $this->log_inserir_lote("D", "retiradas", $where); // App\Http\Controllers\Controller.php
        Retiradas::whereRaw($where)->delete();
        return 200;
    }

    public function proximas($id_pessoa) {
        $hoje = Carbon::today();
        $consulta = DB::table("vpendentesgeral")
                        ->select(
                            DB::raw("IFNULL(produtos.cod_externo, produtos.id) AS id_produto"),
                            "vpendentesgeral.descr",
                            DB::raw("IFNULL(vpendentesgeral.referencia, '') AS referencia"),
                            "vpendentesgeral.tamanho",
                            "vpendentesgeral.qtd",
                            "vpendentesgeral.proxima_retirada_real",
                            "vpendentesgeral.obrigatorio",
                            "vpendentesgeral.esta_pendente"
                        )
                        ->join("produtos", "produtos.id", "vpendentesgeral.id_produto")
                        ->where("vpendentesgeral.id_pessoa", $id_pessoa)
                        ->groupby(
                            "produtos.id",
                            "produtos.cod_externo",
                            "vpendentesgeral.descr",
                            "vpendentesgeral.referencia",
                            "vpendentesgeral.tamanho",
                            "vpendentesgeral.qtd",
                            "vpendentesgeral.proxima_retirada_real",
                            "vpendentesgeral.obrigatorio",
                            "vpendentesgeral.esta_pendente"
                        )
                        ->orderby(DB::raw("obrigatorio DESC, DATE(proxima_retirada_real)"))
                        ->get();
        $resultado = array();
        foreach ($consulta as $linha) {
            $proxima_retirada = Carbon::parse($linha->proxima_retirada_real);
            $dias = $proxima_retirada->diffInDays($hoje);
            if (intval($linha->esta_pendente)) $dias *= -1;
            $aux = new \stdClass;
            $aux->id_produto = $linha->id_produto;
            $aux->descr = $linha->descr;
            $aux->referencia = $linha->referencia;
            $aux->tamanho = $linha->tamanho;
            $aux->qtd = $linha->qtd;
            $aux->proxima_retirada = $proxima_retirada->format("d/m/Y");
            $aux->dias = $dias;
            array_push($resultado, $aux);
        }
        return json_encode($resultado);
    }
}