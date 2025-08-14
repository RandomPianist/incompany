<?php

namespace App\Http\Controllers;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Comodatos;
use App\Models\Estoque;
use App\Models\MaquinasProdutos;

class MaquinasController extends Controller {
    private function consultar_estoque_main($produtos_id, $produtos_descr, $quantidades, $precos, $es) {
        $texto = "";
        $campos = array();
        $valores = array();

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

        if (!$texto) {
            for ($i = 0; $i < sizeof($produtos_id); $i++) {
                $prmin = floatval(
                    DB::table("produtos")
                        ->selectRaw("IFNULL(prmin, 0) AS prmin")
                        ->where("id", $produtos_id[$i])
                        ->value("prmin")
                );
                $preco = floatval($precos[$i]);
                if ($prmin > 0 && $preco < $prmin) {
                    $texto = $texto ? "Há itens com preço abaixo do mínimo.<br>Os campos foram corrigidos" : "Há um item com um preço abaixo do mínimo.<br>O campo foi corrigido";
                    $texto .= " para o preço mínimo.<br>Por favor, verifique e tente novamente.";
                    array_push($campos, "preco-".($i + 1));
                    array_push($valores, $prmin);
                }
            }
        }

        $resultado = new \stdClass;
        $resultado->texto = $texto;
        $resultado->campos = $campos;
        $resultado->valores = $valores;

        return $resultado;
    }

    private function consultar_comodato_main(Request $request) {
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
        return $resultado;
    }
    
    public function estoque(Request $request) {
        if ($this->consultar_estoque_main(
            $request->id_produto,
            $request->produto,
            $request->qtd,
            $request->preco,
            $request->es
        )->texto) return 401;
        
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
        return json_encode($this->consultar_estoque_main(
            explode(",", $request->produtos_id),
            explode(",", $request->produtos_descr),
            explode(",", $request->quantidades),
            explode(",", $request->precos),
            explode(",", $request->es)
        ));
    }

    public function preco(Requst $request) {
        return DB::table("maquinas_produtos")
                    ->where("id_maquina", $request->id_maquina)
                    ->where("id_produto", $request->id_produto)
                    ->value("preco");
    }

    public function consultar_comodato(Request $request) {
        return json_encode($this->consultar_comodato_main($request));
    }

    public function criar_comodato(Request $request) {
        if ($this->consultar_comodato_main($request)->texto) return 401;
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
}