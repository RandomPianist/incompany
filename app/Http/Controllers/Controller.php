<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Log;
use App\Models\Atbbkp;
use App\Models\Pessoas;
use App\Models\Produtos;
use App\Models\Maquinas;
use App\Models\Retiradas;
use App\Models\Empresas;
use App\Models\Comodatos;
use App\Models\ComodatosProdutos;
use App\Models\Atribuicoes;
use App\Http\Traits\GlobaisTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, GlobaisTrait;

    protected function comparar_num($a, $b) {
        if ($a === null) $a = 0;
        if ($b === null) $b = 0;
        return floatval($a) != floatval($b);
    }

    protected function comparar_texto($a, $b) {
        if ($a === null) $a = "";
        if ($b === null) $b = "";
        return mb_strtoupper(trim($a)) != mb_strtoupper(trim($b));
    }

    protected function verifica_vazios(Request $request, $chaves) {
        $erro = "";
        foreach ($chaves as $chave) {
            if (!trim($request->input($chave))) $erro = $chave;
        }
        return $erro;
    }
    
    protected function view_mensagem($icon, $text) {
        return view("mensagem", compact("icon", "text"));
    }

    protected function consultar_geral_main($tabela, $id, $filtro) {
        $coluna = "descr";
        switch ($tabela) {
            case "empresas":
                $coluna = "nome_fantasia";
                break;
            case "pessoas":
                $coluna = "nome";
                break;
        }
        if ($tabela == "produtos") $tabela = "vprodaux";
        return DB::table($tabela)
                    ->where("id", $id)
                    ->where($coluna, $filtro)
                    ->where("lixeira", 0)
                    ->exists() ? "1" : "0";
    }

    protected function obter_where($id_pessoa, $tabela = "pessoas", $inclusive_excluidos = false) {
        $id_emp = Pessoas::find($id_pessoa)->id_empresa;
        $where = !in_array($tabela, ["comodatos", "retiradas"]) && !$inclusive_excluidos ? $tabela.".lixeira = 0" : "1";
        if (intval($id_emp)) {
            $where .= " AND ".($tabela != "empresas" ? $tabela.".id_empresa" : "empresas.id")." IN (
                SELECT id
                FROM empresas
                WHERE empresas.id = ".$id_emp."
                UNION ALL (
                    SELECT filiais.id
                    FROM empresas AS filiais
                    WHERE filiais.id_matriz = ".$id_emp."
                )
            )";
        }
        return $where;
    }

    protected function minhas_empresas() {
        $resultado = new \stdClass;
        $id_emp = $this->obter_empresa(); // App\Http\Traits\GlobaisTrait.php
        $matriz = $id_emp ? intval(Empresas::find($id_emp)->id_matriz) : 0;
        $filial = "N";
        if ($matriz > 0) {
            $filial = "S";
            $id_emp = $matriz;
        }
        $empresas = $this->busca_emp("T", $id_emp, 0);
        foreach($empresas as $matriz) {
            $filiais = $this->busca_emp("T", $id_emp, $matriz->id);
            $matriz->filiais = $filiais;
        }
        $resultado->filial = $filial;
        $resultado->empresas = $empresas;
        return $resultado;
    }

    protected function busca_emp($tipo, $id_emp = 0, $id_matriz = 0) {
        return DB::table("empresas")
            ->select(
                "id",
                "nome_fantasia",
                "id_matriz"
            )
            ->where(function($sql) use($tipo, $id_emp, $id_matriz) {
                if ($tipo == "T") {
                    $m_emp = $this->obter_empresa(); // App\Http\Traits\GlobaisTrait.php
                    $sql->where("id_matriz", $id_matriz);
                    if ($id_emp) {
                        $where = "id = ".$id_emp;
                        if (DB::table("empresas")
                                ->where("id_matriz", $id_emp)
                                ->where("lixeira", 0)
                                ->exists()
                        ) $where .= " OR id_matriz = ".$id_emp;
                        $sql->whereRaw("(".$where.")");
                    }
                    if ($m_emp) {
                        $possiveis = [$m_emp];
                        $matriz = intval(Empresas::find($possiveis[0])->id_matriz);
                        if ($matriz) {
                            array_push($possiveis, $matriz);
                            $sql->whereIn("id", $possiveis);
                        }
                    }
                } else {
                    $empresa_usuario = Pessoas::find(Auth::user()->id_pessoa)->empresa;
                    if ($empresa_usuario !== null) {
                        if ($tipo == "F" && !intval($empresa_usuario->id_matriz)) $sql->where("id_matriz", $empresa_usuario->id);
                        else $sql->where("id", $tipo == "M" ? !intval($empresa_usuario->id_matriz) ? $empresa_usuario->id : $empresa_usuario->id_matriz : $empresa_usuario->id);
                    } else $sql->where("id_matriz", $tipo == "M" ? "=" : ">", 0);
                }
            })
            ->where("lixeira", 0)
            ->orderBy("nome_fantasia")
            ->get();
    }
    
    protected function empresa_consultar(Request $request) {
        return $this->consultar_geral_main("empresas", $request->id_empresa, $request->empresa) == "0";
    }
    
    protected function supervisor_consultar(Request $request) {
        $supervisor = Pessoas::where("cpf", $request->cpf)
            ->where("senha", $request->senha)
            ->where("supervisor", 1)
            ->where("lixeira", 0)
            ->value("id");
        if ($supervisor === null) return 0;
        return $supervisor;
    }

    protected function obter_lista_permissoes() {
        return ["financeiro", "atribuicoes", "retiradas", "pessoas", "usuarios", "solicitacoes", "supervisor"];
    }

    protected function validar_permissoes(Request $request) {
        $erro = false;
        $permissoes = (array) DB::table("permissoes")->where("id_usuario", Auth::user()->id)->first();
        $lista = $this->obter_lista_permissoes();
        foreach ($lista as $permissao) {
            if (!$permissoes[$permissao] && intval($request->input($permissao))) $erro = true;
        }
        return $erro ? 401 : 200;
    }
     
    protected function alterar_usuario_editando($tabela, $id, $remover = false) {
        $consulta = DB::table($tabela)->where("id", $id);
        if ($remover || !intval($consulta->first()->id_usuario_editando)) $consulta->update(["id_usuario_editando" => $remover ? 0 : Auth::user()->id]);
        return $consulta->first();
    }

    protected function log_inserir($acao, $tabela, $fk, $origem = "WEB", $nome = "") {
        $linha = new Log;
        $linha->acao = $acao;
        $linha->origem = $origem;
        $linha->tabela = $tabela;
        $linha->fk = $fk;
        if (in_array($origem, ["WEB", "SYS"])) {
            $linha->id_pessoa = Auth::user()->id_pessoa;
            $linha->nome = Pessoas::find($linha->id_pessoa)->nome;
        } elseif ($nome) $linha->nome = $nome;
        $linha->data = date("Y-m-d");
        $linha->hms = date("H:i:s");
        $linha->save();
        if ($tabela == "users" && $acao == "D") {
            DB::statement("
                INSERT INTO usrbkp (
                    SELECT
                        id,
                        name,
                        email,
                        password,
                        id_pessoa,
                        admin,
                        created_at,
                        updated_at
                    
                    FROM users

                    WHERE id = ".$fk."
                )
            ");
        } elseif (in_array($tabela, ["categorias", "empresas", "pessoas", "produtos", "setores"])) $this->alterar_usuario_editando($tabela, $fk, true);
        return $linha;
    }

    protected function log_inserir_lote($acao, $tabela, $where, $origem = "WEB", $nome = "") {
        $id_pessoa = "NULL";
        if (in_array($origem, ["WEB", "SYS"])) {
            $id_pessoa = Auth::user()->id_pessoa;
            $nome = Pessoas::find($id_pessoa)->nome;
        }
        $query = $tabela;
        if (!$where) {
            $query .= " LEFT JOIN log ON log.fk = ".$tabela.".id AND log.tabela = '".$tabela."' ";
            $where = " log.id IS NULL ";
        }
        DB::statement("
            INSERT INTO log (acao, origem, tabela, fk, id_pessoa, nome, data, hms) (
                SELECT
                    '".$acao."',
                    '".$origem."',
                    '".$tabela."',
                    ".$tabela.".id,
                    ".$id_pessoa.",
                    ".($nome ? "'".$nome."'" : "NULL").",
                    '".date("Y-m-d")."',
                    '".date("H:i:s")."'
                
                FROM ".$query."

                WHERE ".$where."
            )
        ");
    }

    protected function obter_autor_da_solicitacao($solicitacao) {
        return Log::where("fk", $solicitacao)
                    ->where("tabela", "solicitacoes")
                    ->where("acao", "C")
                    ->value("id_pessoa");
    }

    protected function maquinas_da_pessoa($id_pessoa) {
        return DB::table("vativos")
                    ->where("id", $id_pessoa)
                    ->value("maquinas");
    }

    protected function obter_comodato($id_maquina) {
        return Comodatos::find(
            DB::table("comodatos")
                ->whereRaw("CURDATE() >= inicio AND CURDATE() < fim")
                ->where("id_maquina", $id_maquina)
                ->value("id")
        );
    }

    protected function maquinas_periodo($inicio, $fim) {
        $where = "";
        if ($inicio) $where .= "('".$inicio."' >= comodatos.inicio AND '".$inicio."' < comodatos.fim)";
        if ($fim) {
            if ($where) $where .= " OR ";
            $where .= "('".$fim."' >= comodatos.inicio AND '".$fim."' < comodatos.fim)";
        }
        $where = $where ? "(".$where.")" : "1";
        return DB::table("comodatos")
            ->selectRaw("DISTINCTROW comodatos.id_maquina")
            ->join(
                DB::raw("(
                    SELECT
                        pessoas.id AS id_pessoa,
                        pessoas.id_empresa
                    FROM pessoas
                    JOIN empresas
                        ON pessoas.id_empresa IN (empresas.id, empresas.id_matriz)
                    WHERE pessoas.lixeira = 0
                        AND empresas.lixeira = 0
                    
                    UNION ALL (
                        SELECT
                            pessoas.id AS id_pessoa,
                            empresas.id AS id_empresa

                        FROM pessoas

                        CROSS JOIN empresas

                        WHERE empresas.lixeira = 0
                            AND pessoas.id_empresa = 0
                            AND pessoas.lixeira = 0
                    )
                ) AS minhas_empresas"),
                function ($join) {
                    $join->on("minhas_empresas.id_empresa", "comodatos.id_empresa");
                }
            )
            ->whereRaw($where)
            ->where(function($sql) {
                $emp = $this->obter_empresa(); // App\Http\Traits\GlobaisTrait.php
                if ($emp) $sql->where("minhas_empresas.id_empresa", $emp);
            })
            ->pluck("id_maquina")
            ->toArray();
    }

    protected function dados_comodato(Request $request) {
        return DB::table("comodatos")
            ->select(
                DB::raw("MIN(inicio) AS inicio"),
                DB::raw("MAX(fim) AS fim")
            )
            ->whereRaw($this->obter_where(Auth::user()->id_pessoa, "comodatos"))
            ->where(function($sql) use($request) {
                if ($request->id_maquina) $sql->where("id_maquina", $request->id_maquina);
            })
            ->first();
    }

    protected function maquina_consultar(Request $request) {
        return ((
            trim($request->maquina) &&
            !Maquinas::where("id", $request->id_maquina)->where("descr", $request->maquina)->where("lixeira", 0)->exists()
        ) || (
            trim($request->id_maquina) && !trim($request->maquina)
        ));
    }

    protected function retorna_saldo_cp($id_comodato, $id_produto) {
        $consulta = DB::table("comodatos_produtos AS cp")
                        ->selectRaw("IFNULL(vestoque.qtd, 0) AS saldo")
                        ->leftjoin("vestoque", "vestoque.id_cp", "cp.id")
                        ->where("cp.id_comodato", $id_comodato)
                        ->where("cp.id_produto", $id_produto)
                        ->first();
        if ($consulta === null) return 0;
        return floatval($consulta->saldo);
    }
    
    public function consultar_comodatos_produtos($contexto, $id_principal, $ids, $descricoes, $precos, $maximos) {
        $texto = "";
        $campos = [];
        $valores = [];

        for ($i = 0; $i < sizeof($ids); $i++) {;
            if (
                !DB::table($contexto == "maquina" ? "vprodaux" : "maquinas")
                    ->where("id", $ids[$i])
                    ->where("descr", $descricoes[$i])
                    ->where("lixeira", 0)
                    ->exists()
            ) {
                array_push($campos, ($contexto == "maquina" ? "produto-" : "maquina-")."-".($i + 1));
                array_push($valores, $descricoes[$i]);
                if ($contexto == "produto") $texto = !$texto ? "Máquina não encontrada" : "Máquinas não encontradas";
                else $texto = !$texto ? "Produto não encontrado" : "Produtos não encontrados";
            }
        }
        if ($texto) goto retornar;

        for ($i = 0; $i < sizeof($ids); $i++) {
            $id_maquina = $contexto == "maquina" ? $id_principal : $ids[$i];
            $id_produto = $contexto == "maquina" ? $ids[$i] : $id_principal;
            
            $comodato = $this->obter_comodato($id_maquina);
            if (!$comodato) continue;

            $saldo = $this->retorna_saldo_cp($comodato->id, $id_produto);
            if (floatval($maximos[$i]) && floatval($maximos[$i]) < $saldo) {
                array_push($campos, "max-".($i + 1));
                array_push($valores, $saldo);
                $texto = 
                    $texto ?
                        "Esse valor de estoque máximo é inferior ao saldo atual do produto.<br>O campo foi corrigido."
                    :
                        "Esses valores de estoque máximo são inferiores ao saldo atual dos produtos.<br>Os campos foram corrigidos."
                ;
            }
        }
        if ($texto) goto retornar;

        for ($i = 0; $i < sizeof($ids); $i++) {
            if (!ceil(floatval($precos[$i]))) {
                $texto = $texto ? "Há preços zerados" : "Há um preço zerado";
                array_push($campos, "preco-" . ($i + 1));
                array_push($valores, "0");
            }
        }
        if ($texto) goto retornar;

        for ($i = 0; $i < sizeof($ids); $i++) {
            $id_produto = $contexto == "maquina" ? $ids[$i] : $id_principal;
            $prmin = floatval(DB::table("produtos")->where("id", $id_produto)->value("prmin") ?? 0);
            
            if ($prmin > 0 && floatval($precos[$i]) < $prmin) {
                $texto = $texto ? "Há itens com preço abaixo do mínimo.<br>Os campos foram corrigidos" : "Há um item com um preço abaixo do mínimo.<br>O campo foi corrigido";
                $texto .= " para o preço mínimo.<br>Por favor, verifique e tente novamente.";
                array_push($campos, "preco-".($i + 1));
                array_push($valores, $prmin);
            }
        }

        retornar:
        $resultado = new \stdClass;
        $resultado->texto = $texto;
        $resultado->campos = $campos;
        $resultado->valores = $valores;
        return $resultado;
    }

    public function salvar_comodatos_produtos($contexto, Request $request) {
        $id_principal = $contexto == "maquina" ? $request->id_maquina : $request->id_produto;
        $ids = $contexto == "maquina" ? $request->id_produto : $request->id_maquina;
        
        $connection = DB::connection();
        $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
        $connection->beginTransaction();
        try {
            $maquinas_para_atualizar = [];

            for ($i = 0; $i < count($ids); $i++) {
                $id_maquina = $contexto == "maquina" ? $id_principal : $ids[$i];
                $id_produto = $contexto == "maquina" ? $ids[$i] : $id_principal;
                
                $comodato = $this->obter_comodato($id_maquina);
                if (!$comodato) continue;

                $modelo = ComodatosProdutos::firstOrNew([
                    "id_comodato" => $comodato->id,
                    "id_produto" => $id_produto
                ]);

                $lixeira = str_replace("opt-", "", $request->lixeira[$i]);
                $preco = $request->preco[$i];
                $maximo = $request->maximo[$i];
                $minimo = $request->minimo[$i];
                
                $houveAlteracao = !$modelo->exists ||
                    $this->comparar_num($modelo->lixeira, $lixeira) ||
                    $this->comparar_num($modelo->preco, $preco) ||
                    $this->comparar_num($modelo->maximo, $maximo) ||
                    $this->comparar_num($modelo->minimo, $minimo);

                if ($houveAlteracao) {
                    $letra_log = $modelo->exists ? "E" : "C";
                    $modelo->preco = $preco;
                    $modelo->maximo = $maximo;
                    $modelo->minimo = $minimo;
                    $modelo->lixeira = $lixeira;
                    $modelo->save();
                    $this->log_inserir($letra_log, "comodatos_produtos", $modelo->id);
                }
                
                if ($this->gerar_atribuicoes($comodato)) array_push($maquinas_para_atualizar, $id_maquina);
            }

            if (!empty($maquinas_para_atualizar)) $this->atualizar_tudo(array_unique($maquinas_para_atualizar), "M", true);
            $connection->commit();
            return "/".($contexto == "maquina" ? "maquinas" : "produtos");
        } catch (\Exception $e) {    
            $connection->rollBack();
            return $e->getMessage();
        }
    }
    
    protected function gerar_atribuicoes(Comodatos $comodato) {
        $ret = false;
        $where = "lixeira = 0 AND id_maquina = ".$comodato->id_maquina." AND id_empresa = ".$comodato->id_empresa;
        $where_g = $where." AND gerado = 1";
        if (!$comodato->atb_todos) {
            $ret = Atribuicoes::whereRaw($where_g)->exists();
            if ($ret) {
                $this->log_inserir_lote("D", "atribuicoes", $where_g);
                Atribuicoes::whereRaw($where_g)->update(["lixeira" => 1]);
                $this->excluir_atribuicao_sem_retirada();
            }
            return $ret;
        }
        $lista_itens = DB::table("produtos")
            ->select(
                DB::raw("IFNULL(produtos.cod_externo, '') AS cod_externo"),
                DB::raw("IFNULL(produtos.referencia, '') AS referencia")
            )
            ->join("comodatos_produtos AS cp", "cp.id_produto", "produtos.id")
            ->where("cp.id_comodato", $comodato->id)
            ->where("cp.lixeira", 0)
            ->where("produtos.lixeira", 0)
            ->get();
        foreach ($lista_itens as $item) {
            $modelo = null;
            $letra_log = "E";
            $continua = true;
            $atb = Atribuicoes::whereRaw($where)->where("referencia", $item->referencia)->first();
            if ($atb !== null) {
                if (intval($atb->gerado)) $modelo = Atribuicoes::find($atb->id);
                else $continua = false;
            }
            if (!$continua) {
                $atb = Atribuicoes::whereRaw($where)->where("cod_produto", $item->cod_externo)->first();
                if ($atb !== null) {
                    if (intval($atb->gerado)) $modelo = Atribuicoes::find($atb->id);
                    else $continua = false;
                }
            }
            if ($continua && $modelo === null) {
                $modelo = new Atribuicoes;
                $letra_log = "C";
            }
            if ($modelo !== null && (
                $this->comparar_num($comodato->qtd, $modelo->qtd) ||
                $this->comparar_num($comodato->validade, $modelo->validade) ||
                $this->comparar_num($comodato->obrigatorio, $modelo->obrigatorio)
            )) {
                $modelo->gerado = 1;
                $modelo->qtd = $comodato->qtd;
                $modelo->validade = $comodato->validade;
                $modelo->obrigatorio = $comodato->obrigatorio;
                $modelo->id_maquina = $comodato->id_maquina;
                $modelo->id_empresa = $comodato->id_empresa;
                $modelo->referencia = $item->referencia ? $item->referencia : null;
                $modelo->cod_produto = $item->referencia ? null : $item->cod_externo;
                $modelo->id_empresa_autor = $this->obter_empresa(); // App\Http\Traits\GlobaisTrait.php
                $modelo->data = date("Y-m-d");
                $modelo->save();
                $this->log_inserir($letra_log, "atribuicoes", $modelo->id);
                if ($letra_log == "C") $ret = true;
            }
        }
        return $ret;
    }

    protected function atribuicao_atualiza_ref($id, $antigo, $novo, $nome = "", $api = false) {
        if ($id && $this->comparar_texto($antigo, $novo)) {
            try {
                $novo = trim($novo);
                $where = "referencia = '".$antigo."'";
                $lista = DB::table("vatbold")
                    ->select(
                        "psm_chave",
                        "psm_valor"
                    )
                    ->whereRaw($where)
                    ->groupby(
                        "psm_chave",
                        "psm_valor"
                    )
                    ->get();
                $this->log_inserir_lote($novo ? "E" : "D", "atribuicoes", $where, $api ? "ERP" : "WEB", $nome);
                Atribuicoes::whereRaw($where)->update($novo ? ["referencia" => $novo] : ["lixeira" => 1]);
                $this->atualizar_atribuicoes($lista);
                $connection->commit();
            } catch (\Exception $e) {
                $connection->rollBack();
                throw $e;
            }
        }
    }

    protected function atribuicao_listar($consulta) {
        $resultado = array();
        foreach ($consulta as $linha) {
            $linha->pode_editar = 1;
            $mostrar = true;
            if ($mostrar) array_push($resultado, $linha);
        }
        return $resultado;
    }
    
    protected function backup_atribuicao(Atribuicoes $atribuicao) {
        $bkp = new Atbbkp;
        $bkp->qtd = $atribuicao->qtd;
        $bkp->data = $atribuicao->data;
        $bkp->validade = $atribuicao->validade;
        $bkp->obrigatorio = $atribuicao->obrigatorio;
        $bkp->gerado = $atribuicao->gerado;
        $bkp->id_usuario = $atribuicao->id_usuario;
        $bkp->id_atribuicao = $atribuicao->id;
        $bkp->id_usuario_editando = Auth::user()->id;
        $bkp->save();
    }
    
    protected function retirada_consultar($id_atribuicao, $qtd, $id_pessoa) {
        // --- Início da Lógica de Refatoração ---
    
        // Construímos uma query que replica a lógica da vpendentesgeral,
        // mas apenas para a atribuição e pessoa específicas.
        $consulta = DB::table("vatbold")
            // A cláusula WHERE principal torna a consulta extremamente rápida.
            ->where("vatbold.id", $id_atribuicao)
            // Precisamos do produto para os joins seguintes.
            ->join('produtos', function ($join) {
                $join->on('produtos.cod_externo', '=', 'vatbold.cod_produto')
                     ->orOn('produtos.referencia', '=', 'vatbold.referencia');
            })
            // Trazemos o histórico de retiradas (pode não existir -> LEFT JOIN)
            ->leftJoin('mat_vretiradas', function($join) use ($id_pessoa) {
                $join->on('mat_vretiradas.id_atribuicao', '=', 'vatbold.id')
                     ->where('mat_vretiradas.id_pessoa', '=', $id_pessoa);
            })
            // Trazemos a última retirada (pode não existir -> LEFT JOIN)
            ->leftJoin('mat_vultretirada', function($join) use ($id_pessoa) {
                $join->on('mat_vultretirada.id_atribuicao', '=', 'vatbold.id')
                     ->where('mat_vultretirada.id_pessoa', '=', $id_pessoa);
            })
            // Trazemos a contagem de pré-retiradas (pode não existir -> LEFT JOIN)
            ->leftJoin(DB::raw("(
                SELECT id_produto, id_pessoa, COUNT(id) AS qtd
                FROM pre_retiradas
                GROUP BY id_produto, id_pessoa
            ) AS prev"), function ($join) use ($id_pessoa) {
                $join->on('prev.id_produto', '=', 'produtos.id')
                     ->where('prev.id_pessoa', '=', $id_pessoa);
            })
            // Trazemos informações de estoque da pessoa (deve existir -> INNER JOIN)
            ->join('vprodutosgeral AS vprodutos', function ($join) use ($id_pessoa) {
                $join->on('vprodutos.id_pessoa', '=', $id_pessoa)
                     ->on('vprodutos.id_produto', '=', 'produtos.id');
            })
            // Aqui replicamos o filtro 'esta_pendente = 1'
            ->where(function($query) {
                // Condição 1: A retirada está vencida ou nunca ocorreu.
                $query->whereRaw("DATE_ADD(IFNULL(mat_vultretirada.data, '1900-01-01'), INTERVAL vatbold.validade DAY) <= CURDATE()");
                // Condição 2: A quantidade pendente (descontando retiradas e pré-retiradas) é maior que zero.
                $query->whereRaw("(vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))) > 0");
            })
            // E aqui calculamos e extraímos o valor 'qtd' da antiga VIEW
            ->value(DB::raw("
                ROUND(
                    CASE
                        WHEN (vprodutos.travar_estq = 1) THEN
                            CASE
                                WHEN (vprodutos.qtd >= (vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))))
                                THEN (vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0)))
                                ELSE vprodutos.qtd
                            END
                        ELSE (vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0)))
                    END
                )
            "));
    
        // --- Fim da Lógica de Refatoração ---
    
        // A lógica de negócio original da função permanece idêntica.
        if ($consulta === null) {
            return 0;
        }
        return floatval($consulta) > floatval($qtd) ? 0 : 1;
    }

    protected function retirada_salvar($json) {
        $comodato = intval($json["id_comodato"]);
        $api = $comodato > 0;

        $consulta = $api ?
            DB::table("comodatos_produtos AS cp")
                ->select(
                    "produtos.ca",
                    DB::raw("IFNULL(cp.preco, produtos.preco) AS preco")
                )
                ->join("produtos", "produtos.id", "cp.id_produto")
                ->join("comodatos", "comodatos.id", "cp.id_comodato")
                ->where("comodatos.id", $comodato)
        :
            DB::table("produtos")
                ->select(
                    "ca",
                    "preco"
                )
        ;
        $consulta_produto = $consulta->where("produtos.id", $json["id_produto"])->first();

        $pessoa = Pessoas::find($json["id_pessoa"]);
        $linha = new Retiradas;
        if (isset($json["obs"])) $linha->observacao = $json["obs"];
        if (isset($json["hora"])) $linha->hms = $json["hora"];
        if (isset($json["biometria"])) $linha->biometria = $json["biometria"];
        if (isset($json["id_supervisor"])) {
            if (intval($json["id_supervisor"])) $linha->id_supervisor = $json["id_supervisor"];
        }
        $linha->id_pessoa = $pessoa->id;
        $linha->id_atribuicao = $json["id_atribuicao"];
        $linha->id_produto = $json["id_produto"];
        $linha->id_comodato = $comodato;
        $linha->qtd = $json["qtd"];
        $linha->data = $json["data"];
        $linha->id_empresa = $pessoa->id_empresa;
        $linha->id_setor = $pessoa->id_setor;
        $linha->preco = $consulta_produto->preco;
        $linha->ca = $consulta_produto->ca;
        $linha->save();
        
        $reg_log = $this->log_inserir("C", "retiradas", $linha->id, $api ? "APP" : "WEB");
        if ($api) {
            $reg_log->id_pessoa = $pessoa->id;
            $reg_log->nome = $pessoa->nome;
            $reg_log->save();
        }
    }

    protected function atualizar_tudo($valor, $chave = "M", $completo = false, $id_pessoa = "0") {
        $valor_ant = $valor;
        if (is_iterable($valor)) $valor = implode(",", $valor);
        $valor = "'".$valor."'";
        if ($completo) {
            switch($chave) {
                case "P":
                    $minhas_maquinas = explode(",", $this->maquinas_da_pessoa($valor_ant));
                    foreach ($minhas_maquinas as $maq) $this->atualizar_mat_vcomodatos($maq);
                    break;
                case "M":
                    if (is_iterable($valor_ant)) {
                        foreach ($valor_ant as $maq) $this->atualizar_mat_vcomodatos($maq);
                    } else $this->atualizar_mat_vcomodatos($valor_ant);
                    break;
            }
        }
        // $this->atualizar_mat_vretiradas_vultretirada($chave, $valor, "R", false);
        // $this->atualizar_mat_vretiradas_vultretirada($chave, $valor, "U", false);
        if ($completo) $this->excluir_atribuicao_sem_retirada();
    }

    protected function atualizar_atribuicoes($consulta) {
        foreach ($consulta as $linha) $this->atualizar_tudo($linha->psm_valor, $linha->psm_chave);
        $this->excluir_atribuicao_sem_retirada();
    }
    
    protected function extrato_consultar_main(Request $request) {
        $resultado = new \stdClass;
        if (isset($request->maquina)) {
            if ($this->maquina_consultar($request)) {
                $resultado->el = "maquina";
                return $resultado;
            }
        }
        if (((trim($request->produto) && !(
            DB::table("vprodaux")
                ->where("id", $request->id_produto)
                ->where("descr", $request->produto)
                ->where("lixeira", 0)
                ->exists()
        )) || (trim($request->id_produto) && !trim($request->produto)))) {
            $resultado->el = "produto";
            return $resultado;
        }
        if ($request->inicio || $request->fim) {
            $consulta = $this->dados_comodato($request);
            $elementos = array();
            if ($request->inicio) {
                $inicio = Carbon::createFromFormat('d/m/Y', $request->inicio)->startOfDay();
                $consulta_inicio = Carbon::parse($consulta->inicio)->startOfDay();
                if ($inicio->lessThan($consulta_inicio)) {
                    $resultado->inicio_correto = $consulta_inicio->format("d/m/Y");
                    array_push($elementos, "inicio");
                }
            }
            if ($request->fim) {
                $fim = Carbon::createFromFormat('d/m/Y', $request->fim)->startOfDay();
                $consulta_fim = Carbon::parse($consulta->fim)->startOfDay();
                if ($fim->greaterThan($consulta_fim)) {
                    $resultado->fim_correto = $consulta_fim->format("d/m/Y");
                    array_push($elementos, "fim");
                }
            }
            $resultado->varias_maquinas = $request->id_maquina ? "N" : "S";
            $resultado->el = join(",", $elementos);
            return $resultado;
        }
        $resultado->el = "";
        return $resultado;
    }

    protected function sugestao_main(Request $request) {
        $criterios = array();
        array_push($criterios, "Período de ".$request->inicio." até ".$request->fim);
        $lm = $request->lm == "S";
        $tipo = $request->tipo;
        $dias = intval($request->dias);
        $dtinicio = Carbon::createFromFormat('d/m/Y', $request->inicio);
        $dtfim = Carbon::createFromFormat('d/m/Y', $request->fim);
        $diferenca = $dtinicio->diffInDays($dtfim);
        $inicio = $dtinicio->format('Y-m-d');
        $fim = $dtfim->format('Y-m-d');
        if (!$diferenca) $diferenca = 1;
        
        $resultado = collect(
            DB::table("comodatos_produtos AS cp")
                ->select(
                    // GRUPO
                    "mq.id AS id_maquina",
                    "mq.descr AS maquina",

                    // DETALHES
                    "vprodaux.id AS id_produto",
                    "vprodaux.descr AS produto",
                    "cp.minimo",

                    DB::raw("
                        SUM(
                            CASE
                                WHEN (estq.data >= cm.inicio AND estq.data < '".$inicio."') THEN
                                    CASE
                                        WHEN estq.es = 'E' THEN estq.qtd
                                        ELSE estq.qtd * -1
                                    END
                                ELSE 0
                            END
                        ) AS saldo_ant
                    "),
                    DB::raw("
                        SUM(
                            CASE
                                WHEN (estq.data >= '".$inicio."' AND estq.data < '".$fim."') THEN
                                    CASE
                                        WHEN estq.es = 'E' THEN estq.qtd
                                        ELSE 0
                                    END
                                ELSE 0
                            END
                        ) AS entradas
                    "),
                    DB::raw("
                        SUM(
                            CASE
                                WHEN (estq.data >= '".$inicio."' AND estq.data < '".$fim."' AND estq.origem = 'ERP') THEN
                                    CASE
                                        WHEN estq.es = 'S' THEN estq.qtd
                                        ELSE 0
                                    END
                                ELSE 0
                            END
                        ) AS saidas_avulsas
                    "),
                    DB::raw("
                        SUM(
                            CASE
                                WHEN (estq.data >= '".$inicio."' AND estq.data < '".$fim."' AND estq.origem <> 'ERP') THEN
                                    CASE
                                        WHEN estq.es = 'S' THEN estq.qtd
                                        ELSE 0
                                    END
                                ELSE 0
                            END
                        ) AS retiradas
                    ")
                )
                ->join("vprodaux", "vprodaux.id", "cp.id_produto")
                ->joinSub(
                    DB::table("comodatos")
                        ->select(
                            "id",
                            "id_maquina",
                            "inicio"
                        )
                        ->whereRaw("('".$inicio."' BETWEEN comodatos.inicio AND comodatos.fim) OR ('".$fim."' BETWEEN comodatos.inicio AND comodatos.fim)"),
                    "cm",
                    "cm.id",
                    "cp.id_comodato"
                )
                ->joinSub(
                    DB::table("maquinas")
                        ->select(
                            "id",
                            "descr"
                        )
                        ->where(function($sql) use($request, $inicio, $fim, &$criterios) {
                            if ($this->obter_empresa()) { // App\Http\Traits\GlobaisTrait.php
                                $sql->whereIn("id", $this->maquinas_periodo($inicio, $fim));
                            }
                            if ($request->id_maquina) {
                                $maquina = Maquinas::find($request->id_maquina);
                                array_push($criterios, "Máquina: ".$maquina->descr);
                                $sql->where("id", $maquina->id);
                            }
                        })
                        ->where("lixeira", 0),
                    "mq",
                    "mq.id",
                    "cm.id_maquina"
                )
                ->joinSub(
                    DB::table("estoque")
                        ->select(
                            "estoque.id_cp",
                            "estoque.es",
                            "estoque.qtd",
                            "log.data",
                            "log.origem"
                        )
                        ->leftjoin("log", function($join) {
                            $join->on("log.fk", "estoque.id")
                                ->where("log.tabela", "estoque");
                        }),
                    "estq",
                    "estq.id_cp",
                    "cp.id"
                )
                ->where(function($sql) use($request, &$criterios) {
                    if ($request->id_produto) {
                        $produto = Produtos::find($request->id_produto);
                        array_push($criterios, "Produto: ".$produto->descr);
                        $sql->where("vprodaux.id", $produto->id);
                    }
                })
                ->where("vprodaux.lixeira", 0)
                ->groupby(
                    "mq.id",
                    "mq.descr",
                    "vprodaux.id",
                    "vprodaux.descr",
                    "cp.minimo"
                )
                ->get()
        )->groupBy("id_maquina")->map(function($maquinas) use($dias, $diferenca, $tipo, $lm) {
            $produtos = $maquinas->map(function($produto) use($dias, $diferenca, $tipo) {
                $saldo_ant = floatval($produto->saldo_ant);
                $entradas = floatval($produto->entradas);
                $saidas_avulsas = floatval($produto->saidas_avulsas);
                $retiradas = floatval($produto->retiradas);
                $minimo = floatval($produto->minimo);
                $saidas_totais = $saidas_avulsas + $retiradas;
                $saldo_res = $saldo_ant + $entradas - $saidas_totais;
                $giro = $retiradas / $diferenca;
                $sugeridos = $tipo == "G" ? (($giro * $dias) - $saldo_res) : ($minimo - $saldo_res);
                if ($sugeridos < 0) $sugeridos = 0;
                return [
                    "id" => $produto->id_produto,
                    "descr" => $produto->produto,
                    "saldo_ant" => number_format($saldo_ant, 0),
                    "entradas" => number_format($entradas, 0),
                    "saidas_avulsas" => number_format($saidas_avulsas, 0),
                    "retiradas" => number_format($retiradas, 0),
                    "minimo" => number_format($minimo, 0),
                    "saidas_totais" => number_format($saidas_totais, 0),
                    "saldo_res" => number_format($saldo_res, 0),
                    "giro" => number_format($giro, 2),
                    "sugeridos" => ceil($sugeridos)
                ];
            })->filter(function($produto) use ($lm) {
                return !$lm || intval($produto["sugeridos"]);
            })->sortBy("descr")->values();
            if ($produtos->isEmpty()) return null;
            return [
                "maquina" => [
                    "id" => $maquinas[0]->id_maquina,
                    "descr" => $maquinas[0]->maquina,
                    "produtos" => $produtos->all()
                ]
            ];
        })->filter()->sortBy(fn($m) => $m["maquina"]["descr"])->values()->all();
        if ($tipo == "G") array_push($criterios, "Compra sugerida para ".$dias." dia".($dias > 1 ? "s" : ""));
        if ($lm) array_push($criterios, "Apenas produtos cuja compra é sugerida");
        $tela = new \stdClass;
        $tela->resultado = $resultado;
        $tela->criterios = implode(" | ", $criterios);
        return $tela;
    }

    protected function nomear($id) {
        $pessoa = Pessoas::find($id);
        if (!intval($pessoa->id_empresa)) return "administrador";
        if (DB::table("users")->where("id_pessoa", $id)->exists()) return "usuário";
        if ($pessoa->supervisor) return "supervisor";
        return "funcionário";
    }

    private function atualizar_mat_vcomodatos($id_maquina) {
        DB::table("mat_vcomodatos")
            ->where("id_maquina", $id_maquina)
            ->delete();
        $campos = "
            comodatos.id,
            minhas_empresas.id_pessoa,
            comodatos.id_maquina,
            comodatos.travar_estq,
            comodatos.id_empresa
        ";
        DB::statement("
            INSERT INTO mat_vcomodatos (
                SELECT ".$campos."

                FROM (
                    SELECT
                        p.id AS id_pessoa,
                        p.id_empresa
                    FROM pessoas AS p
                    JOIN empresas
                        ON p.id_empresa IN (empresas.id, empresas.id_matriz)
                    WHERE p.lixeira = 0
                      AND empresas.lixeira = 0
                ) AS minhas_empresas
                
                JOIN comodatos
                    ON comodatos.id_empresa = minhas_empresas.id_empresa

                WHERE comodatos.id_maquina = ".$id_maquina."
                  AND (CURDATE() >= comodatos.inicio AND CURDATE() < comodatos.fim)

                GROUP BY ".$campos."
            )
        ");
    }

    protected function atualizar_mat_vretiradas_vultretirada($chave, $valor, $tipo, $apenas_ativos) {
        // Definir nomes de tabelas e colunas
        $tabela_pessoas = $apenas_ativos ? "vativos" : "pessoas";
        $targetTable = $tipo == "R" ? "mat_vretiradas" : "mat_vultretirada";
    
        // =================================================================
        // PASSO 1: OBTER A FONTE DE DADOS JÁ FILTRADA E OTIMIZADA
        // =================================================================
        $atribuicoesQuery = $this->retorna_atb_aux($chave, $valor, $apenas_ativos, 0);
    
        // =================================================================
        // PASSO 2: CALCULAR AS ATRIBUIÇÕES PRIORITÁRIAS
        // =================================================================
        $prioridades = DB::table(DB::raw("({$atribuicoesQuery}) AS sub_atb"))
            ->select(
                "sub_atb.id_pessoa",
                "sub_atb.id_produto",
                DB::raw("MIN(sub_atb.grandeza) as min_grandeza")
            )
            ->where('sub_atb.lixeira', '=', 0)
            ->groupBy("sub_atb.id_pessoa", "sub_atb.id_produto");
    
        $atribuicoesPriorizadas = DB::table(DB::raw("({$atribuicoesQuery}) AS atb_bruto"))
            ->select('atb_bruto.id_pessoa', 'atb_bruto.id_atribuicao', 'atb_bruto.id_produto')
            ->joinSub($prioridades, 'prioridades', function ($join) {
                $join->on('atb_bruto.id_pessoa', '=', 'prioridades.id_pessoa')
                     ->on('atb_bruto.id_produto', '=', 'prioridades.id_produto')
                     ->on('atb_bruto.grandeza', '=', 'prioridades.min_grandeza');
            });
    
        // =================================================================
        // PASSO 3: CONSTRUIR A QUERY DE INSERT DE FORMA CONDICIONAL
        // =================================================================
        if ($tipo == "U") {
            // Lógica para mat_vultretirada
            $valueCalculation = DB::raw("MAX(retiradas.data) AS data");
    
            // Recria a lógica de 'id_associado' dinamicamente
            $atribuicoesComAssociados = DB::table($atribuicoesPriorizadas, 'main_atb')
                ->select(
                    'main_atb.id_pessoa',
                    'main_atb.id_atribuicao',
                    'associada.id_atribuicao AS id_associado'
                )
                ->join(DB::raw("({$atribuicoesQuery}) AS associada"), function ($join) {
                    $join->on('main_atb.id_pessoa', '=', 'associada.id_pessoa')
                         ->on('main_atb.id_produto', '=', 'associada.id_produto');
                })
                ->groupBy('main_atb.id_pessoa', 'main_atb.id_atribuicao', 'id_associado');
    
            $sourceQuery = DB::table('vatbreal')
                ->select('atb.id_pessoa', 'atb.id_atribuicao', 'p.id_setor', $valueCalculation)
                // *** CORREÇÃO: Apenas UM joinSub com o alias 'atb' é feito aqui ***
                ->joinSub($atribuicoesComAssociados, 'atb', 'atb.id_atribuicao', '=', 'vatbreal.id')
                ->join('vatbreal AS associadas', 'associadas.id', '=', 'atb.id_associado')
                ->join($tabela_pessoas . ' AS p', 'p.id', '=', 'atb.id_pessoa')
                ->leftJoin('retiradas', function ($join) {
                    $join->on('retiradas.id_atribuicao', '=', 'associadas.id')
                         ->on('retiradas.id_pessoa', '=', 'p.id')
                         ->whereRaw('p.id_empresa IN (0, retiradas.id_empresa)')
                         ->whereNull('retiradas.id_supervisor');
                });
    
        } else { // $tipo == "R"
            // Lógica para mat_vretiradas
            $valueCalculation = DB::raw("IFNULL(SUM(retiradas.qtd), 0) AS valor");
            
            $sourceQuery = DB::table('vatbreal')
                ->select('atb.id_pessoa', 'atb.id_atribuicao', 'p.id_setor', $valueCalculation)
                // *** CORREÇÃO: Apenas UM joinSub com o alias 'atb' é feito aqui ***
                ->joinSub($atribuicoesPriorizadas, 'atb', 'atb.id_atribuicao', '=', 'vatbreal.id')
                ->join($tabela_pessoas . ' AS p', 'p.id', '=', 'atb.id_pessoa')
                ->leftJoin('retiradas', function ($join) {
                    $join->on('retiradas.id_atribuicao', '=', 'vatbreal.id')
                         ->on('retiradas.id_pessoa', '=', 'p.id')
                         ->whereRaw('p.id_empresa IN (0, retiradas.id_empresa)')
                         ->whereRaw('retiradas.data >= vatbreal.data')
                         ->whereRaw('retiradas.data > DATE_SUB(CURDATE(), INTERVAL vatbreal.validade DAY)')
                         ->whereNull('retiradas.id_supervisor');
                });
        }
    
        $sourceQuery->groupBy('atb.id_pessoa', 'atb.id_atribuicao', 'p.id_setor');
    
        // =================================================================
        // PASSO 4: EXECUTAR AS OPERAÇÕES DE DELETE E INSERT
        // =================================================================
        $valorArray = explode(',', $valor);
        switch($chave) {
            case "M":
                DB::statement("DELETE {$targetTable} FROM {$targetTable} JOIN mat_vcomodatos ON mat_vcomodatos.id_pessoa = {$targetTable}.id_pessoa WHERE mat_vcomodatos.id_maquina IN ({$valor})");
                break;
            case "S":
                DB::statement("DELETE {$targetTable} FROM {$targetTable} JOIN {$tabela_pessoas} ON {$tabela_pessoas}.id = {$targetTable}.id_pessoa WHERE {$tabela_pessoas}.id_setor IN ({$valor})");
                break;
            default:
                DB::table($targetTable)
                    ->where(function($sql) use($chave, $valor) {
                        if ($chave == "P") $sql->whereRaw("id_pessoa IN (".$valor.")");
                    })
                    ->delete();
        }
    
        $bindings = $sourceQuery->getBindings();
        $sql = $sourceQuery->toSql();
        DB::insert("INSERT INTO {$targetTable} (id_pessoa, id_atribuicao, id_setor, ".($tipo == 'R' ? 'valor' : 'data').") ".$sql, $bindings);
    }

    private function excluir_atribuicao_sem_retirada() {
        DB::statement("
            DELETE a
            FROM atribuicoes a
            LEFT JOIN retiradas r ON r.id_atribuicao = a.id
            WHERE r.id IS NULL
              AND a.lixeira = 1 AND a.gerado = 0
        ");
        DB::statement("
            DELETE l
            FROM log l
            LEFT JOIN atribuicoes a2 ON a2.id = l.fk
            WHERE a2.id IS NULL
              AND l.tabela = 'atribuicoes'
        ");
    }

    protected function retorna_atb_aux($chave, $valor, $apenas_ativos, $id_pessoa) {
        $tabela = $apenas_ativos ? "vativos" : "pessoas";
        $where = "(users.admin = 0 OR users.id IS NULL OR vatbreal.gerado = 0) AND excecoes.id IS NULL AND cp.lixeira = 0 AND produtos.lixeira = 0";
        if ($id_pessoa) $where .= " AND p.id = ".$id_pessoa;
        else if ($chave == "P") $where .= " AND p.id IN (".$valor.")";
        else if ($chave == "S") $where .= " AND p.id_setor IN (".$valor.")";
        else if ($chave == "M") $where .= " AND mat_vcomodatos.id_maquina IN (".$valor.")";
        $leftjoin = "
            LEFT JOIN users
                ON users.id_pessoa = p.id

            LEFT JOIN excecoes
                ON (excecoes.id_setor = p.id_setor OR excecoes.id_pessoa = p.id)
                    AND vatbreal.id = excecoes.id_atribuicao
                    AND excecoes.rascunho = 'S'
        ";
        $joincomum = "
            JOIN mat_vcomodatos
                ON mat_vcomodatos.id_pessoa = p.id
            
            JOIN comodatos_produtos AS cp
                ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id
        ".$leftjoin;
        return "
            SELECT * FROM (
                SELECT 
                    p.id AS id_pessoa, 
                    vatbreal.id AS id_atribuicao, 
                    produtos.id AS id_produto,
                    vatbreal.cod_produto AS cod,
                    IFNULL(produtos.referencia, '') AS ref,
                    vatbreal.lixeira,
                    1 AS grandeza

                FROM vatbreal 
                
                JOIN ".$tabela." AS p
                    ON p.id = vatbreal.id_pessoa 
                
                JOIN produtos
                    ON produtos.cod_externo = vatbreal.cod_produto 
                
                ".$joincomum."
                
                WHERE ".$where."

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao,
                        produtos.id AS id_produto,
                        IFNULL(produtos.cod_externo, '') AS cod, 
                        vatbreal.referencia AS ref,
                        vatbreal.lixeira,
                        2 AS grandeza

                    FROM vatbreal

                    JOIN ".$tabela." AS p
                        ON p.id = vatbreal.id_pessoa

                    JOIN produtos
                        ON produtos.referencia = vatbreal.referencia

                    ".$joincomum."
            
                    WHERE ".$where."
                )

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao, 
                        produtos.id AS id_produto,
                        vatbreal.cod_produto AS cod, 
                        IFNULL(produtos.referencia, '') AS ref,
                        vatbreal.lixeira,
                        3 AS grandeza

                    FROM vatbreal 

                    JOIN ".$tabela." AS p
                        ON p.id_setor = vatbreal.id_setor

                    JOIN produtos
                        ON produtos.cod_externo = vatbreal.cod_produto

                    ".$joincomum."
        
                    WHERE ".$where."
                )

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao, 
                        produtos.id AS id_produto,
                        IFNULL(produtos.cod_externo, '') AS cod, 
                        vatbreal.referencia AS ref,
                        vatbreal.lixeira,
                        4 AS grandeza

                    FROM vatbreal 

                    JOIN ".$tabela." AS p
                        ON p.id_setor = vatbreal.id_setor 

                    JOIN produtos
                        ON produtos.referencia = vatbreal.referencia

                    ".$joincomum."
        
                    WHERE ".$where."
                )

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao, 
                        produtos.id AS id_produto,
                        vatbreal.cod_produto AS cod, 
                        IFNULL(produtos.referencia, '') AS ref,
                        vatbreal.lixeira,
                        5 AS grandeza

                    FROM vatbreal 
                    
                    JOIN mat_vcomodatos
                        ON mat_vcomodatos.id_maquina = vatbreal.id_maquina

                    JOIN ".$tabela." AS p
                        ON p.id = mat_vcomodatos.id_pessoa 

                    JOIN produtos
                        ON produtos.cod_externo = vatbreal.cod_produto

                    JOIN comodatos_produtos AS cp
                        ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id

                    ".$leftjoin."
        
                    WHERE ".$where."
                )

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao, 
                        produtos.id AS id_produto,
                        IFNULL(produtos.cod_externo, '') AS cod, 
                        vatbreal.referencia AS ref,
                        vatbreal.lixeira,
                        6 AS grandeza
                        
                    FROM vatbreal 

                    JOIN mat_vcomodatos
                        ON mat_vcomodatos.id_maquina = vatbreal.id_maquina 

                    JOIN ".$tabela." AS p
                        ON p.id = mat_vcomodatos.id_pessoa 

                    JOIN produtos
                        ON produtos.referencia = vatbreal.referencia
                        
                    JOIN comodatos_produtos AS cp
                        ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id

                    ".$leftjoin."

                    WHERE ".$where."
                )
            ) AS tab

            GROUP BY
                id_pessoa,
                id_atribuicao,
                id_produto,
                cod,
                ref,
                lixeira,
                grandeza
        ";
    }

    private function campos_usuario($terceiro) {
        return "
            IFNULL(users1.name, '') AS usuario,
            IFNULL(users1.id, 0) AS usuario_id,
            IFNULL(users2.name, '') AS associado1_usuario,
            IFNULL(users2.id, 0) AS associado1_usuario_id,
            ".($terceiro ? "IFNULL(users3.name, '')" : "''")." AS associado2_usuario,
            ".($terceiro ? "IFNULL(users3.id, 0)" : "0")." AS associado2_usuario_id
        ";
    }

    private function join_users($tabela, $cont) {
        return "LEFT JOIN users AS users".$cont. " ON users".$cont.".id = ".$tabela.".id_usuario_editando";
    }

    private function from($tabela) {
        return "FROM ".$tabela." ".$this->join_users($tabela, 1);
    }

    private function join_n_para_um($coluna_nome, $tabela, $fk) {
        $alias = substr($tabela, 0, 3);
        return "LEFT JOIN (
            SELECT
                id,
                ".$coluna_nome.",
                id_usuario_editando

            FROM ".$tabela."

            WHERE id_usuario_editando <> 0
              AND lixeira = 0
        ) AS ".$alias." ON ".$alias."."."id = ".$fk;
    }

    private function join_um_para_n($coluna_nome, $tabela, $alias, $fk, $pk) {
        $colunas = $tabela.".id, ".$tabela.".".$coluna_nome.", ".$tabela.".".$fk.", ".$tabela.".id_usuario_editando";
        return "LEFT JOIN (
            SELECT ".$colunas."

            FROM ".$tabela."

            JOIN (
                SELECT
                    ".$fk.",
                    MIN(id_usuario_editando) AS id_usuario_editando

                FROM ".$tabela." AS ".substr($tabela, 0, 1)."

                WHERE id_usuario_editando <> 0
                  AND lixeira = 0

                GROUP BY ".$fk."
            ) AS lim ON lim.".$fk." = ".$tabela.".".$fk." AND lim.id_usuario_editando = ".$tabela.".id_usuario_editando

            GROUP BY ".$colunas."
        ) AS ".$alias." ON ".$alias.".".$fk." = ".$pk;
    }

    private function obter_mensagem($msg, $consulta) {
        $id_pessoa = intval($consulta["id_pessoa"]);
        if (!$id_pessoa) return $msg;        
        $titulo = $this->nomear($id_pessoa); 
        $msg = str_replace("a pessoa", ($consulta["pessoa_associado"] == "S" ? "o" : "e")." ".$titulo, $msg);
        $msg = str_replace("o pessoa", ($consulta["pessoa_associado"] == "S" ? "o" : "e")." ".$titulo, $msg);
        return $msg;
    }

    protected function pode_abrir_main($tabela, $id, $acao) {
        $resultado = new \stdClass;
        $resultado->permitir = 1;
        
        $query = "";
        switch($tabela) {
            case "categorias":
                $query = "
                    SELECT
                        categorias.descr AS titulo,
                        prod.descr AS associado1_titulo,
                        '' AS associado2_titulo,

                        'a' AS artigo,
                        'a categoria' AS tipo,

                        'o' AS associado1_artigo,
                        'produto' AS associado1_tipo,

                        '' AS associado2_artigo,
                        '' AS associado2_tipo,

                        ".$this->campos_usuario(false)."

                    ".$this->from($tabela)."

                    ".$this->join_um_para_n("descr", "produtos", "prod", "id_categoria", "categorias.id")."

                    ".$this->join_users("prod", 2);
                break;
            case "empresas":
                $query = "
                    SELECT
                        empresas.nome_fantasia AS titulo,
                        set.descr AS associado1_titulo,
                        pes.nome AS associado2_titulo,

                        'a' AS artigo,
                        'a empresa' AS tipo,

                        'o' AS associado1_artigo,
                        'centro de custo' AS associado1_tipo,

                        'a' AS associado2_artigo,
                        'pessoa' AS associado2_tipo,

                        pes.id AS id_pessoa,
                        'S' AS pessoa_associado,

                        ".$this->campos_usuario(true)."

                    ".$this->from($tabela)."

                    ".$this->join_um_para_n("descr", "setores", "set", "id_empresa", "empresas.id")."

                    ".$this->join_users("set", 2)."

                    ".$this->join_um_para_n("nome", "pessoas", "pes", "id_empresa", "empresas.id")."

                    ".$this->join_users("pes", 3);
                break;
            case "pessoas":
                $query = "
                    SELECT
                        pessoas.nome AS titulo,
                        emp.nome_fantasia AS associado1_titulo,
                        set.descr AS associado2_titulo,

                        'a' AS artigo,
                        'a pessoa' AS tipo,

                        'a' AS associado1_artigo,
                        'empresa' AS associado1_tipo,

                        'o' AS associado2_artigo,
                        'centro de custo' AS associado2_tipo,

                        pessoas.id AS id_pessoa,
                        'N' AS pessoa_associado,

                        ".$this->campos_usuario(true)."

                    ".$this->from($tabela)."

                    ".$this->join_n_para_um("nome_fantasia", "empresas", "pessoas.id_empresa")."

                    ".$this->join_users("emp", 2)."

                    ".$this->join_n_para_um("descr", "setores", "pessoas.id_setor")."

                    ".$this->join_users("set", 3);
                break;
            case "produtos":
                $query = "
                    SELECT
                        produtos.descr AS titulo,
                        cat.descr AS associado1_titulo,
                        '' AS associado2_titulo,

                        'o' AS artigo,
                        'e produto' AS tipo,

                        'a' AS associado1_artigo,
                        'categoria' AS associado1_tipo,

                        '' AS associado2_artigo,
                        '' AS associado2_tipo,

                        0 AS id_pessoa,
                        'N' AS pessoa_associado,

                        ".$this->campos_usuario(false)."

                    ".$this->from($tabela)."

                    ".$this->join_n_para_um("descr", "categorias", "produtos.id_categoria")."

                    ".$this->join_users("cat", 2);
                break;
            case "setores":
                $query = "
                    SELECT
                        setores.descr AS titulo,
                        emp.nome_fantasia AS associado1_titulo,
                        pes.nome AS associado2_titulo,

                        'o' AS artigo,
                        'e centro de custo' AS tipo,

                        'a' AS associado1_artigo,
                        'empresa' AS associado1_tipo,

                        'a' AS associado2_artigo,
                        'pessoa' AS associado2_tipo,

                        pes.id AS id_pessoa,
                        'S' AS pessoa_associado,

                        ".$this->campos_usuario(true)."

                    ".$this->from($tabela)."

                    ".$this->join_n_para_um("nome_fantasia", "empresas", "setores.id_empresa")."

                    ".$this->join_users("emp", 2)."

                    ".$this->join_um_para_n("nome", "pessoas", "pes", "id_setor", "setores.id")."

                    ".$this->join_users("pes", 3)."
                ";
                break;
        }
        if (!$query) return $resultado;
        $query .= " WHERE ".$tabela.".id = ".$id;
        $consulta = (array) DB::select(DB::raw(str_replace(" set ", " `set` ", $query)))[0];
        if (
            intval($consulta["usuario_id"]) &&
            intval($consulta["usuario_id"]) != Auth::user()->id
        ) {
            if (intval($consulta["id_pessoa"])) $consulta["artigo"] = "o";
            $resultado->permitir = 0;
            $resultado->aviso = $this->obter_mensagem("Não é possível ".$acao." <b>".mb_strtoupper($consulta["titulo"])."</b> porque ess".$consulta["tipo"]." está sendo editad".$consulta["artigo"]." por <b>".mb_strtoupper($consulta["usuario"])."</b>", $consulta);
            return $resultado;
        }
        $msg = "";
        for ($i = 1; $i <= 2; $i++) {
            $chave = "associado".$i;
            if (
                !$msg &&
                intval($consulta[$chave."_usuario_id"]) &&
                intval($consulta[$chave."_usuario_id"]) != Auth::user()->id
            ) {
                if (intval($consulta["id_pessoa"])) $consulta[$chave."_artigo"] = "o";
                $msg = "Não é possível ".$acao." <b>".mb_strtoupper($consulta["titulo"])."</b> porque ".$consulta[$chave."_artigo"]." ".$consulta[$chave."_tipo"]." <b>".mb_strtoupper($consulta[$chave."_titulo"])."</b>,";
                $msg .= "associado a ess".$consulta["tipo"].", está sendo editad".$consulta[$chave."_artigo"]." por <b>".mb_strtoupper($consulta[$chave."_usuario"])."</b>";
                $msg = $this->obter_mensagem($msg, $consulta);
            }
        }
        $resultado->aviso = $msg;
        $resultado->permitir = $msg ? 0 : 1;
        return $resultado;
    }
}