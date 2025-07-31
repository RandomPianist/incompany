<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Log;
use App\Models\Pessoas;
use App\Models\Retiradas;

class ControllerKX extends Controller {
    protected function empresa_consultar(Request $request) {
        return (!sizeof(
            DB::table("empresas")
                ->where("id", $request->id_empresa)
                ->where("nome_fantasia", $request->empresa)
                ->where("lixeira", 0)
                ->get()
        ));
    }

    protected function log_inserir($acao, $tabela, $fk, $origem = "WEB", $nome = "") {
        $linha = new Log;
        $linha->acao = $acao;
        $linha->origem = $origem;
        $linha->tabela = $tabela;
        $linha->fk = $fk;
        if ($origem == "WEB") {
            $linha->id_pessoa = Auth::user()->id_pessoa;
            $linha->nome = Pessoas::find($linha->id_pessoa)->nome;
        } else if ($nome) $linha->nome = $linha->nome;
        $linha->data = date("Y-m-d");
        $linha->hms = date("H:i:s");
        $linha->save();
        return $linha;
    }

    protected function log_inserir_lote($acao, $origem, $tabela, $where, $nome = "") {
        $lista = DB::table($tabela)
                    ->whereRaw($where)
                    ->pluck("id")
                    ->toArray();
        foreach ($lista as $fk) $this->log_inserir($acao, $tabela, $fk, $origem, $nome);
    }

    protected function log_consultar($tabela, $param = "") {
        $query = "
            SELECT
                IFNULL(log.nome, log.origem) AS nome,
                CONCAT(DATE_FORMAT(log.data, '%d/%m/%Y'), CASE WHEN log.hms IS NOT NULL THEN CONCAT(' às ', log.hms) ELSE '' END) AS data

            FROM log

            LEFT JOIN pessoas
                ON pessoas.id = log.id_pessoa
        ";

        if ($tabela == "pessoas") {
            $param2 = str_replace("aux1", "aux2", $param);
            $param2 = str_replace("setores1", "setores2", $param2);
            $query .= "
                LEFT JOIN pessoas AS aux1
                    ON aux1.id = log.fk

                LEFT JOIN setores AS setores1
                    ON setores1.id = aux1.id_setor

                LEFT JOIN (
                    SELECT
                        id,
                        pessoa_ou_setor_valor
                    FROM atribuicoes
                    WHERE pessoa_ou_setor_chave = 'P'
                ) AS atb ON atb.id = log.fk

                LEFT JOIN pessoas AS aux2
                    ON aux2.id = atb.pessoa_ou_setor_valor

                LEFT JOIN setores AS setores2
                    ON setores2.id = aux2.id_setor

                LEFT JOIN retiradas
                    ON retiradas.id_atribuicao = atb.id AND retiradas.id_comodato = 0

                WHERE ((log.tabela = 'pessoas' AND ".$param.")
                    OR (".$param2." AND (log.tabela = 'atribuicoes' OR (log.tabela = 'retiradas' AND retiradas.id IS NOT NULL))))
            ";
        } else if ($tabela == "valores") {
            $query .= "
                LEFT JOIN (
                    SELECT id
                    FROM valores
                    WHERE alias = '".$param."'
                ) AS main ON main.id = log.fk

                LEFT JOIN maquinas_produtos AS mp
                    ON mp.id_maquina = main.id

                LEFT JOIN estoque
                    ON estoque.id_mp = mp.id

                WHERE ((log.tabela = 'valores' AND main.id IS NOT NULL)
                   OR (log.tabela = 'maquinas_produtos' AND mp.id IS NOT NULL)
                   OR (log.tabela = 'estoque' AND estoque.id IS NOT NULL))
            ";
        } else if ($tabela == "setores") {
            $query .= "
                LEFT JOIN (
                    SELECT id
                    FROM atribuicoes
                    WHERE pessoa_ou_setor_chave = 'S'
                ) AS atb ON atb.id = log.fk

                WHERE (log.tabela = 'setores'
                  OR (log.tabela = 'atribuicoes' AND atb.id IS NOT NULL))
            ";
        } else $query .= " WHERE log.tabela = '".$tabela."'";

        $query .= " AND log.origem IS NOT NULL ORDER BY log.data DESC";

        $consulta = DB::select(DB::raw($query));
        return !intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa) ? sizeof($consulta) ? "Última atualização feita por ".$consulta[0]->nome." em ".$consulta[0]->data : "Nenhuma atualização feita" : "";
    }

    protected function retirada_consultar($id_atribuicao, $qtd, $id_pessoa) {
        $consulta = DB::table("vpendentes")
                        ->where("esta_pendente", 1)
                        ->where("id_atribuicao", $id_atribuicao)
                        ->where("id_pessoa", $id_pessoa)
                        ->value("qtd");
        if ($consulta === null) return 0;
        return floatval($consulta) > floatval($qtd) ? 0 : 1;
    }

    protected function retirada_salvar($json) {
        $linha = new Retiradas;
        if (isset($json["obs"])) $linha->obs = $json["obs"];
        if (isset($json["biometria_ou_senha"])) $linha->biometria_ou_senha = $json["biometria_ou_senha"];
        if (isset($json["id_supervisor"])) {
            if (intval($json["id_supervisor"])) $linha->id_supervisor = $json["id_supervisor"];
        }
        $linha->id_pessoa = $json["id_pessoa"];
        $linha->id_atribuicao = $json["id_atribuicao"];
        $linha->id_produto = $json["id_produto"];
        $linha->id_comodato = $json["id_comodato"];
        $linha->qtd = $json["qtd"];
        $linha->data = $json["data"];
        $linha->id_empresa = Pessoas::find($json["id_pessoa"])->id_empresa;
        $linha->save();
        $api = $json["id_comodato"] > 0;
        $reg_log = $this->log_inserir("C", "retiradas", $linha->id, $api ? "APP" : "WEB");
        if ($api) {
            $reg_log->id_pessoa = $json["id_pessoa"];
            $reg_log->nome = Pessoas::find($json["id_pessoa"])->nome;
            $reg_log->save();
        }
        return $linha;
    }

    protected function supervisor_consultar(Request $request) {
        $consulta = DB::table("pessoas")
                        ->where("cpf", $request->cpf)
                        ->where("senha", $request->senha)
                        ->where("supervisor", 1)
                        ->where("lixeira", 0)
                        ->get();
        return sizeof($consulta) ? $consulta[0]->id : 0;
    }

    protected function setor_mostrar($id) {
        if (intval($id)) {
            return DB::table("setores")
                        ->leftjoin("empresas", "empresas.id", "setores.id_empresa")
                        ->select(
                            "setores.descr",
                            "setores.cria_usuario",
                            "setores.id_empresa",
                            "empresas.nome_fantasia AS empresa"
                        )
                        ->where("setores.id", $id)
                        ->first();
        }
        $resultado = new \stdClass;
        $resultado->cria_usuario = 0;
        return $resultado;
    }

    protected function criar_mp($id_produto, $id_maquina, $api = false, $nome = "") {
        $id_produto = strval($id_produto);
        $id_maquina = strval($id_maquina);
        $tabela = strpos(".", $id_maquina) !== false ? "valores" : "produtos";
        DB::statement("
            INSERT INTO maquinas_produtos (id_produto, id_maquina) (
                SELECT
                    ".$id_produto.",
                    ".$id_maquina."

                FROM ".$tabela."

                LEFT JOIN maquinas_produtos AS mp
                    ON mp.id_produto = ".$id_produto." AND mp.id_maquina = ".$id_maquina."
                
                WHERE mp.id IS NULL ".($tabela == "valores" ? " AND valores.alias = 'maquinas'" : "")."
            )
        ");
        $id_pessoa = $api ? "NULL" : Auth::user()->id_pessoa;
        if (!$api) $nome = Pessoas::find($id_pessoa)->nome;
        DB::statement("
            INSERT INTO log (id_pessoa, nome, origem, acao, tabela, fk, data) (
                SELECT
                    ".$id_pessoa.",
                    ".($nome ? "'".$nome."'" : "NULL").",
                    '".($api ? "ERP" : "WEB")."',
                    'C',
                    'maquinas_produtos',
                    mp.id,
                    CURDATE()

                FROM maquinas_produtos AS mp

                LEFT JOIN log
                    ON log.tabela = 'maquinas_produtos' AND log.fk = mp.id

                WHERE log.id IS NULL
            )
        ");
    }

    protected function atribuicao_atualiza_ref($id, $antigo, $novo, $nome = "", $api = false) {
        if ($id) {
            $novo = trim($novo);
            $where = "produto_ou_referencia_valor = '".$antigo."' AND produto_ou_referencia_chave = 'R'";
            DB::statement("
                UPDATE atribuicoes
                SET ".($novo ? "produto_ou_referencia_valor = '".$novo."'" : "lixeira = 1")."
                WHERE ".$where
            );
            $this->log_inserir_lote($novo ? "E" : "D", $api ? "ERP" : "WEB", "atribuicoes", $where, $nome);
        }
    }

    protected function obter_where($id_pessoa, $coluna = "pessoas.id_empresa") {
        $id_emp = Pessoas::find($id_pessoa)->id_empresa;
        $where = $coluna == "pessoas.id_empresa" ? "pessoas.lixeira = 0" : "1";
        if (intval($id_emp)) {
            $where .= " AND ".$coluna." IN (
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

    protected function retorna_saldo_mp($id_maquina, $id_produto) {
        return floatval(
            DB::table("maquinas_produtos AS mp")
                ->selectRaw("IFNULL(vestoque.qtd, 0) AS saldo")
                ->leftjoin("vestoque", "vestoque.id_mp", "mp.id")
                ->where("mp.id_maquina", $id_maquina)
                ->where("mp.id_produto", $id_produto)
                ->first()
                ->saldo
        );
    }
}