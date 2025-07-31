<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Comodatos;
use App\Models\Estoque;
use App\Models\MaquinasProdutos;

class MaquinasController extends ControllerKX {
    public function estoque(Request $request) {
        for ($i = 0; $i < sizeof($request->id_produto); $i++) {
            $linha = new Estoque;

            $qtdRequest = floatval($request->qtd[$i]);
            $ajusteIgualEstoque = false;
            if ($request->es[$i] == "A") {
                $saldo = $this->retorna_saldo_mp($request->id_maquina, $request->id_produto[$i]);
                if ($saldo > $qtdRequest) {
                    $linha->es = "S";
                    $linha->qtd = $saldo - $qtdRequest;
                } else if ($saldo < $qtdRequest) {
                    $linha->es = "E";
                    $linha->qtd = ($saldo - $qtdRequest) * -1;
                } else $ajusteIgualEstoque = true;
            } else {
                $linha->es = $request->es[$i];
                $linha->qtd = $qtdRequest;
            }

            if (!$ajusteIgualEstoque) {
                $linha->descr = $request->obs[$i];
                $linha->preco = $request->preco[$i];
                $linha->id_mp = DB::table("maquinas_produtos")
                                    ->where("id_produto", $request->id_produto[$i])
                                    ->where("id_maquina", $request->id_maquina)
                                    ->value("id");
                $linha->save();
                $this->log_inserir("C", "estoque", $linha->id);
                $gestor = MaquinasProdutos::find($linha->id_mp);
                if ($gestor->preco != $linha->preco) {
                    $gestor->preco = $linha->preco;
                    $this->log_inserir("E", "maquinas_produtos", $gestor->id);
                }
            }
        }
        return redirect("/valores/maquinas");
    }

    public function consultar_estoque(Request $request) {
        $texto = "";
        $campos = array();
        $valores = array();

        $produtos_id = explode(",", $request->produtos_id);
        $produtos_descr = explode(",", $request->produtos_descr);
        $quantidades = explode(",", $request->quantidades);
        $precos = explode(",", $request->precos);
        $es = explode(",", $request->es);

        for ($i = 0; $i < sizeof($produtos_id); $i++) {
            if (!sizeof(
                DB::table("produtos")
                    ->where("id", $produtos_id[$i])
                    ->where("descr", $produtos_descr[$i])
                    ->where("lixeira", 0)
                    ->get()
            )) {
                array_push($campos, "produto-".($i + 1));
                array_push($valores, $produtos_descr[$i]);
                $texto = !$texto ? "Produtos não encontrados" : "Produto não encontrado";
            }
        }

        if (!$texto) {
            for ($i = 0; $i < sizeof($produtos_id); $i++) {
                $saldo = $this->retorna_saldo_mp($request->id_maquina, $produtos_id[$i]);
                if (
                    $es[$i] == "S" &&
                    ($saldo - floatval($quantidades[$i])) < 0
                ) {
                    array_push($campos, "qtd-".($i + 1));
                    array_push($valores, $saldo);
                    $linha2 = $texto ? "Os campos foram corrigidos" : "O campo foi corrigido";
                    $linha2 .= " para zerar o estoque.<br>Por favor, verifique e tente novamente.";
                    $texto = "Essa movimentação de estoque provocaria estoque negativo.<br>".$linha2;
                }
            }
        }

        if (!$texto) {
            for ($i = 0; $i < sizeof($produtos_id); $i++) {
                if (!intval($precos[$i])) {
                    $texto = $texto ? "Há preços zerados" : "Há um preço zerado";
                    array_push($campos, "preco-".($i + 1));
                    array_push($valores, "0");
                }
            }
        }

        $resultado = new \stdClass;
        $resultado->texto = $texto;
        $resultado->campos = $campos;
        $resultado->valores = $valores;
        return json_encode($resultado);
    }

    public function consultar_comodato(Request $request) {
        $resultado = new \stdClass;
        $resultado->texto = "";
        if ($this->empresa_consultar($request)) $resultado->texto = "Empresa não encontrada";
        if (!$resultado->texto) {
            $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
            $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
            $consulta = DB::table("comodatos")
                            ->select(
                                DB::raw("
                                    CONCAT(
                                        valores.descr, ' ',
                                        CASE
                                            WHEN (CURDATE() > fim) THEN 'esteve'
                                            WHEN (CURDATE() >= inicio) THEN 'está'
                                            ELSE 'estará'
                                        END,
                                        ' comodatada entre ',
                                        DATE_FORMAT(inicio, '%d/%m/%Y'), ' e ', DATE_FORMAT(fim, '%d/%m/%Y')
                                    ) AS texto
                                "),
                                DB::raw("
                                    CASE
                                        WHEN inicio >= '".$inicio."' THEN 'S'
                                        ELSE 'N'
                                    END AS invalida_inicio
                                "),
                                DB::raw("
                                    CASE
                                        WHEN fim < '".$fim."' THEN 'S'
                                        ELSE 'N'
                                    END AS invalida_fim
                                ")
                            )->join("valores", "valores.id", "comodatos.id_maquina")
                            ->whereRaw("(('".$inicio."' BETWEEN comodatos.inicio AND comodatos.fim) OR ('".$fim."' BETWEEN comodatos.inicio AND comodatos.fim))")
                            ->where("comodatos.inicio", "<>", "comodatos.fim")
                            ->where("id_maquina", $request->id_maquina)
                            ->get();
            if (sizeof($consulta)) $resultado = $consulta[0];
        }
        return json_encode($resultado);
    }

    public function criar_comodato(Request $request) {
        $this->criar_comodato_main($request->id_maquina, $request->id_empresa, $request->inicio, $request->fim);
        return redirect("/valores/maquinas");
    }

    public function encerrar_comodato(Request $request) {
        $modelo = Comodatos::find(
            DB::table("comodatos")
                ->whereRaw("CURDATE() >= inicio AND CURDATE() < fim")
                ->where("id_maquina", $request->id_maquina)
                ->value("id")
        );
        $modelo->fim = date('Y-m-d');
        $modelo->save();
        $this->log_inserir("E", "comodatos", $modelo->id);
        return redirect("/valores/maquinas");
    }

    public function historico(Request $request) {
        $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
        $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
        $consulta = $request->tipo == "retirada" ?
            DB::table("retiradas")
                ->select(
                    "funcionario.nome AS funcionario",
                    DB::raw("IFNULL(supervisor.nome, '') AS supervisor"),
                    DB::raw("
                        CASE
                            WHEN log.origem = 'WEB' THEN autor.nome
                            ELSE ''
                        END
                    "),
                    DB::raw("DATE_FORMAT(retiradas.data, '%d/%m/%Y') AS data"),
                    DB::raw("ROUND(retiradas.qtd) AS qtd")
                )
                ->join("log", function($join) {
                    $join->on("log.fk", "retiradas.id")
                        ->where("log.tabela", "retiradas")
                        ->where("log.acao", "C");
                })
                ->join("comodatos", "comodatos.id", "retiradas.id_comodato")
                ->join("pessoas AS funcionario", "funcionario.id", "retiradas.id_pessoa")
                ->leftjoin("pessoas AS supervisor", "supervisor.id", "retiradas.id_supervisor")
                ->leftjoin("pessoas AS autor", "autor.id", "log.id_pessoa")
                ->whereRaw("((CURDATE() BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim))")
                ->whereRaw("retiradas.data >= '".$inicio."'")
                ->whereRaw("retiradas.data <= '".$fim."'")
                ->where("comodatos.id_maquina", $request->id_maquina)
                ->where("retiradas.id_produto", $request->id_produto)
        :
            DB::table("maquinas_produtos AS mp")
                ->select(
                    DB::raw("CONCAT(IFNULL(CONCAT(log.nome, ' ('), ''), log.origem, CASE WHEN log.nome IS NOT NULL THEN ')' ELSE '' END) AS origem"),
                    DB::raw("DATE_FORMAT(log.data, '%d/%m/%Y') AS data"),
                    DB::raw("ROUND(estoque.qtd) AS qtd")
                )
                ->join("estoque", "estoque.id_mp", "mp.id")
                ->join("log", function($join) {
                    $join->on("log.fk", "estoque.id")
                        ->where("log.tabela", "estoque");
                })
                ->where(function($sql) use ($request) {
                    $sql->where("estoque.es", $request->tipo == "entrada" ? "E" : "S");
                })
                ->whereRaw("log.data >= '".$inicio."'")
                ->whereRaw("log.data <= '".$fim."'")
                ->where("mp.id_maquina", $request->id_maquina)
                ->where("mp.id_produto", $request->id_produto);
        return json_encode($consulta->get());
    }
}