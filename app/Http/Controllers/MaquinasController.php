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
            $linha->es = $request->es[$i];
            $linha->descr = $request->obs[$i];
            $linha->qtd = $request->qtd[$i];
            $linha->id_mp = DB::table("maquinas_produtos")
                                ->where("id_produto", $request->id_produto[$i])
                                ->where("id_maquina", $request->id_maquina)
                                ->value("id");
            $linha->save();
            $this->log_inserir("C", "estoque", $linha->id);
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
                $consulta = DB::table(DB::raw("(
                    SELECT
                        CASE
                            WHEN (es = 'E') THEN qtd
                            ELSE qtd * -1
                        END AS qtd,
                        id_mp

                    FROM estoque
                ) AS estq"))->selectRaw("IFNULL(SUM(qtd), 0) AS saldo")
                    ->join("maquinas_produtos AS mp", "mp.id", "estq.id_mp")
                    ->where("mp.id_maquina", $request->id_maquina)
                    ->where("mp.id_produto", $produtos_id[$i])
                    ->get();
                $erro = !sizeof($consulta);
                if (!$erro) {
                    $valor = floatval($quantidades[$i]);
                    if ($es[$i] == "S") $valor *= -1;
                    $erro = (floatval($consulta[0]->saldo) + $valor) < 0;
                }
                if ($erro) {
                    array_push($campos, "qtd-".($i + 1));
                    array_push($valores, floatval($consulta[0]->saldo) * 1);
                    $linha2 = !$texto ? "Os campos foram corrigidos" : "O campo foi corrigido";
                    $linha2 .= " para zerar o estoque.<br>Por favor, verifique e tente novamente.";
                    $texto = "Essa movimentação de estoque provocaria estoque negativo.<br>".$linha2;
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
        $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
        $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
        
        $linha = new Comodatos;
        $linha->id_maquina = $request->id_maquina;
        $linha->id_empresa = $request->id_empresa;
        $linha->inicio = $inicio;
        $linha->fim = $fim;
        $linha->fim_orig = $fim;
        $linha->save();
        $this->log_inserir("C", "comodatos", $linha->id);
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