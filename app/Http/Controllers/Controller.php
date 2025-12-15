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
use App\Services\ConcorrenciaService;
use App\Services\AtualizacaoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController {
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, GlobaisTrait;

    // =====================================================================
    // BLOCO 1: HELPERS E UTILITÁRIOS GERAIS
    // (Funções genéricas de comparação, validação e visualização)
    // =====================================================================

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

    // =====================================================================
	// BLOCO 2: CONTROLE DE CONCORRÊNCIA
	// (Funções para verificar e bloquear registros em edição)
	// =====================================================================

    protected function alterar_usuario_editando($tabela, $id, $remover = false) {
        $consulta = DB::table($tabela)->where("id", $id);
        if ($remover || !intval($consulta->first()->id_usuario_editando)) $consulta->update(["id_usuario_editando" => $remover ? 0 : Auth::user()->id]);
        return $consulta->first();
    }

    protected function nomear($id) {
        $servico = new ConcorrenciaService;
        return $servico->srv_nomear($id); // App\Services\ConcorrenciaService.php
    }

    protected function pode_abrir_main($tabela, $id, $acao) {
        $servico = new ConcorrenciaService;

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

                        ".$servico->campos_usuario(false)."

                    ".$servico->from($tabela)."

                    ".$servico->join_um_para_n("descr", "produtos", "prod", "id_categoria", "categorias.id")."

                    ".$servico->join_users("prod", 2);
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

                        ".$servico->campos_usuario(true)."

                    ".$servico->from($tabela)."

                    ".$servico->join_um_para_n("descr", "setores", "set", "id_empresa", "empresas.id")."

                    ".$servico->join_users("set", 2)."

                    ".$servico->join_um_para_n("nome", "pessoas", "pes", "id_empresa", "empresas.id")."

                    ".$servico->join_users("pes", 3);
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

                        ".$servico->campos_usuario(true)."

                    ".$servico->from($tabela)."

                    ".$servico->join_n_para_um("nome_fantasia", "empresas", "pessoas.id_empresa")."

                    ".$servico->join_users("emp", 2)."

                    ".$servico->join_n_para_um("descr", "setores", "pessoas.id_setor")."

                    ".$servico->join_users("set", 3);
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

                        ".$servico->campos_usuario(false)."

                    ".$servico->from($tabela)."

                    ".$servico->join_n_para_um("descr", "categorias", "produtos.id_categoria")."

                    ".$servico->join_users("cat", 2);
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

                        ".$servico->campos_usuario(true)."

                    ".$servico->from($tabela)."

                    ".$servico->join_n_para_um("nome_fantasia", "empresas", "setores.id_empresa")."

                    ".$servico->join_users("emp", 2)."

                    ".$servico->join_um_para_n("nome", "pessoas", "pes", "id_setor", "setores.id")."

                    ".$servico->join_users("pes", 3)."
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
            $resultado->aviso = $servico->obter_mensagem("Não é possível ".$acao." <b>".mb_strtoupper($consulta["titulo"])."</b> porque ess".$consulta["tipo"]." está sendo editad".$consulta["artigo"]." por <b>".mb_strtoupper($consulta["usuario"])."</b>", $consulta); // App\Services\ConcorrenciaService.php
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
                $msg = $servico->obter_mensagem($msg, $consulta); // App\Services\ConcorrenciaService.php
            }
        }
        $resultado->aviso = $msg;
        $resultado->permitir = $msg ? 0 : 1;
        return $resultado;
    }

    // =====================================================================
	// BLOCO 3: LOGS
	// (Funções para inserir e consultar registros de log)
	// =====================================================================

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

    // =====================================================================
	// BLOCO 4: LÓGICA DE NEGÓCIO - EMPRESAS E PERMISSÕES
	// (Funções que definem o que o usuário pode ver e fazer)
	// =====================================================================

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
        return "(".$where.")";
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
                        $sql->where(
                            ($tipo == "F" && !intval($empresa_usuario->id_matriz)) ? "id_matriz" : "id",
                            ($tipo == "M" && intval($empresa_usuario->id_matriz)) ? $empresa_usuario->id_matriz : $empresa_usuario->id
                        );
                    } else $sql->where("id_matriz", $tipo == "M" ? "=" : ">", 0);
                }
            })
            ->where("lixeira", 0)
            ->orderBy("nome_fantasia")
            ->get();
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
        return ["financeiro", "atribuicoes", "retiradas", "pessoas", "usuarios", "solicitacoes"];
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

    // =====================================================================
	// BLOCO 5: LÓGICA DE NEGÓCIO - COMODATOS E MÁQUINAS
	// (Funções para gerenciar máquinas, comodatos e os produtos neles)
	// =====================================================================

    protected function maquinas_da_pessoa($id_pessoa) {
        $consulta = DB::table("vativos")
                        ->where("id", $id_pessoa)
                        ->value("maquinas");
        if ($consulta === null) return [];
        return explode(",", $consulta);
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
    
    protected function consultar_comodatos_produtos($contexto, $id_principal, $ids, $descricoes, $precos, $maximos) {
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

    protected function salvar_comodatos_produtos($contexto, Request $request) {
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

            if (!empty($maquinas_para_atualizar)) $this->atualizar_tudo(array_unique($maquinas_para_atualizar));
            $connection->commit();
            return "/".($contexto == "maquina" ? "maquinas" : "produtos");
        } catch (\Exception $e) {    
            $connection->rollBack();
            return $e->getMessage();
        }
    }

    // =====================================================================
	// BLOCO 6: LÓGICA DE NEGÓCIO - ATRIBUIÇÕES
	// (Funções para gerar, atualizar e gerenciar atribuições de produtos)
	// =====================================================================
    
    protected function gerar_atribuicoes(Comodatos $comodato) {
        $servico = new AtualizacaoService;
        $ret = false;
        $where = "lixeira = 0 AND id_maquina = ".$comodato->id_maquina." AND id_empresa = ".$comodato->id_empresa;
        $where_g = $where." AND gerado = 1";
        if (!$comodato->atb_todos) {
            $ret = Atribuicoes::whereRaw($where_g)->exists();
            if ($ret) {
                $this->log_inserir_lote("D", "atribuicoes", $where_g);
                Atribuicoes::whereRaw($where_g)->update(["lixeira" => 1]);
                $servico->excluir_atribuicao_sem_retirada(); // App\Services\AtualizacaoService.php
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
                if (intval($atb->gerado)) $modelo = $atb;
                $continua = false;
            }
            if ($continua) {
                $atb = Atribuicoes::whereRaw($where)->where("cod_produto", $item->cod_externo)->first();
                if ($atb !== null) {
                    if (intval($atb->gerado)) $modelo = $atb;
                    $continua = false;
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
            $connection = DB::connection();
            $connection->statement('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;');
            $connection->beginTransaction();
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
                $this->atualizar_atribuicoes();
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

    // =====================================================================
	// BLOCO 7: LÓGICA DE NEGÓCIO - RETIRADAS
	// (Funções e helpers SQL para consultar e salvar retiradas)
	// =====================================================================

    protected function retorna_join_prev($id_pessoa) {
        return "
            LEFT JOIN (
                SELECT
                    id_produto,
                    id_pessoa,
                    COUNT(id) AS qtd
                    
                FROM pre_retiradas
                
                GROUP BY
                    id_produto,
                    id_pessoa
            ) AS prev ON prev.id_produto = produtos.id AND prev.id_pessoa = ".$id_pessoa;
    }
    
    protected function retorna_calc_qtd() {
        return "
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
        ";
    }

    protected function calculo_atraso() {
        return "DATE_ADD(IFNULL(mat_vultretirada.data, IFNULL(pessoas.admissao, DATE(pessoas.created_at))), INTERVAL vatbold.validade DAY)";
    }

    protected function retorna_case_qtd() {
        return "
            CASE
                WHEN (".$this->calculo_atraso()." <= CURDATE()) THEN ".$this->retorna_calc_qtd()."
                ELSE 0
            END
        ";
    }

    protected function retirada_consultar($id_atribuicao, $qtd, $id_pessoa) {
        $consulta = DB::select(DB::raw("
            SELECT ".$this->retorna_calc_qtd()." AS qtd
                
            FROM vatbold

            JOIN produtos
                ON produtos.cod_externo = vatbold.cod_produto OR produtos.referencia = vatbold.referencia

            JOIN pessoas
                ON pessoas.id = ".$id_pessoa."
                
            LEFT JOIN mat_vretiradas
                ON mat_vretiradas.id_atribuicao = vatbold.id AND mat_vretiradas.id_pessoa = ".$id_pessoa."

            LEFT JOIN mat_vultretirada
                ON mat_vultretirada.id_atribuicao = vatbold.id AND mat_vultretirada.id_pessoa = ".$id_pessoa."

            ".$this->retorna_join_prev($id_pessoa)."

            JOIN vprodutosgeral AS vprodutos
                ON vprodutos.id_pessoa = ".$id_pessoa." AND vprodutos.id_produto = produtos.id
                
            WHERE vatbold.id = ".$id_atribuicao."
              AND (".$this->calculo_atraso()." <= CURDATE())
              AND ((vatbold.qtd - (IFNULL(mat_vretiradas.valor, 0) + IFNULL(prev.qtd, 0))) > 0)
        "));
        if (!sizeof($consulta)) return 0;
        return floatval($consulta[0]->qtd) > floatval($qtd) ? 0 : 1;
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

        return $linha;
    }  

    protected function retorna_sql_pendentes($id_pessoa) {
        return "
            vatbold

            JOIN (".$this->retorna_sql_atb_vigente(
                $id_pessoa ? $this->retorna_atb_aux("P", $id_pessoa, false, $id_pessoa) : $this->retorna_atb_aux("T", "0", false, 0)
            ).") AS atb ON atb.id_atribuicao = vatbold.id
            
            JOIN produtos
                ON produtos.cod_externo = vatbold.cod_produto OR produtos.referencia = vatbold.referencia

            ".$this->retorna_join_prev("atb.id_pessoa")."

            JOIN vprodutosgeral AS vprodutos
                ON vprodutos.id_pessoa = atb.id_pessoa AND vprodutos.id_produto = produtos.id

            LEFT JOIN mat_vretiradas
                ON mat_vretiradas.id_atribuicao = vatbold.id AND mat_vretiradas.id_pessoa = atb.id_pessoa

            LEFT JOIN mat_vultretirada
                ON mat_vultretirada.id_atribuicao = vatbold.id AND mat_vultretirada.id_pessoa = atb.id_pessoa
        ";
    }

    // =====================================================================
	// BLOCO 8: LÓGICA DE NEGÓCIO - RELATÓRIOS (EXTRATO / SUGESTÃO)
	// (Funções complexas de consulta e processamento para gerar relatórios)
	// =====================================================================

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
                ->where("cp.lixeira", 0)
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

    // =====================================================================
	// BLOCO 9: ATUALIZAÇÃO DE VIEWS MATERIALIZADAS
	// (Lógica complexa para atualizar tabelas de dados pré-calculados)
	// =====================================================================

    protected function atualizar_tudo($maquinas) {
        $servico = new AtualizacaoService;
        foreach ($maquinas as $maquina) $servico->atualizar_mat_vcomodatos($maquina);
        $servico->excluir_atribuicao_sem_retirada();
    }

    protected function atualizar_atribuicoes() {
        $servico = new AtualizacaoService;
        $servico->excluir_atribuicao_sem_retirada(); // App\Services\AtualizacaoService.php
    }

    protected function retorna_sql_atb_vigente($base) {
        return "
            SELECT
                atb_bruto.id_pessoa,
                atb_bruto.id_atribuicao,
                atb_bruto.id_produto

            FROM (".$base.") AS atb_bruto

            JOIN (
                SELECT
                    id_pessoa,
                    id_produto,
                    MIN(grandeza) AS grandeza

                FROM (".$base.") AS sub_atb

                WHERE lixeira = 0
                
                GROUP BY
                    id_pessoa,
                    id_produto
            ) AS prioridades
                ON prioridades.id_pessoa = atb_bruto.id_pessoa
                    AND prioridades.id_produto = atb_bruto.id_produto
                    AND prioridades.grandeza = atb_bruto.grandeza

            GROUP BY
                atb_bruto.id_pessoa,
                atb_bruto.id_atribuicao,
                atb_bruto.id_produto
        ";
    }

    protected function retorna_atb_aux($chave, $valor, $apenas_ativos, $id_pessoa) {
        $tabela = $apenas_ativos ? "vativos" : "pessoas";
        $where = "
                excecoes.id IS NULL
            AND cp.lixeira = 0
            AND produtos.lixeira = 0
        ";
        if ($id_pessoa) $where .= " AND p.id = ".$id_pessoa;
        else if ($chave == "P") $where .= " AND p.id IN (".$valor.")";
        else if ($chave == "S") $where .= " AND p.id_setor IN (".$valor.")";
        else if ($chave == "M") $where .= " AND mat_vcomodatos.id_maquina IN (".$valor.")";
        else $where .= " AND ".$this->obter_where(Auth::user()->id_pessoa, "p");
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

    protected function atualizar_mat_vretiradas_vultretirada($chave, $valor, $tipo, $apenas_ativos) {
        $tabela_pessoas = $apenas_ativos ? "vativos" : "pessoas";
        $tabela = $tipo == "R" ? "mat_vretiradas" : "mat_vultretirada";
        $base = $this->retorna_atb_aux($chave, $valor, $apenas_ativos, 0);
        $vigentes = $this->retorna_sql_atb_vigente($base);
        $campos = "
            atb.id_pessoa,
            atb.id_atribuicao,
            p.id_setor
        ";
        $query = "SELECT ".$campos.(
            $tipo == "U" ? ", MAX(retiradas.data) AS data FROM vatbreal

                JOIN (
                    SELECT
                        main_atb.id_pessoa,
                        main_atb.id_atribuicao,
                        associada.id_atribuicao AS id_associado

                    FROM (".$vigentes.") AS main_atb

                    JOIN (".$base.") AS associada
                        ON associada.id_pessoa = main_atb.id_pessoa AND associada.id_produto = main_atb.id_produto

                    GROUP BY
                        main_atb.id_pessoa,
                        main_atb.id_atribuicao,
                        associada.id_atribuicao
                ) AS atb ON atb.id_atribuicao = vatbreal.id

                JOIN vatbreal AS associadas
                    ON associadas.id = atb.id_associado

                JOIN ".$tabela_pessoas." AS p
                    ON p.id = atb.id_pessoa

                JOIN retiradas
                    ON retiradas.id_atribuicao = associadas.id
                        AND retiradas.id_pessoa = p.id
                        AND p.id_empresa IN (0, retiradas.id_empresa)
                        AND retiradas.id_supervisor IS NULL            
            " : ", IFNULL(SUM(retiradas.qtd), 0) AS valor FROM vatbreal

                JOIN (".$vigentes.") AS atb
                    ON atb.id_atribuicao = vatbreal.id

                JOIN ".$tabela_pessoas." AS p
                    ON p.id = atb.id_pessoa

                JOIN retiradas
                    ON retiradas.id_atribuicao = vatbreal.id
                        AND retiradas.id_pessoa = p.id
                        AND p.id_empresa IN (0, retiradas.id_empresa)
                        AND retiradas.data >= vatbreal.data
                        AND retiradas.data > DATE_SUB(CURDATE(), INTERVAL vatbreal.validade DAY)
                        AND retiradas.id_supervisor IS NULL            
            "
        )."GROUP BY ".$campos;
        switch($chave) {
            case "M":
                DB::statement("
                    DELETE ".$tabela."
                    FROM ".$tabela."
                    JOIN mat_vcomodatos
                        ON mat_vcomodatos.id_pessoa = ".$tabela.".id_pessoa
                    WHERE mat_vcomodatos.id_maquina IN (".$valor.")
                ");
                break;
            case "S":
                DB::statement("
                    DELETE ".$tabela."
                    FROM ".$tabela."
                    JOIN ".$tabela_pessoas."
                        ON ".$tabela_pessoas.".id = ".$tabela.".id_pessoa
                    WHERE ".$tabela_pessoas.".id_setor IN (".$valor.")
                ");
                break;
            default:
                DB::table($tabela)
                    ->where(function($sql) use($chave, $valor) {
                        if ($chave == "P") $sql->whereRaw("id_pessoa IN (".$valor.")");
                    })
                    ->delete();
        }
        DB::statement("INSERT INTO ".$tabela."(".$query.")");
    }
}