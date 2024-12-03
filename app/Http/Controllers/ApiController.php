<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Valores;
use App\Models\Produtos;
use App\Models\Estoque;
use App\Models\Retiradas;
use App\Models\Atribuicoes;
use App\Models\Empresas;

class ApiController extends ControllerKX {
    //GETS
    private function retirarMain(Request $request) {
        $resultado = new \stdClass;
        $cont = 0;
        while (isset($request[$cont]["id_atribuicao"])) {
            $retirada = $request[$cont];
            $atribuicao = Atribuicoes::find($retirada["id_atribuicao"]);
            if ($atribuicao == null) {
                $resultado->code = 404;
                $resultado->msg = "Atribuição não encontrada";
                return json_encode($resultado);
            }
            $maquinas = DB::table("valores")
                            ->where("seq", $retirada["id_maquina"])
                            ->where("alias", "maquinas")
                            ->get();
            if (!sizeof($maquinas)) {
                $resultado->code = 404;
                $resultado->msg = "Máquina não encontrada";
                return json_encode($resultado);
            }
            $comodato = DB::table("comodatos")
                            ->select("id")
                            ->where("id_maquina", $maquinas[0]->id)
                            ->whereRaw("inicio <= CURDATE()")
                            ->whereRaw("fim >= CURDATE()")
                            ->get();
            if (!sizeof($comodato)) {
                $resultado->code = 404;
                $resultado->msg = "Máquina não comodatada para nenhuma empresa";
                return json_encode($resultado);
            }
            if (!isset($retirada["id_supervisor"]) && !$this->retirada_consultar($retirada["id_atribuicao"], $retirada["qtd"])) {
                $resultado->code = 401;
                $resultado->msg = "Essa quantidade de produtos não é permitida para essa pessoa";
                return json_encode($resultado);
            }
            if (floatval($retirada["qtd"]) > floatval(DB::table(DB::raw("(
                SELECT
                    CASE
                        WHEN (es = 'E') THEN qtd
                        ELSE qtd * -1
                    END AS qtd
                
                FROM estoque
    
                JOIN maquinas_produtos AS mp
                    ON mp.id = estoque.id_mp
    
                WHERE id_maquina = ".$maquinas[0]->id."
                  AND id_produto = ".$retirada["id_produto"]."
            ) AS tab"))->selectRaw("SUM(qtd) AS qtd")->value("qtd"))) {
                $resultado->code = 500;
                $resultado->msg = "Essa quantidade de produtos não está disponível em estoque";
                return json_encode($resultado);
            }
            $salvar = array(
                "id_pessoa" => $retirada["id_pessoa"],
                "id_produto" => $retirada["id_produto"],
                "id_atribuicao" => $retirada["id_atribuicao"],
                "id_comodato" => $comodato[0]->id,
                "qtd" => $retirada["qtd"],
                "data" => date("Y-m-d")
            );
            if (isset($retirada["id_supervisor"])) {
                $salvar += [
                    "id_supervisor" => $retirada["id_supervisor"],
                    "obs" => $retirada["obs"]
                ];
            }
            $this->retirada_salvar($salvar);
            $linha = new Estoque;
            $linha->es = "S";
            $linha->descr = "RETIRADA";
            $linha->qtd = $retirada["qtd"];
            $linha->id_mp = DB::table("maquinas_produtos")
                                ->where("id_produto", $retirada["id_produto"])
                                ->where("id_maquina", $maquinas[0]->id)
                                ->value("id");
            $linha->save();
            $this->log_inserir("C", "estoque", $linha->id, true);
            $cont++;
        }
        $resultado->code = 201;
        $resultado->msg = "Sucesso";
        return json_encode($resultado);
    }

    public function empresas() {
        return json_encode(
            DB::table("empresas")
                ->select(
                    "empresas.id",
                    DB::raw("
                        CONCAT(
                            empresas.nome_fantasia,
                            IFNULL(CONCAT(' - ', matriz.nome_fantasia), '')
                        ) AS descr, IFNULL(empresas.cod_externo, '') as cod_externo
                    "),
                )
                ->leftjoin("empresas AS matriz", "matriz.id", "empresas.id_matriz")
                ->where("empresas.lixeira", 0)
                ->where("matriz.lixeira", 0)
                ->get()
        );
    }

    public function maquinas(Request $request) {
        return DB::table(DB::raw("(
            SELECT
                id,
                descr
            FROM valores
            WHERE alias = 'maquinas'
                AND lixeira = 0
        ) AS tab"))->selectRaw("tab.*")->leftjoinSub(
            DB::table("comodatos")
                ->select("id_maquina")
                ->where(function($sql) use($request) {
                    if (isset($request->idEmp)) $sql->where("id_empresa", $request->idEmp);
                })
                ->whereRaw("CURDATE() >= inicio")
                ->whereRaw("CURDATE() < fim"),
        "aux", "aux.id_maquina", "tab.id")
        ->where(function($sql) use($request) {
            if (isset($request->idEmp)) $sql->whereNotNull("aux.id_maquina");
        })->get();
    }

    public function produtos_por_maquina(Request $request) {
        $consulta = DB::table("maquinas_produtos AS mp")
                        ->select(
                            "produtos.id",
                            "produtos.descr",
                            DB::raw("IFNULL(mp.preco, 0) AS preco"),
                            DB::raw("IFNULL(tab.saldo, 0) AS saldo"),
                            DB::raw("IFNULL(mp.minimo, 0) AS minimo"),
                            DB::raw("IFNULL(mp.maximo, 0) AS maximo")
                        )
                        ->leftjoinSub(DB::table(DB::raw("(
                            SELECT
                                CASE
                                    WHEN (es = 'E') THEN qtd
                                    ELSE qtd * -1
                                END AS qtd,
                                id_mp
                            
                            FROM estoque
                        ) AS estq"))->select(
                            DB::raw("IFNULL(SUM(qtd), 0) AS saldo"),
                            "id_mp"
                        )->groupBy("id_mp"), "tab", "tab.id_mp", "mp.id")
                        ->join("produtos", "produtos.id", "mp.id_produto")
                        ->where("mp.id_maquina", $request->idMaquina)
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
                        ->leftjoin("pessoas", "pessoas.id", "retiradas.id_pessoa")
                        ->leftjoin("empresas", "empresas.id", "pessoas.id_empresa")
                        ->leftjoin("comodatos", "comodatos.id", "retiradas.id_comodato")
                        ->whereRaw("data BETWEEN '".$request->dini."' AND '".$request->dfim."'")
                        ->where("empresas.id", "<>", 0)
                        ->where(function($sql) use($request) {
                            if ($request->idemp) $sql->whereRaw($request->idemp." IN (empresas.id, empresas.id_matriz)");
                            if ($request->idmaq) $sql->where("comodatos.id_maquina", $request->idmaq);
                            if (!$request->listagerados) $sql->where("retiradas.gerou_pedido", "N");
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
        $linha = Valores::firstOrNew(["id" => $request->id]);
        $linha->descr = mb_strtoupper($request->descr);
        $linha->alias = "categorias";
        if (!$request->id) {
            $linha->seq = intval(
                DB::table("valores")
                    ->selectRaw("IFNULL(MAX(seq), 0) AS ultimo")
                    ->where("alias", "categorias")
                    ->value("ultimo")
            ) + 1;
        }
        $linha->save();
        $modelo = $this->log_inserir($request->id ? "E" : "C", "valores", $linha->id, true);
        if (isset($request->usu)) $modelo->nome = $request->usu;
        $modelo->save();
        $resultado = new \stdClass;
        $resultado->id = $linha->id;
        $resultado->descr = $linha->descr;
        return json_encode($resultado);
    }

    public function produtos(Request $request) {
        $linha = Produtos::firstOrNew(["id" => $request->id]);
        $nome = "NULL";
        if (isset($request->usu)) $nome = $request->usu;
        $this->atribuicao_atualiza_ref($request->id, $linha->referencia, $request->refer, $nome, true);
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
        $linha->save();
        $letra_log = $request->id ? "E" : "C";
        if (intval($request->lixeira)) $letra_log = "D";
        $modelo = $this->log_inserir($letra_log, "produtos", $linha->id, true);
        if (isset($request->usu)) $modelo->nome = $request->usu;
        $modelo->save();
        $this->mov_estoque($linha->id, true);
        $consulta = DB::table("produtos")
                        ->select(
                            "id",
                            "descr",
                            "preco",
                            "validade",
                            DB::raw("IFNULL(ca, '') AS ca"),
                            DB::raw("IFNULL(foto, '') AS foto"),
                            "lixeira",
                            "referencia AS refer",
                            DB::raw("IFNULL(tamanho, '') AS tamanho"),
                            "id_categoria AS idCategoria",
                            "cod_externo AS codExterno",
                            DB::raw("'123' AS usu"),
                            "consumo",
                            "validade_ca"
                        )
                        ->where("id", $linha->id)
                        ->first();
        $consulta->preco = floatval($consulta->preco);
        $consulta->lixeira = intval($consulta->lixeira);
        return json_encode($consulta);
    }

    public function movimentar_estoque(Request $request) {
        for ($i = 0; $i < sizeof($request->idProduto); $i++) {
            $linha = new Estoque;
            $linha->es = $request->es[$i];
            $linha->descr = $request->descr[$i];
            $linha->qtd = $request->qtd[$i];
            $linha->id_mp = DB::table("maquinas_produtos")
                                ->where("id_produto", $request->idProduto[$i])
                                ->where("id_maquina", $request->idMaquina)
                                ->value("id");
            $linha->save();
            $modelo = $this->log_inserir("C", "estoque", $linha->id, true);
            if (isset($request->usu)) $modelo->nome = $request->usu;
            $modelo->save();
        }
        return 200;
    }

    public function gerenciar_estoque(Request $request) {
        $precoProd = floatval(DB::select("produtos")->where("id", $request->idProduto)->value("preco"));
        if (isset($request->preco)) {
            if (floatval($request->preco) > 0) $precoProd = floatval($request->preco);
        }
        $nome = "NULL";
        $where = "id_produto = ".$request->idProduto." AND id_maquina = ".$request->idMaquina;
        DB::statement("
            UPDATE maquinas_produtos SET
                minimo = ".$request->minimo.",
                maximo = ".$request->maximo.",
                preco = ".$precoProd."
            WHERE ".$where
        );
        if (isset($request->usu)) $nome = $request->usu;
        $this->log_inserir2("E", "maquinas_produtos", $where, $nome, true);
    }

    public function validarApp(Request $request) {
        return sizeof(
            DB::table("pessoas")
                ->where("cpf", $request->cpf)
                ->where("senha", $request->senha)
                ->where("lixeira", 0)
                ->get()
        ) ? 1 : 0;
    }

    public function verPessoa(Request $request) {
        return json_encode(
            DB::table("pessoas")
                ->where("cpf", $request->cpf)
                ->first()
        );
    }

    public function produtosPorPessoa(Request $request) {
        $consulta = DB::table("produtos")
                        ->select(
                            "produtos.id",
                            "produtos.referencia",
                            "produtos.descr AS nome",
                            "produtos.detalhes",
                            "produtos.cod_externo AS codbar",
                            DB::raw("IFNULL(produtos.tamanho, '') AS tamanho"),
                            DB::raw("IFNULL(produtos.foto, '') AS foto"),
                            "atribuicoes.id AS id_atribuicao",
                            "atribuicoes.obrigatorio",
                            DB::raw("(atribuicoes.qtd - IFNULL(ret.qtd, 0)) AS qtd"),
                            DB::raw("IFNULL(ret.ultima_retirada, '') AS ultima_retirada"),
                            DB::raw("DATE_FORMAT(IFNULL(ret.proxima_retirada, CURDATE()), '%d/%m/%Y') AS proxima_retirada")
                        )->join("atribuicoes", "atribuicoes.id", DB::raw("(
                            SELECT atribuicoes.id
                            
                            FROM atribuicoes

                            JOIN pessoas
                                ON (atribuicoes.pessoa_ou_setor_chave = 'P' AND atribuicoes.pessoa_ou_setor_valor = pessoas.id)
                                    OR (atribuicoes.pessoa_ou_setor_chave = 'S' AND atribuicoes.pessoa_ou_setor_valor = pessoas.id_setor)
                            
                            WHERE (
                                (produto_ou_referencia_chave = 'P' AND produto_ou_referencia_valor = produtos.cod_externo)
                            OR (produto_ou_referencia_chave = 'R' AND produto_ou_referencia_valor = produtos.referencia)
                            ) AND pessoas.cpf = '".$request->cpf."'
                              AND pessoas.lixeira = 0
                              AND atribuicoes.lixeira = 0

                            ORDER BY pessoa_ou_setor_chave

                            LIMIT 1
                        )"))->leftjoinSub(
                            DB::table("retiradas")
                                ->select(
                                    DB::raw("SUM(retiradas.qtd) AS qtd"),
                                    "id_atribuicao",
                                    DB::raw("DATE_FORMAT(MAX(retiradas.data), '%d/%m/%Y') AS ultima_retirada"),
                                    DB::raw("DATE_ADD(MAX(retiradas.data), INTERVAL atribuicoes.validade DAY) AS proxima_retirada")
                                )
                                ->join("atribuicoes", "atribuicoes.id", "retiradas.id_atribuicao")
                                ->whereRaw("DATE_ADD(retiradas.data, INTERVAL atribuicoes.validade DAY) >= CURDATE()")
                                ->groupby(
                                    "id_atribuicao",
                                    "atribuicoes.validade"
                                ),
                        "ret", "ret.id_atribuicao", "atribuicoes.id")
                        ->get();
        $resultado = array();
        foreach ($consulta as $linha) {
            if ($linha->foto) {
                $foto = explode("/", $linha->foto);
                $linha->foto = $foto[sizeof($foto) - 1];
            }
            array_push($resultado, $linha);
        }
        return json_encode(collect($resultado)->groupBy("referencia")->map(function($itens) use($request) {
            return [
                "id_pessoa" => DB::table("pessoas")->where("cpf", $request->cpf)->value("id"),
                "nome" => $itens[0]->nome,
                "foto" => $itens[0]->foto,
                "referencia" => $itens[0]->referencia,
                "qtd" => $itens[0]->qtd,
                "detalhes" => $itens[0]->detalhes,
                "ultima_retirada" => $itens[0]->ultima_retirada,
                "proxima_retirada" => $itens[0]->proxima_retirada,
                "obrigatorio" => $itens[0]->obrigatorio,
                "tamanhos" => $itens->map(function($tamanho) use($request) {
                    return [
                        "id" => $tamanho->id,
                        "id_pessoa" => DB::table("pessoas")->where("cpf", $request->cpf)->value("id"),
                        "id_atribuicao" => $tamanho->id_atribuicao,
                        "selecionado" => false,
                        "codbar" => $tamanho->codbar,
                        "numero" => $tamanho->tamanho ? $tamanho->tamanho : "UN"
                    ];
                })->values()->all()
            ];
        })->sortBy("nome")->values()->all());
    }

    public function retirar(Request $request) {
        return $this->retirarMain($request);
    }

    public function validarSpv(Request $request) {
        return $this->supervisor_consultar($request);
    }

    public function retirarComSupervisao(Request $request) {
        return $this->retirarMain($request);
    }

    public function marcarGerouPedido(Request $request) {
        foreach ($request->ids as $id) {
            $retirada = Retiradas::firstOrNew(["id" => $id]);
            $retirada->gerou_pedido = "S";
            $retirada->numero_ped = $request->numped;
            $retirada->save();
        }
        return "salvou";
    }

    public function associar_empresa(Request $request) {
        $empresa = Empresas::firstOrNew(["id" => $request->idemp]);
        $empresa->cod_externo = $request->cod_cli;
        $empresa->save();
        return $empresa->id;
    }

    public function pessoasComFoto() {
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
}