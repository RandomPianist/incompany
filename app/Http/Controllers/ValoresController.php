<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Pessoas;
use App\Models\Valores;

class ValoresController extends ControllerKX {
    private function busca($alias, $where) {
        return DB::table("valores")
                    ->select(
                        "valores.id",
                        "valores.seq",
                        "valores.descr",
                        "valores.alias",
                        DB::raw("
                            CASE
                                WHEN aux3.id_maquina IS NOT NULL THEN 'S'
                                ELSE 'N'
                            END AS tem_mov
                        "),
                        DB::raw("
                            CASE
                                WHEN aux2.id IS NOT NULL THEN CONCAT(
                                    aux2.nome_fantasia,
                                    ' até ',
                                    aux1.fim_formatado
                                ) ELSE '---'
                            END AS comodato
                        ")
                    )
                    ->leftjoinSub(
                        DB::table("comodatos")
                            ->select(
                                "id_maquina",
                                "id_empresa",
                                DB::raw("DATE_FORMAT(fim, '%d/%m/%Y') AS fim_formatado")
                            )
                            ->whereRaw("CURDATE() >= inicio")
                            ->whereRaw("CURDATE() < fim"),
                    "aux1", "aux1.id_maquina", "valores.id")
                    ->leftjoinSub(
                        DB::table("empresas")
                            ->select(
                                "id",
                                "id_matriz",
                                "nome_fantasia"
                            )
                            ->where("lixeira", 0),
                    "aux2", "aux2.id", "aux1.id_empresa")
                    ->leftjoinSub(
                        DB::table("maquinas_produtos AS mp")
                            ->selectRaw("DISTINCTROW id_maquina")
                            ->join("estoque", "estoque.id_mp", "mp.id"),
                    "aux3", "aux3.id_maquina", "valores.id")
                    ->where(function($sql) use ($alias) {
                        if ($alias == "maquinas") {
                            $id_emp = intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
                            if ($id_emp) $sql->whereRaw($id_emp." IN (aux2.id, aux2.id_matriz)");
                        }
                    })
                    ->whereRaw($where)
                    ->where("alias", $alias)
                    ->where("lixeira", 0)
                    ->get();
    }

    private function aviso_main($alias, $id) {
        $aviso = "";
        if ($alias == "maquinas") {
            $aviso = DB::table("valores")
                        ->selectRaw("
                            CASE
                                WHEN (tab_comodatos.id_maquina IS NOT NULL) THEN CONCAT('está comodatada para ', tab_comodatos.empresa, ' até ', tab_comodatos.fim)
                                WHEN (tab_estoque.saldo <> 0) THEN 'possui saldo diferente de zero'
                                ELSE ''
                            END AS aviso
                        ")
                        ->leftjoinSub(
                            DB::table(DB::raw("(
                                SELECT
                                    CASE
                                        WHEN (es = 'E') THEN qtd
                                        ELSE qtd * -1
                                    END AS qtd,
                                    id_mp
                        
                                FROM estoque
                            ) AS estq"))
                            ->select(
                                DB::raw("IFNULL(SUM(qtd), 0) AS saldo"),
                                "mp.id_maquina"
                            )
                            ->join("maquinas_produtos AS mp", "mp.id", "estq.id_mp")
                            ->groupby("id_maquina"),
                        "tab_estoque", "tab_estoque.id_maquina", "valores.id")
                        ->leftjoinSub(
                            DB::table("comodatos")
                                ->select(
                                    "id_maquina",
                                    "empresas.nome_fantasia AS empresa",
                                    DB::raw("DATE_FORMAT(fim, '%d/%m/%Y') AS fim")
                                )
                                ->join("empresas", "empresas.id", "comodatos.id_empresa")
                                ->whereRaw("CURDATE() >= inicio")
                                ->whereRaw("CURDATE() < fim"),
                        "tab_comodatos", "tab_comodatos.id_maquina", "valores.id")
                        ->where("valores.id", $id)
                        ->get()[0]->aviso;
            $vinculo = $aviso != "";
        } else {
            $vinculo = sizeof(
                DB::table("produtos")
                    ->where("id_categoria", $id)
                    ->where("lixeira", 0)
                    ->get()
            ) > 0;
        }
        $resultado = new \stdClass;
        $nome = Valores::find($id)->descr;
        $resultado->permitir = !$vinculo || $alias == "categorias" ? 1 : 0;
        $resultado->aviso = $vinculo ?
            $alias == "categorias" ?
                "Não é recomendado excluir ".$nome." porque existem produtos vinculados a essa categoria.<br>Deseja prosseguir assim mesmo?"
            :
                "Não é possível excluir ".$nome." porque essa máquina ".$aviso
        : "Tem certeza que deseja excluir ".$nome."?";
        return $resultado;
    }

    public function ver($alias) {
        $comodato = false;
        if ($alias == "maquinas") {
            $busca = $this->busca($alias, "1");
            foreach($busca as $linha) {
                if ($linha->comodato != "---") $comodato = true;
            }
        }
        $ultima_atualizacao = $this->log_consultar("valores", $alias);
        $titulo = $alias == "maquinas" ? "Máquinas" : "Categorias";
        return view("valores", compact("alias", "titulo", "ultima_atualizacao", "comodato"));
    }

    public function listar($alias, Request $request) {
        $filtro = trim($request->filtro);
        if ($filtro) {
            $busca = $this->busca($alias, "descr LIKE '".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca($alias, "descr LIKE '%".$filtro."%'");
            if (sizeof($busca) < 3) $busca = $this->busca($alias, "(descr LIKE '%".implode("%' AND descr LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')");
        } else $busca = $this->busca($alias, "1");
        return json_encode($busca);
    }

    public function consultar($alias, Request $request) {
        if (sizeof(
            DB::table("valores")
                ->where("alias", $alias)
                ->where("lixeira", 0)
                ->where("descr", $request->descr)
                ->get()
        )) return "1";
        return "0";
    }

    public function mostrar($alias, $id) {
        return Valores::find($id)->descr;
    }

    public function aviso($alias, $id) {
        return json_encode($this->aviso_main($alias, $id));
    }

    public function salvar($alias, Request $request) {
        if (intval($this->consultar($alias, $request))) return 401;
        $linha = Valores::firstOrNew(["id" => $request->id]);
        $linha->descr = mb_strtoupper($request->descr);
        $linha->alias = $alias;
        if (!$request->id) {
            $linha->seq = intval(
                DB::table("valores")
                    ->selectRaw("IFNULL(MAX(seq), 0) AS ultimo")
                    ->where("alias", $alias)
                    ->value("ultimo")
            ) + 1;
        }
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "valores", $linha->id);
        if ($alias == "maquinas") $this->criar_mp("produtos.id", $linha->id);
        return redirect("/valores/$alias");
    }

    public function excluir($alias, Request $request) {
        if (!$this->aviso_main($alias, $request->id)->permitir) return 401;
        $linha = Valores::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $where = "id_categoria = ".$request->id;
        DB::statement("UPDATE produtos SET id_categoria = NULL ".$where);
        $this->log_inserir("D", "valores", $linha->id);
        $this->log_inserir_lote("E", "WEB", "produtos", $where);
    }
}