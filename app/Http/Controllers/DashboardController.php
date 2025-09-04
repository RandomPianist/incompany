<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Pessoas;

class DashboardController extends Controller {
    private function formatar_data($data) {
        $arr = explode("-", $data);
        $resultado = $arr[0];
        for ($i = 1; $i < sizeof($arr); $i++) {
            $aux = "";
            if (strlen($arr[$i]) == 1) $aux = "0";
            $aux .= $arr[$i];
            $resultado .= "-".$aux;
        }
        return $resultado;
    }

    private function ultimas_retiradas_main($id_pessoa, $inicio = "", $fim = "") {
        if (!$inicio) $inicio = date("Y-m")."-01";
        if (!$fim) $fim = date("Y-m-d");
        $ultimas_retiradas = DB::table("pessoas")
                                ->select(
                                    "pessoas.id",
                                    "pessoas.foto",
                                    "pessoas.nome"
                                )
                                ->joinsub(
                                    DB::table("retiradas")
                                        ->selectRaw("DISTINCTROW id_pessoa")
                                        ->whereRaw($this->obter_where($id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
                                        ->whereRaw("retiradas.data >= '".$inicio."'")
                                        ->whereRaw("retiradas.data <= '".$fim."'"),
                                    "ret",
                                    "ret.id_pessoa",
                                    "pessoas.id"
                                )
                                ->whereRaw($this->obter_where($id_pessoa, "pessoas", true)) // App\Http\Controllers\Controller.php
                                ->get();
        foreach ($ultimas_retiradas as $retirada) $retirada->foto = asset("storage/".$retirada->foto);
        return $ultimas_retiradas;
    }

    private function retiradas_por_setor_main($id_pessoa, $inicio = "", $fim = "") {
        if (!$inicio) $inicio = date("Y-m")."-01";
        if (!$fim) $fim = date("Y-m-d");
        return collect(
            DB::table("retiradas")
                ->select(
                    "setores.id",
                    "setores.descr",
                    DB::raw("SUM(retiradas.qtd) AS retirados"),
                    DB::raw("SUM(retiradas.preco) AS valor")
                )
                ->join("setores", "setores.id", "retiradas.id_setor")
                ->whereRaw("retiradas.data >= '".$inicio."'")
                ->whereRaw("retiradas.data <= '".$fim."'")
                ->whereRaw($this->obter_where($id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
                ->whereRaw($this->obter_where($id_pessoa, "setores")) // App\Http\Controllers\Controller.php
                ->groupby(
                    "setores.id",
                    "setores.descr"
                )
                ->get()
        )->groupBy("id")->map(function($itens) {
            return [
                "id" => $itens[0]->id,
                "descr" => $itens[0]->descr,
                "retirados" => $itens->sum("retirados"),
                "valor" => $itens->sum("valor")
            ];
        })->sortByDesc("valor")->values()->all();
    }

    private function maquinas_main($inicio, $fim) {
        return DB::table("valores")
                    ->select(
                        "id",
                        "descr"
                    )
                    ->whereIn(
                        "id",
                        DB::table("comodatos")
                            ->select(
                                "minhas_empresas.id_pessoa",
                                "comodatos.id_maquina"
                            )
                            ->joinsub(
                                DB::table("pessoas")
                                    ->select(
                                        "id AS id_pessoa",
                                        "id_empresa"
                                    )
                                    ->unionAll(
                                        DB::table("pessoas")
                                            ->select(
                                                "pessoas.id AS id_pessoa",
                                                "filiais.id AS id_empresa"
                                            )
                                            ->join("empresas AS filiais", "filiais.id_matriz", "pessoas.id_empresa")
                                    ),
                                "minhas_empresas",
                                "minhas_empresas.id_empresa",
                                "comodatos.id_empresa"
                            )
                            ->whereRaw("(('".$inicio."' BETWEEN comodatos.inicio AND comodatos.fim) OR ('".$fim."' BETWEEN comodatos.inicio AND comodatos.fim))")
                            ->where("id_pessoa", Auth::user()->id_pessoa)
                            ->pluck("id_maquina")
                            ->toArray()
                    )
                    ->get();
    }

    private function retiradas_em_atraso_main($id_pessoa) {
        $atrasos = DB::table("pessoas")
                        ->select(
                            "pessoas.id",
                            "pessoas.nome",
                            "pessoas.foto",
                            DB::raw("ROUND(pendente.qtd) AS total")
                        )
                        ->joinsub(
                            DB::table(DB::raw("(
                                SELECT
                                    id_atribuicao,
                                    id_pessoa,
                                    qtd
                                    
                                FROM vpendentes

                                WHERE esta_pendente = 1
                                
                                GROUP BY
                                    id_atribuicao,
                                    id_pessoa,
                                    qtd
                            ) AS aux"))
                                ->select(
                                    "id_pessoa",
                                    DB::raw("SUM(qtd) AS qtd")
                                )
                                ->groupby("id_pessoa"),
                            "pendente",
                            "pendente.id_pessoa",
                            "pessoas.id"
                        )
                        ->whereRaw($this->obter_where($id_pessoa)) // App\Http\Controllers\Controller.php
                        ->orderby("pendente.qtd", "DESC")
                        ->get();

        foreach ($atrasos as $pessoa) $pessoa->foto = asset("storage/".$pessoa->foto);
        return $atrasos;
    }

    public function iniciar() {
        return view("dashboard");
    }

    public function maquinas(Request $request) {
        return json_encode($this->maquinas_main($request->inicio, $request->fim));
    }

    public function dados(Request $request) {
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $this->formatar_data($request->inicio);
        if (isset($request->fim)) $fim = $this->formatar_data($request->fim);
        $resultado = new \stdClass;

        $id_pessoa = Auth::user()->id_pessoa;
        $retiradas_por_setor = new \stdClass;
        $aux = $this->retiradas_por_setor_main($id_pessoa, $inicio, $fim);
        $total_val = 0;
        $total_qtd = 0;
        foreach ($aux as $rps) {
            $total_qtd += floatval($rps["retirados"]);
            $total_val += floatval($rps["valor"]);
        }
        $retiradas_por_setor->retiradas = $aux;
        $retiradas_por_setor->totalQtd = $total_qtd;
        $retiradas_por_setor->totalVal = $total_val;
        
        $ranking = DB::table("retiradas")
                    ->select(
                        "pessoas.id",
                        "pessoas.nome",
                        "pessoas.foto",
                        DB::raw("SUM(retiradas.qtd) AS retirados")
                    )
                    ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                    ->whereRaw($this->obter_where($id_pessoa, "pessoas", true)) // App\Http\Controllers\Controller.php
                    ->whereRaw($this->obter_where($id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->groupby(
                        "pessoas.id",
                        "pessoas.nome",
                        "pessoas.foto"
                    )
                    ->orderby("retirados", "DESC")
                    ->orderby("pessoas.nome")
                    ->get();
        foreach ($ranking as $pessoa) $pessoa->foto = asset("storage/".$pessoa->foto);
        
        $resultado->atrasos = $inicio == date("Y-m")."-01" ? $this->retiradas_em_atraso_main($id_pessoa) : [];
        $resultado->ultimasRetiradas = $inicio == date("Y-m")."-01" ? $this->ultimas_retiradas_main($id_pessoa, $inicio, $fim) : [];
        $resultado->retiradasPorSetor = $retiradas_por_setor;
        $resultado->ranking = $ranking;
        $resultado->maquinas = $this->maquinas_main($inicio, $fim);
        return json_encode($resultado);
    }

    public function det_retiradas_por_pessoa(Request $request) {
        $id_pessoa = $request->id_pessoa;
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $request->inicio;
        if (isset($request->fim)) $fim = $request->fim;
        return json_encode(
            collect(
                DB::table("retiradas")
                    ->select(
                        "produtos.id",
                        "produtos.descr AS produto",
                        DB::raw("ROUND(SUM(retiradas.qtd)) AS qtd")
                    )
                    ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                    ->join("produtos", "produtos.id", "retiradas.id_produto")
                    ->where("retiradas.id_pessoa", $id_pessoa)
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->groupby(
                        "produtos.id",
                        "produtos.descr"
                    )
                    ->get()
            )->sortByDesc("qtd")->values()->all()
        );
    }

    public function det_ultimas_retiradas(Request $request) {
        $id_pessoa = $request->id_pessoa;
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $request->inicio;
        if (isset($request->fim)) $fim = $request->fim;
        return json_encode(
            collect(
                DB::table("retiradas")
                    ->select(
                        "retiradas.id AS id_retirada",
                        "produtos.id",
                        "produtos.descr AS produto",
                        DB::raw("ROUND(retiradas.qtd) AS qtd"),
                        DB::raw("DATE_FORMAT(retiradas.data, '%d/%m/%Y') AS data")
                    )
                    ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                    ->join("produtos", "produtos.id", "retiradas.id_produto")
                    ->where("retiradas.id_pessoa", $id_pessoa)
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->get()
            )->sortBy("id_retirada")->values()->all()
        );
    }

    public function det_retiradas_por_setor(Request $request) {
        $id_setor = $request->id_setor;
        $inicio = date("Y-m")."-01";
        $fim = date("Y-m-d");
        if (isset($request->inicio)) $inicio = $request->inicio;
        if (isset($request->fim)) $fim = $request->fim;
        return collect(
            DB::table("retiradas")
                    ->select(
                        "pessoas.id",
                        "pessoas.nome",
                        DB::raw("SUM(retiradas.qtd) AS retirados"),
                        DB::raw("SUM(retiradas.preco) AS valor")
                    )
                    ->join("pessoas", "pessoas.id", "retiradas.id_pessoa")
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa)) // App\Http\Controllers\Controller.php
                    ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "retiradas")) // App\Http\Controllers\Controller.php
                    ->whereRaw("retiradas.data >= '".$inicio."'")
                    ->whereRaw("retiradas.data <= '".$fim."'")
                    ->where("retiradas.id_setor", $id_setor)
                    ->groupby(
                        "pessoas.id",
                        "pessoas.nome"
                    )
                    ->get()
        )->groupby("id")->map(function($itens) {
            return [
                "id" => $itens[0]->id,
                "nome" => $itens[0]->nome,
                "retirados" => $itens->sum("retirados"),
                "valor" => $itens->sum("valor")
            ];
        })->sortByDesc("valor")->values()->all();
    }

    // API
    public function produtos_em_atraso($id_pessoa) {
        return json_encode(
            DB::table("vpendentes")
                ->select(
                    "id_produto AS id",
                    "validade",
                    "qtd",
                    "nome_produto AS produto",
                    DB::raw("
                        CASE
                            WHEN produto_ou_referencia_chave = 'P' THEN descr
                            ELSE CONCAT('REF: ', referencia)
                        END AS nome_produto
                    ")
                )
                ->where("id_pessoa", $id_pessoa)
                ->where("esta_pendente", 1)
                ->groupby(
                    "id_produto",
                    "validade",
                    "qtd",
                    "nome_produto",
                    "produto_ou_referencia_chave",
                    DB::raw("
                        CASE
                            WHEN produto_ou_referencia_chave = 'P' THEN descr
                            ELSE referencia
                        END
                    ")
                )
                ->orderby("qtd", "DESC")
                ->get()
        );
    }

    public function ultimas_retiradas($id_pessoa) {
        return json_encode($this->ultimas_retiradas_main($id_pessoa));
    }

    public function retiradas_por_setor($id_pessoa) {
        return json_encode($this->retiradas_por_setor_main($id_pessoa));
    }

    public function retiradas_em_atraso($id_pessoa) {
        return json_encode($this->retiradas_em_atraso_main($id_pessoa));
    }
}