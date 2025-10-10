<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Comodatos;
use App\Models\ComodatosProdutos;
use App\Models\Estoque;
use App\Models\Produtos;
use App\Models\Maquinas;
use App\Models\Permissoes;

class MaquinasController extends Controller {
    private function busca($where, $id_maquina) {
        return !$id_maquina ? 
            DB::table("maquinas")
                ->select(
                    "maquinas.id",
                    "maquinas.descr",
                    DB::raw("
                        CASE
                            WHEN aux1.id_maquina IS NOT NULL THEN 'S'
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
                    "),
                    DB::raw("
                        CASE
                            WHEN aux2.cod_externo IS NOT NULL THEN 'S'
                            ELSE 'N'
                        END AS tem_cod
                    "),
                    DB::raw("
                        CASE
                            WHEN aux4.id_comodato IS NOT NULL THEN 'S'
                            ELSE 'N'
                        END AS tem_cp
                    ")
                )
                ->leftjoinSub(
                    DB::table("comodatos")
                        ->select(
                            "id",
                            "id_maquina",
                            "id_empresa",
                            DB::raw("DATE_FORMAT(fim, '%d/%m/%Y') AS fim_formatado")
                        )
                        ->whereRaw("CURDATE() >= inicio")
                        ->whereRaw("CURDATE() < fim"),
                "aux1", "aux1.id_maquina", "maquinas.id")
                ->leftjoinSub(
                    DB::table("empresas")
                        ->select(
                            "id",
                            "id_matriz",
                            "nome_fantasia",
                            "cod_externo"
                        )
                        ->where("lixeira", 0),
                "aux2", "aux2.id", "aux1.id_empresa")
                ->leftjoinSub(
                    DB::table("comodatos_produtos AS cp")
                        ->selectRaw("DISTINCTROW id_comodato")
                        ->join("estoque", "estoque.id_cp", "cp.id"),
                "aux3", "aux3.id_comodato", "aux1.id")
                ->leftjoinSub(
                    DB::table("comodatos_produtos AS cp")
                        ->selectRaw("DISTINCTROW id_comodato"),
                "aux4", "aux4.id_comodato", "aux1.id")
                ->where(function($sql) {
                    $id_emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
                    if ($id_emp) $sql->whereRaw($id_emp." IN (aux2.id, aux2.id_matriz)");
                })
                ->whereRaw($where)
                ->where("maquinas.lixeira", 0)
                ->get()
        :
            DB::table("comodatos_produtos AS cp")
                ->select(
                    "cp.id_produto",
                    "vprodaux.descr AS produto",
                    "cp.lixeira",
                    "cp.preco",
                    DB::raw("IFNULL(cp.minimo, 0) AS minimo"),
                    DB::raw("IFNULL(cp.maximo, 0) AS maximo")
                )
                ->join("vprodaux", "vprodaux.id", "cp.id_produto")
                ->leftjoin("categorias", "categorias.id", "vprodaux.id_categoria")
                ->where("cp.id_comodato", $this->obter_comodato($id_maquina)->id) // App\Http\Controllers\Controller.php
                ->whereRaw($where)
                ->where("vprodaux.lixeira", 0)
                ->orderby("cp.lixeira")
                ->take(20)
                ->get();
    }

    private function chamar_busca_main($tabela, $coluna, $filtro, $id_maquina) {
        $busca = $this->busca($tabela.".".$coluna." LIKE '".$filtro."%'", $id_maquina);
        if (sizeof($busca) < 3) $busca = $this->busca($tabela.".".$coluna." LIKE '%".$filtro."%'", $id_maquina);
        if (sizeof($busca) < 3) $busca = $this->busca("(".$tabela.".".$coluna." LIKE '%".implode("%' AND ".$tabela.".".$coluna." LIKE '%", explode(" ", str_replace("  ", " ", $filtro)))."%')", $id_maquina);
        return $busca;
    }

    private function chamar_busca(Request $request) {
        $filtro = trim($request->filtro);
        $id_maquina = isset($request->id_maquina) ? $request->id_maquina : 0;
        $tabela = isset($request->id_maquina) ? "vprodaux" : "maquinas";
        if ($filtro) return $this->chamar_busca_main($tabela, "descr", $filtro, $id_maquina);
        if (!$id_maquina) return $this->busca("1", 0);
        $filtro = trim($request->filtro_ref);
        if ($filtro) return $this->chamar_busca_main($tabela, "referencia", $filtro, $id_maquina);
        $filtro = trim($request->filtro_cat);
        if ($filtro) return $this->chamar_busca_main("categorias", "descr", $filtro, $id_maquina);
        return $this->busca("1", $id_maquina);
    }

    private function aviso_main($id) {
        $aviso = DB::table("maquinas")
                    ->selectRaw("
                        CASE
                            WHEN (tab_comodatos.id_maquina IS NOT NULL) THEN CONCAT('está comodatada para ', tab_comodatos.empresa, ' até ', tab_comodatos.fim)
                            WHEN (tab_estoque.saldo <> 0) THEN 'possui saldo diferente de zero'
                            ELSE ''
                        END AS aviso
                    ")
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
                        "tab_comodatos",
                        "tab_comodatos.id_maquina",
                        "maquinas.id"
                    )
                    ->leftjoinSub(
                        DB::table("vestoque")
                            ->select(
                                DB::raw("IFNULL(SUM(qtd), 0) AS saldo"),
                                "cp.id_comodato"
                            )
                            ->join("comodatos_produtos AS cp", "cp.id", "estq.id_cp")
                            ->groupby("id_comodato"),
                        "tab_estoque",
                        "tab_estoque.id_comodato",
                        "maquinas.id"
                    )
                    ->where("maquinas.id", $id)
                    ->value("aviso");
        $vinculo = $aviso != "";
        $resultado = new \stdClass;
        $nome = "<b>".Maquinas::find($id)->descr."</b>";
        $resultado->permitir = !$vinculo;
        $resultado->aviso = $vinculo ? "Não é possível excluir ".$nome." porque essa máquina ".$aviso : "Tem certeza que deseja excluir ".$nome."?";
        return $resultado;
    }

    private function info_cp($id_comodato, $id_produto, $coluna) {
        return floatval(
            DB::table("comodatos_produtos")
                ->selectRaw("IFNULL(".$coluna.", 0) AS ".$coluna)
                ->where("id_comodato", $id_comodato)
                ->where("id_produto", $id_produto)
                ->value($coluna)
        );
    }

    private function consultar_estoque_main($id_maquina, $produtos_id, $produtos_descr, $quantidades, $precos, $es) {
        $texto = "";
        $campos = array();
        $valores = array();

        $comodato = $this->obter_comodato($id_maquina); // App\Http\Controllers\Controller.php

        for ($i = 0; $i < sizeof($produtos_id); $i++) {
            if (!DB::table("vprodaux")
                    ->where("id", $produtos_id[$i])
                    ->where("descr", $produtos_descr[$i])
                    ->where("lixeira", 0)
                    ->exists()
            ) {
                array_push($campos, "produto-".($i + 1));
                array_push($valores, $produtos_descr[$i]);
                $texto = !$texto ? "Produtos não encontrados" : "Produto não encontrado";
            }
        }

        if (!$texto) {
            for ($i = 0; $i < sizeof($produtos_id); $i++) {
                $saldo = $this->retorna_saldo_cp($comodato->id, $produtos_id[$i]); // App\Http\Controllers\Controller.php
                switch($es[$i]) {
                    case "E":
                        $maximo = $this->info_cp($comodato->id, $produtos_id[$i], "maximo");
                        if ($maximo && (($saldo + floatval($quantidades[$i])) > $maximo)) {
                            array_push($campos, "es-".($i + 1));
                            array_push($valores, "A");
                            array_push($campos, "qtd-".($i + 1));
                            array_push($valores, $maximo);
                            $linha2 = $texto ? "Os campos foram corrigidos" : "O campo foi corrigido";
                            $linha2 .= " para o estoque máximo.<br>Por favor, verifique e tente novamente.";
                            $texto = "Essa movimentação de estoque provocaria estoque acima do máximo.<br>".$linha2;
                        }
                        break;
                    case "S":
                        $minimo = $this->info_cp($comodato->id, $produtos_id[$i], "minimo");
                        if (($saldo - floatval($quantidades[$i])) < $minimo) {
                            array_push($campos, "es-".($i + 1));
                            array_push($valores, "A");
                            array_push($campos, "qtd-".($i + 1));
                            array_push($valores, $minimo);
                            $linha2 = $texto ? "Os campos foram corrigidos" : "O campo foi corrigido";
                            $linha2 .= " para ".($minimo ? "o estoque mínimo" : "zerar o estoque").".<br>Por favor, verifique e tente novamente.";
                            $texto = "Essa movimentação de estoque provocaria estoque ".($minimo ? "abaixo do mínimo" : "negativo").".<br>".$linha2;
                        }
                        break;
                }
            }
        }

        if (!$texto) {
            for ($i = 0; $i < sizeof($produtos_id); $i++) {
                if (!ceil($precos[$i])) {
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
        if ($this->empresa_consultar($request)) $resultado->texto = "Empresa não encontrada"; // App\Http\Controllers\Controller.php
        if (!$resultado->texto) {
            $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
            $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
            $consulta = DB::table("comodatos")
                            ->select(
                                DB::raw("
                                    CONCAT(
                                        maquinas.descr, ' ',
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
                            )
                            ->join("maquinas", "maquinas.id", "comodatos.id_maquina")
                            ->whereRaw("(('".$inicio."' >= comodatos.inicio AND '".$inicio."' < comodatos.fim) OR ('".$fim."' >= comodatos.inicio AND '".$fim."' < comodatos.fim))")
                            ->where("comodatos.inicio", "<>", "comodatos.fim")
                            ->where("id_maquina", $request->id_maquina)
                            ->get();
            if (sizeof($consulta)) $resultado = $consulta[0];
        }
        return $resultado;
    }

    public function ver() {
        $comodato = false;
        $busca = $this->busca("1", 0);
        foreach($busca as $linha) {
            if ($linha->comodato != "---") $comodato = true;
        }
        return view("maquinas", compact("comodato"));
    }

    public function listar(Request $request) {
        $id_maquina = isset($request->id_maquina) ? $request->id_maquina : 0;
        $busca = $this->chamar_busca($request);
        if (!$id_maquina) return json_encode($busca);
        $resultado = new \stdClass;
        $resultado->lista = $busca;
        $resultado->total = DB::table("comodatos_produtos")
                                ->selectRaw("COUNT(id) AS total")
                                ->where("id_comodato", $this->obter_comodato($id_maquina)->id)
                                ->value("total");
        return json_encode($resultado);
    }

    public function consultar(Request $request) {
        return (!intval($request->id) && Maquinas::where("descr", $request->descr)->exists()) ? "1" : "0";
    }

    public function mostrar($id) {
        return json_encode(Maquinas::find($id));
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function salvar(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php
        if (!trim($request->descr)) return 400;
        if (intval($this->consultar($request))) return 401;
        $linha = Maquinas::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            if (
                !$this->comparar_texto($request->descr, $linha->descr) && 
                !$this->comparar_texto($request->patrimonio, $linha->patrimonio)
            ) return 400; // App\Http\Controllers\Controller.php
        }
        $linha->descr = mb_strtoupper($request->descr);
        $linha->patrimonio = mb_strtoupper($request->patrimonio);
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "maquinas", $linha->id); // App\Http\Controllers\Controller.php
        return redirect("/maquinas");
    }

    public function excluir(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php        
        if (!$this->aviso_main($request->id)->permitir) return 401; // App\Http\Controllers\Controller.php
        $linha = Maquinas::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "maquinas", $linha->id); // App\Http\Controllers\Controller.php
        return 200;
    }
    
    public function estoque(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php

        if ($this->consultar_estoque_main(
            $request->id_maquina,
            $request->id_produto,
            $request->produto,
            $request->qtd,
            $request->preco,
            $request->es
        )->texto) return 401;
        
        $recalcular = false;
        $comodato = $this->obter_comodato($request->id_maquina); // App\Http\Controllers\Controller.php
        for ($i = 0; $i < sizeof($request->id_produto); $i++) {
            $linha = new Estoque;

            $saldo_ant = $this->retorna_saldo_cp($comodato->id, $request->id_produto[$i]); // App\Http\Controllers\Controller.php

            $qtdRequest = floatval($request->qtd[$i]);
            $ajusteIgualEstoque = false;
            if ($request->es[$i] == "A") {
                if ($saldo_ant > $qtdRequest) {
                    $linha->es = "S";
                    $linha->qtd = $saldo_ant - $qtdRequest;
                } elseif ($saldo_ant < $qtdRequest) {
                    $linha->es = "E";
                    $linha->qtd = ($saldo_ant - $qtdRequest) * -1;
                } else $ajusteIgualEstoque = true;
            } else {
                $linha->es = $request->es[$i];
                $linha->qtd = $qtdRequest;
            }

            if (!$ajusteIgualEstoque) {
                $linha->descr = $request->obs[$i];
                $linha->preco = $request->preco[$i];
                $linha->data = date("Y-m-d");
                $linha->hms = date("H:i:s");
                $linha->id_cp = $comodato->cp($request->id_produto[$i])->value("id");
                $linha->save();
                $this->log_inserir("C", "estoque", $linha->id); // App\Http\Controllers\Controller.php
                $gestor = ComodatosProdutos::find($linha->id_cp);
                if ($gestor->preco != $linha->preco) {
                    $gestor->preco = $linha->preco;
                    $gestor->save();
                    $this->log_inserir("E", "comodatos_produtos", $gestor->id); // App\Http\Controllers\Controller.php
                }
            }
        }
        return redirect("/maquinas");
    }

    public function consultar_produto(Request $request) {
        return json_encode($this->consultar_comodatos_produtos( // App\Http\Controllers\Controller.php
            "maquina",
            $request->id_maquina,
            explode("|!|", $request->produtos_id),
            explode("|!|", $request->produtos_descr),
            explode("|!|", $request->precos),
            explode("|!|", $request->maximos)
        ));
    }

    public function consultar_estoque(Request $request) {
        return json_encode($this->consultar_estoque_main(
            $request->id_maquina,
            explode("|!|", $request->produtos_id),
            explode("|!|", $request->produtos_descr),
            explode("|!|", $request->quantidades),
            explode("|!|", $request->precos),
            explode("|!|", $request->es)
        ));
    }

    public function preco(Request $request) {
        $preco = $this->obter_comodato($request->id_maquina)->cp($request->id_produto)->value("preco"); // App\Http\Controllers\Controller.php
        if ($preco !== null) return $preco;
        return Produtos::find($request->id_produto)->preco;
    }

    public function consultar_comodato(Request $request) {
        return json_encode($this->consultar_comodato_main($request)); // App\Http\Controllers\Controller.php
    }

    public function criar_comodato(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php
        if ($this->consultar_comodato_main($request)->texto) return 401;
        $dtinicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->format('Y-m-d');
        $dtfim = Carbon::createFromFormat('d/m/Y', $request->fim)->format('Y-m-d');
        
        $linha = new Comodatos;
        $linha->id_maquina = $request->id_maquina;
        $linha->id_empresa = $request->id_empresa;
        $linha->inicio = $dtinicio;
        $linha->fim = $dtfim;
        $linha->fim_orig = $dtfim;
        $linha->travar_ret = $request->travar_ret;
        $linha->travar_estq = $request->travar_estq;
        $linha->atb_todos = $request->atb_todos;
        $linha->qtd = $request->qtd;
        $linha->obrigatorio = str_replace("opt-", "", $request->obrigatorio);
        $linha->validade = $request->validade;
        $linha->save();

        if ($this->gerar_atribuicoes($comodato)) $this->atualizar_tudo($request->id_maquina, "M", true); // App\Http\Controllers\Controller.php

        $this->log_inserir("C", "comodatos", $comodato->id); // App\Http\Controllers\Controller.php
        return redirect("/maquinas");
    }

    public function encerrar_comodato(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php
        $modelo = $this->obter_comodato($request->id_maquina); // App\Http\Controllers\Controller.php
        $modelo->fim = date('Y-m-d');
        $modelo->save();
        $this->log_inserir("E", "comodatos", $modelo->id); // App\Http\Controllers\Controller.php
        if ($this->gerar_atribuicoes($modelo)) $this->atualizar_tudo($request->id_maquina, "M", true); // App\Http\Controllers\Controller.php
        return redirect("/maquinas");
    }

    public function mostrar_comodato($id_maquina) {
        return json_encode(
            DB::table("comodatos")
                ->select(
                    DB::raw("DATE_FORMAT(comodatos.inicio, '%d/%m/%Y') AS inicio"),
                    DB::raw("DATE_FORMAT(comodatos.fim, '%d/%m/%Y') AS fim"),
                    "comodatos.atb_todos",
                    "comodatos.travar_ret",
                    "comodatos.travar_estq",
                    "comodatos.obrigatorio",
                    "comodatos.validade",
                    DB::raw("ROUND(comodatos.qtd) AS qtd"),
                    "empresas.nome_fantasia AS empresa",
                    "maquinas.descr AS maquina"
                )
                ->join("maquinas", "maquinas.id", "comodatos.id_maquina")
                ->join("empresas", "empresas.id", "comodatos.id_empresa")
                ->where("comodatos.id_maquina", $id_maquina)
                ->whereRaw("CURDATE() >= comodatos.inicio")
                ->whereRaw("CURDATE() < comodatos.fim")
                ->first()
        );
    }

    public function editar_comodato(Request $request) {
        if (!Permissoes::where("id_usuario", Auth::user()->id)->first()->atribuicoes) return 401;
        $obrigatorio = str_replace("opt-", "", $request->obrigatorio);
        $comodato = $this->obter_comodato($request->id_maquina); // App\Http\Controllers\Controller.php
        $atb_todos_ant = $comodato->atb_todos_ant;
        $comodato->travar_ret = $request->travar_ret;
        $comodato->travar_estq = $request->travar_estq;
        $comodato->atb_todos = $request->atb_todos;
        $comodato->qtd = $request->qtd;
        $comodato->obrigatorio = $obrigatorio;
        $comodato->validade = $request->validade;
        $comodato->save();
        $this->log_inserir("E", "comodatos", $comodato->id); // App\Http\Controllers\Controller.php
        if ($this->comparar_num($atb_todos_ant, $request->atb_todos)) { // App\Http\Controllers\Controller.php
            $this->gerar_atribuicoes($comodato); // App\Http\Controllers\Controller.php
            $this->atualizar_tudo($request->id_maquina, "M", true); // App\Http\Controllers\Controller.php
        }
        return redirect("/maquinas");
    }

    public function produto(Request $request) {
        if ($this->obter_empresa()) return 401; // App\Http\Controllers\Controller.php

        if ($this->consultar_comodatos_produtos( // App\Http\Controllers\Controller.php
            "maquina",
            $request->id_maquina,
            $request->id_produto,
            $request->produto,
            $request->preco,
            $request->maximo
        )->texto) return 401;
        
        $this->salvar_comodatos_produtos("maquina", $request); // App\Http\Controllers\Controller.php
        return redirect("/maquinas");
    }

    public function verificar_novo_cp(Request $request) {
        $id_cp = $this->obter_comodato($request->id_maquina)->cp($request->id_produto)->value("id"); // App\Http\Controllers\Controller.php
        if ($id_cp === null) return "1";
        $cp = ComodatosProdutos::find($id_cp);
        return (
            $this->comparar_num($cp->minimo, $request->minimo) || // App\Http\Controllers\Controller.php
            $this->comparar_num($cp->maximo, $request->maximo) || // App\Http\Controllers\Controller.php
            $this->comparar_num($cp->preco, $request->preco) || // App\Http\Controllers\Controller.php
            $this->comparar_num($cp->lixeira, $request->lixeira) // App\Http\Controllers\Controller.php
        ) ? "1" : "0";
    }
}