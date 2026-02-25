<?php

namespace App\Http\Controllers;

use DB;
use App\Models\Comodatos;
use App\Models\Estoque;
use App\Models\Atribuicoes;
use App\Models\Pessoas;
use Illuminate\Http\Request;

class ApiController extends Controller {
    public function validar_app(Request $request) {
        return Pessoas::where("cpf", $request->cpf)
                    ->where("senha", $request->senha)
                    ->where("lixeira", 0)
                    ->exists()
        ? 1 : 0;
    }

    public function ver_pessoa(Request $request) {
        return json_encode(Pessoas::where("cpf", $request->cpf)->first());
    }

    public function produtos_por_pessoa(Request $request) {
        $id_pessoa = Pessoas::where("cpf", $request->cpf)->value("id");
        return $this->produtos_por_pessoa_main($id_pessoa, [
            ["obrigatorio", "desc"],
            ["nome", "asc"]
        ]); // App\Http\Controllers\Controller.php
    }

    public function retirar(Request $request) {
        $resultado = new \stdClass;
        $resultado->msg = "";
        $cont = 0;
        $comodato = null;
        $produtos_ids = array();
        $produtos_refer = array();

        if (!DB::table("mat_vultretirada")->exists()) {
            $resultado->code = 500;
            $resultado->msg = "O sistema está em automanutenção. Tente novamente em alguns minutos.";
        }

        while (isset($request[$cont]["id_atribuicao"]) && !$resultado->msg) {
            $retirada = $request[$cont];
            $atribuicao = Atribuicoes::find($retirada["id_atribuicao"]);
            $maquinas = array();
            $cns_comodato = array();

            if (!$resultado->msg && $atribuicao === null) {
                $resultado->code = 404;
                $resultado->msg = "Atribuição não encontrada";
            }

            if (!$resultado->msg) {
                $maquinas = DB::table("maquinas")
                                ->whereRaw("(
                                    CASE
                                        WHEN id_ant IS NOT NULL THEN id_ant
                                        ELSE id
                                    END
                                ) = ".$retirada["id_maquina"])
                                ->get();
                if (!sizeof($maquinas)) {
                    $resultado->code = 404;
                    $resultado->msg = "Máquina não encontrada";
                }
            }

            if (!$resultado->msg) {
                $cns_comodato = DB::table("comodatos")
                                    ->select("id")
                                    ->where("id_maquina", $maquinas[0]->id)
                                    ->whereRaw("inicio <= CURDATE()")
                                    ->whereRaw("fim >= CURDATE()")
                                    ->get();
                if (!sizeof($cns_comodato)) {
                    $resultado->code = 404;
                    $resultado->msg = "Máquina não comodatada para nenhuma empresa";
                }
            }

            if (!intval(Pessoas::find($retirada["id_pessoa"])->id_empresa)) {
                $resultado->code = 401;
                $resultado->msg = "Pessoa sem empresa";
            }
            
            if (!$resultado->msg) {
                $comodato = Comodatos::find($cns_comodato[0]->id);
                if (
                    intval($comodato->travar_ret) &&
                    !isset($retirada["id_supervisor"]) &&
                    !$this->retirada_consultar($retirada["id_atribuicao"], $retirada["qtd"], $retirada["id_pessoa"]) // App\Http\Controllers\Controller.php
                ) {
                    $resultado->code = 401;
                    $resultado->msg = "Essa quantidade de produtos não é permitida para essa pessoa";
                }

                if (!$resultado->msg) {
                    if (
                        intval($comodato->travar_estq) &&
                        floatval($retirada["qtd"]) > $this->retorna_saldo_cp($comodato->id, $retirada["id_produto"]) // App\Http\Controllers\Controller.php
                    ) {
                        $resultado->code = 500;
                        $resultado->msg = "Essa quantidade de produtos não está disponível em estoque";
                    }
                }
            }

            if (!$resultado->msg) {
                array_push($produtos_ids, intval($retirada["id_produto"]));
                array_push($produtos_refer, strval(
                    DB::table("produtos")
                        ->selectRaw("IFNULL(referencia, '') AS referencia")
                        ->where("id", $retirada["id_produto"])
                        ->value("referencia")
                ));
            }
            $cont++;
        }

        if ($resultado->msg) return json_encode($resultado);

        $consulta = $this->info_atb($request[0]["id_pessoa"], true, false); // App\Http\Controllers\Controller.php
        foreach ($consulta as $linha) {
            if (!$resultado->msg && (
                ($linha->produto_ou_referencia_chave == "R" && !in_array($linha->chave_produto, $produtos_refer)) ||
                ($linha->produto_ou_referencia_chave == "P" && !in_array(intval($linha->chave_produto), $produtos_ids))
            )) {
                $msg = "Há um produto obrigatório que não foi retirado: ";
                if ($linha->produto_ou_referencia_chave == "R") $msg .= "referência ";
                $msg .= $linha->nome;
                $resultado->code = 400;
                $resultado->msg = $msg; 
            }
        }

        if ($resultado->msg) return json_encode($resultado);

        $cont = 0;
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            while (isset($request[$cont]["id_atribuicao"])) {
                $retirada = $request[$cont];
                $salvar = array(
                    "id_pessoa" => $retirada["id_pessoa"],
                    "id_produto" => $retirada["id_produto"],
                    "id_atribuicao" => $retirada["id_atribuicao"],
                    "id_comodato" => $comodato->id,
                    "qtd" => $retirada["qtd"],
                    "data" => date("Y-m-d"),
                    "hora" => date("H:i:s")
                );
                if (isset($retirada["id_supervisor"])) {
                    $salvar += [
                        "id_supervisor" => $retirada["id_supervisor"],
                        "obs" => $retirada["obs"]
                    ];
                }    
                if (isset($retirada["biometria"])) $salvar += ["biometria" => $retirada["biometria"]];
                
                $m_retirada = $this->retirada_salvar($salvar); // App\Http\Controllers\Controller.php
                
                $linha = new Estoque;
                $linha->es = "S";
                $linha->descr = "RETIRADA";
                $linha->qtd = $retirada["qtd"];
                $linha->data = date("Y-m-d");
                $linha->hms = date("H:i:s");
                $linha->id_cp = $comodato->cp($retirada["id_produto"])->value("id");
                $linha->id_retirada = $m_retirada->id;
                $linha->save();
                $reg_log = $this->log_inserir("C", "estoque", $linha->id, "APP"); // App\Http\Controllers\Controller.php
                $reg_log->id_pessoa = $retirada["id_pessoa"];
                $reg_log->nome = Pessoas::find($retirada["id_pessoa"])->nome;
                $reg_log->save();
        
                $cont++;
            }
            $this->atualizar_mat_vretiradas_vultretirada("P", $request[0]["id_pessoa"], "R", false); // App\Http\Controllers\Controller.php
            $this->atualizar_mat_vretiradas_vultretirada("P", $request[0]["id_pessoa"], "U", false); // App\Http\Controllers\Controller.php
            $resultado->code = 201;
            $resultado->msg = "Sucesso";
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $resultado->code = 500;
            $resultado->msg = $e->getMessage();
        }
        return json_encode($resultado);
    }

    public function validar_spv(Request $request) {
        return $this->supervisor_consultar($request); // App\Http\Controllers\Controller.php
    }

    public function pessoas_com_foto() {
        return json_encode(
            DB::table("pessoas")
                ->select(
                    "id",
                    "nome",
                    "foto64",
                    "cpf"
                )
                ->whereNotNull("foto64")
                ->get()
        );
    }

    public function biometria(Request $request) {
        $pessoa = Pessoas::find($request->id);
        if ($pessoa == null) return [];
        $pessoa->biometria = $request->biometria;
        $pessoa->save();
        $this->log_inserir("E", "pessoas", $pessoa->id, "APP"); // App\Http\Controllers\Controller.php
        return 200;
    }

    public function validar_biometria(Request $request) {
        $pessoa = Pessoas::where("biometria", $request->biometria)->value("id");
        if ($pessoa == null) return 0;
        return $pessoa;
    }

    public function teste() {
        return '{"status":"ok"}';
    }
}