<?php

namespace App\Services;

use DB;
use Auth;
use App\Models\Pessoas;

class ConsultaService {
    public function srv_obter_empresa() {
        return intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
    }

    public function srv_log_consultar($tabela, $param = "") {
        if ($this->srv_obter_empresa()) return "";
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
                    ON mp.id = log.fk

                LEFT JOIN estoque
                    ON estoque.id = log.fk

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

        $query .= " AND log.origem IS NOT NULL ORDER BY log.data DESC, log.created_at DESC";

        $consulta = DB::select(DB::raw($query));
        return sizeof($consulta) ? "Última atualização feita por ".$consulta[0]->nome." em ".$consulta[0]->data : "Nenhuma atualização feita";
    }
}