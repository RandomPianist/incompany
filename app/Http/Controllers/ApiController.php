<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Categorias;
use App\Models\Comodatos;
use App\Models\Maquinas;
use App\Models\Produtos;
use App\Models\Estoque;
use App\Models\Retiradas;
use App\Models\Atribuicoes;
use App\Models\ComodatosProdutos;
use App\Models\Empresas;
use App\Models\Pessoas;

class ApiController extends Controller {
    private function info_atb($id_pessoa, $obrigatorios, $grade) {
        $nucleo = " vatbold 
            JOIN (".$this->retorna_sql_atb_vigente(
                $this->retorna_atb_aux("P", $id_pessoa, false, $id_pessoa)
            ).") AS atb ON atb.id_atribuicao = vatbold.id

            JOIN produtos
                ON produtos.id = atb.id_produto

            ".$this->retorna_join_prev("atb.id_pessoa")."

            JOIN vprodutosgeral AS vprodutos
                ON vprodutos.id_pessoa = atb.id_pessoa AND vprodutos.id_produto = produtos.id

            JOIN pessoas
                ON pessoas.id = atb.id_pessoa

            LEFT JOIN mat_vretiradas
                ON mat_vretiradas.id_atribuicao = vatbold.id AND mat_vretiradas.id_pessoa = atb.id_pessoa

            LEFT JOIN mat_vultretirada
                ON mat_vultretirada.id_atribuicao = vatbold.id AND mat_vultretirada.id_pessoa = atb.id_pessoa
        ";
        $where = "atb.id_pessoa = ".$id_pessoa." AND vatbold.rascunho = 'S'";
        $where .= $obrigatorios ? "
            AND ((DATE_ADD(IFNULL(mat_vultretirada.data, DATE(pessoas.created_at)), INTERVAL vatbold.validade DAY) <= CURDATE()))
            AND ((vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))) > 0)
            AND vatbold.obrigatorio = 1
        " : " AND produtos.referencia ".($grade ? "IS NOT" : "IS")." NULL";
        return DB::select(DB::raw($obrigatorios ? "
            SELECT
                vatbold.pr_chave AS produto_ou_referencia_chave,
                CASE
                    WHEN (vatbold.pr_chave = 'R') THEN produtos.referencia
                    ELSE produtos.id
                END AS chave,
                vatbold.pr_valor AS nome

            FROM ".$nucleo."

            WHERE ".$where."

            GROUP BY
                vatbold.pr_chave,
                CASE
                    WHEN (vatbold.pr_chave = 'R') THEN produtos.referencia
                    ELSE produtos.id
                END,
                vatbold.pr_valor
        " : "
            SELECT
                vatbold.id AS id_atribuicao,
                vatbold.obrigatorio,
                produtos.id,
                produtos.referencia,
                produtos.descr AS nome,
                produtos.detalhes,
                produtos.cod_externo AS codbar,
                produtos.tamanho,
                produtos.foto,
                ".$this->retorna_case_qtd()." AS qtd,
                IFNULL(DATE_FORMAT(mat_vultretirada.data, '%d/%m/%Y'), '') AS ultima_retirada,
                DATE_FORMAT(
                    (CASE
                        WHEN ((DATE_ADD(IFNULL(mat_vultretirada.data, DATE(pessoas.created_at)), INTERVAL vatbold.validade DAY) <= CURDATE())) THEN CURDATE()
                        ELSE (DATE_ADD(IFNULL(mat_vultretirada.data, DATE(pessoas.created_at)), INTERVAL vatbold.validade DAY))
                    END),
                    '%d/%m/%Y'
                ) AS proxima_retirada,
                pr.seq 
                
            FROM ".$nucleo."

            JOIN pre_retiradas AS pr
                ON pr.id_pessoa = atb.id_pessoa AND pr.id_produto = produtos.id

            WHERE ".$where."

            GROUP BY
                produtos.id,
                produtos.referencia,
                produtos.descr,
                produtos.detalhes,
                produtos.cod_externo,
                produtos.tamanho,
                produtos.foto,
                prev.qtd,
                vatbold.id,
                vatbold.qtd,
                vatbold.validade,
                vatbold.obrigatorio,
                vprodutos.qtd,
                vprodutos.travar_estq,
                mat_vultretirada.data,
                mat_vretiradas.valor,
                pr.seq,
                pessoas.created_at
        "));
    }

    private function produtos_por_pessoa_main($id_pessoa, $grade) {
        $consulta = $this->info_atb($id_pessoa, false, $grade);

        $resultado = array();
        foreach ($consulta as $linha) {
            if ($linha->foto) {
                $foto = explode("/", $linha->foto);
                $linha->foto = $foto[sizeof($foto) - 1];
            }
            array_push($resultado, $linha);
        }

        return collect($resultado)->groupBy($grade ? "referencia" : "id")->map(function($itens) use($id_pessoa) {
            return [
                "id_pessoa" => $id_pessoa,
                "nome" => $itens[0]->nome,
                "foto" => $itens[0]->foto,
                "referencia" => $itens[0]->referencia,
                "qtd" => $itens[0]->qtd,
                "detalhes" => $itens[0]->detalhes,
                "ultima_retirada" => $itens[0]->ultima_retirada,
                "proxima_retirada" => $itens[0]->proxima_retirada,
                "obrigatorio" => $itens[0]->obrigatorio,
                "tamanhos" => $itens->groupBy("id")->map(function($tamanho) use($id_pessoa) {
                    return [
                        "id" => $tamanho[0]->id,
                        "id_pessoa" => $id_pessoa,
                        "id_atribuicao" => $tamanho[0]->id_atribuicao,
                        "selecionado" => false,
                        "codbar" => $tamanho[0]->codbar,
                        "numero" => $tamanho[0]->tamanho ? $tamanho[0]->tamanho : "UN"
                    ];
                })->values()->all()
            ];
        })->values()->all();
    }

    //GETS
    public function empresas() {
        return json_encode(
            DB::table("empresas")
                ->select(
                    "empresas.id",
                    DB::raw("IFNULL(empresas.cod_externo, '') as cod_externo"),
                    DB::raw("
                        CONCAT(
                            empresas.nome_fantasia,
                            IFNULL(CONCAT(' - ', matriz.nome_fantasia), '')
                        ) AS descr
                    ")
                )
                ->leftjoin("empresas AS matriz", "matriz.id", "empresas.id_matriz")
                ->where(function($sql) {
                    $sql->where(function($q) {
                        $q->where("matriz.lixeira", 0);
                    })->orWhere(function($q) {
                        $q->whereNull("matriz.id");
                    });
                })
                ->where("empresas.lixeira", 0)
                ->get()
        );
    }

    public function maquinas(Request $request) {
        return DB::table("maquinas")
                    ->selectRaw("tab.*")
                    ->leftjoinSub(
                        DB::table("comodatos")
                            ->select("id_maquina")
                            ->where(function($sql) use($request) {
                                if (isset($request->idEmp)) $sql->where("id_empresa", $request->idEmp);
                            })
                            ->whereRaw("CURDATE() >= inicio")
                            ->whereRaw("CURDATE() < fim"),
                        "aux",
                        "aux.id_maquina",
                        "tab.id"
                    )
                    ->where(function($sql) use($request) {
                        if (isset($request->idEmp)) $sql->whereNotNull("aux.id_maquina");
                    })
                    ->where("maquinas.lixeira", 0)
                    ->get();
    }

    public function produtos_por_maquina(Request $request) {
        $consulta = DB::table("comodatos_produtos AS cp")
                        ->select(
                            "produtos.id",
                            "produtos.descr",
                            DB::raw("IFNULL(cp.preco, 0) AS preco"),
                            DB::raw("IFNULL(vestoque.qtd, 0) AS saldo"),
                            DB::raw("IFNULL(cp.minimo, 0) AS minimo"),
                            DB::raw("IFNULL(cp.maximo, 0) AS maximo")
                        )
                        ->leftjoin("vestoque", "vestoque.id_cp", "cp.id")
                        ->join("produtos", "produtos.id", "cp.id_produto")
                        ->where("cp.id_comodato", $this->obter_comodato($request->idMaquina)) // App\Http\Controllers\Controller.php
                        ->where("produtos.lixeira", 0)
                        ->get();
        foreach ($consulta as $linha) {
            $linha->preco = floatval($linha->preco);
            $linha->saldo = floatval($linha->saldo);
            $linha->minimo = floatval($linha->minimo);
            $linha->maximo = floatval($linha->maximo);
        }
        return json_encode($consulta);
    }

    public function retiradas_por_periodo(Request $request) {
        $retorno = DB::table("retiradas")
                        ->select(
                            "empresas.id AS empid",
                            "empresas.nome_fantasia",
                            "produtos.cod_externo",
                            DB::raw("IFNULL(empresas.cod_externo, '') AS cft_externo"),
                            DB::raw("SUM(retiradas.qtd) AS qtd"),
                            DB::raw("GROUP_CONCAT(retiradas.id) AS ids")
                        )
                        ->leftjoin("produtos", "produtos.id", "retiradas.id_produto")
                        ->leftjoin("pessoas", function($join) {
                            $join->on("pessoas.id", "retiradas.id_pessoa")
                                ->on("pessoas.id_empresa", "retiradas.id_empresa");
                        })
                        ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                        ->leftjoin("comodatos", "comodatos.id", "retiradas.id_comodato")
                        ->whereRaw("data BETWEEN '".$request->dini."' AND '".$request->dfim."'")
                        ->where("empresas.id", "<>", 0)
                        ->where(function($sql) use($request) {
                            if ($request->idemp) $sql->whereRaw($request->idemp." IN (empresas.id, empresas.id_matriz)");
                            if ($request->idmaq) $sql->where("comodatos.id_maquina", $request->idmaq);
                        })
                        ->groupBy(
                            "empresas.id",
                            "empresas.nome_fantasia",
                            "produtos.cod_externo"
                        )
                        ->get();
        foreach ($retorno as $item) $item->qtd = floatval($item->qtd);
        return json_encode($retorno);
    }

    //POSTS
    public function categorias(Request $request) {
        $linha = Categorias::firstOrNew(["id" => $request->id]);
        $linha->descr = mb_strtoupper($request->descr);
        $linha->save();
        $nome = "";
        if (isset($request->usu)) $nome = $request->usu;
        $this->log_inserir($request->id ? "E" : "C", "categorias", $linha->id, "ERP", $nome); // App\Http\Controllers\Controller.php
        $resultado = new \stdClass;
        $resultado->id = $linha->id;
        $resultado->descr = $linha->descr;
        return json_encode($resultado);
    }

    public function produtos(Request $request) {
        $linha = Produtos::firstOrNew(["id" => $request->id]);
        $nome = "";
        if (isset($request->usu)) $nome = $request->usu;
        $this->atribuicao_atualiza_ref($request->id, $linha->referencia, $request->refer, $nome, true); // App\Http\Controllers\Controller.php
        $linha->descr = mb_strtoupper($request->descr);
        $linha->preco = $request->preco;
        $linha->validade = $request->validade;
        $linha->validade_ca = $request->validade_ca;
        $linha->ca = $request->ca;
        $linha->cod_externo = $request->codExterno;
        $linha->id_categoria = $request->idCategoria;
        $linha->foto = $request->foto;
        $linha->lixeira = $request->lixeira;
        if (isset($request->refer)) $linha->referencia = $request->refer;
        if (isset($request->tamanho)) $linha->tamanho = $request->tamanho;
        if (isset($request->consumo)) $linha->consumo = $request->consumo;
        if (isset($request->cod_fab)) $linha->cod_fab = $request->cod_fab;
        $linha->save();
        $letra_log = $request->id ? "E" : "C";
        if (intval($request->lixeira)) $letra_log = "D";
        $nome = "";
        if (isset($request->usu)) $nome = $request->usu;
        $this->log_inserir($letra_log, "produtos", $linha->id, "ERP", $nome); // App\Http\Controllers\Controller.php
        $consulta = DB::table("produtos")
                        ->select(
                            "id",
                            "descr",
                            "preco",
                            DB::raw("IFNULL(validade, 0) AS validade"),
                            DB::raw("IFNULL(ca, '') AS ca"),
                            DB::raw("IFNULL(foto, '') AS foto"),
                            "lixeira",
                            DB::raw("IFNULL(referencia, '') AS refer"),
                            DB::raw("IFNULL(tamanho, '') AS tamanho"),
                            "id_categoria AS idCategoria",
                            "cod_externo AS codExterno",
                            DB::raw("'123' AS usu"),
                            "consumo",
                            DB::raw("IFNULL (validade_ca, '') AS validade_ca"),
                            DB::raw("IFNULL (cod_fab, '') AS cod_fab")
                        )
                        ->where("id", $linha->id)
                        ->first();
        $consulta->preco = floatval($consulta->preco);
        $consulta->lixeira = intval($consulta->lixeira);
        return json_encode($consulta);
    }

    public function movimentar_estoque(Request $request) {
        $comodato = $this->obter_comodato($request->id_maquina); // App\Http\Controllers\Controller.php
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            for ($i = 0; $i < sizeof($request->idProduto); $i++) {
                $saldo_ant = $this->retorna_saldo_cp($comodato->id, $request->idProduto[$i]); // App\Http\Controllers\Controller.php
                $cp = ComodatosProdutos::find($comodato->cp($request->idProduto[$i])->value("id"));
                $linha = new Estoque;
                $linha->data = date("Y-m-d");
                $linha->hms = date("H:i:s");
                $linha->es = $request->es[$i];
                $linha->descr = $request->descr[$i];
                $linha->qtd = $request->qtd[$i];
                $linha->id_cp = $cp->id;
                $linha->preco = $cp->preco;
                $linha->save();
                $nome = "";
                if (isset($request->usu)) $nome = $request->usu;
                $this->log_inserir("C", "estoque", $linha->id, "ERP", $nome); // App\Http\Controllers\Controller.php
            }
            $connection->commit();
            return 200;
        } catch (\Exception $e) {    
            $connection->rollBack();
            return 500;
        }
    }

    public function gerenciar_estoque(Request $request) {
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            $cp = ComodatosProdutos::find($this->obter_comodato($request->idMaquina)->cp($request->idProduto)->value("id")); // App\Http\Controllers\Controller.php
            $precoProd = floatval($cp->preco);
            if (isset($request->preco)) {
                if (floatval($request->preco) > 0) $precoProd = floatval($request->preco);
            }
            $cp->minimo = $request->minimo;
            $cp->maximo = $request->maximo;
            $cp->preco = $request->preco;
            $cp->save();
            $nome = "";
            if (isset($request->usu)) $nome = $request->usu;
            $this->log_inserir("E", "comodatos_produtos", $cp->id, "ERP", $nome); // App\Http\Controllers\Controller.php
        } catch (\Exception $e) {    
            $connection->rollBack();
            throw $e;
        }
    }

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
        return json_encode(collect(
            array_merge(
                $this->produtos_por_pessoa_main($id_pessoa, true),
                $this->produtos_por_pessoa_main($id_pessoa, false)
            )
        )->sortBy([
            ["obrigatorio", "desc"],
            ["nome", "asc"]
        ])->values()->all());
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

        $consulta = $this->info_atb($request[0]["id_pessoa"], true, false);
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
                
                $this->retirada_salvar($salvar); // App\Http\Controllers\Controller.php
                
                $linha = new Estoque;
                $linha->es = "S";
                $linha->descr = "RETIRADA";
                $linha->qtd = $retirada["qtd"];
                $linha->data = date("Y-m-d");
                $linha->hms = date("H:i:s");
                $linha->id_cp = $comodato->cp($retirada["id_produto"])->value("id");
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

    public function marcar_gerou_pedido(Request $request) {
        foreach ($request->ids as $id) {
            $retirada = Retiradas::find($id);
            $retirada->numero_ped = $request->numped;
            $retirada->save();
            $nome = "";
            if (isset($request->usu)) $nome = $request->usu;
            $this->log_inserir("E", "retiradas", $id, "ERP", $nome); // App\Http\Controllers\Controller.php
        }
        return "salvou";
    }

    public function associar_empresa(Request $request) {
        $empresa = Empresas::find($request->idemp);
        $empresa->cod_externo = $request->cod_cli;
        $empresa->save();
        $nome = "";
        if (isset($request->usu)) $nome = $request->usu;
        $this->log_inserir("E", "empresas", $empresa->id, "APP", $nome); // App\Http\Controllers\Controller.php
        return $empresa->id;
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
}