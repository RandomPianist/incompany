<?php

namespace App\Services;

use DB;
use App\Models\Pessoas;

class ConcorrenciaService {
    public function srv_nomear($id) {
        $pessoa = Pessoas::find($id);
        if (!intval($pessoa->id_empresa)) return "administrador";
        if (DB::table("users")->where("id_pessoa", $id)->exists()) return "usuário";
        if ($pessoa->supervisor) return "supervisor";
        if ($pessoa->visitante) return "visitante";
        return "funcionário";
    }

    public function campos_usuario($terceiro) {
        return "
            IFNULL(users1.name, '') AS usuario,
            IFNULL(users1.id, 0) AS usuario_id,
            IFNULL(users2.name, '') AS associado1_usuario,
            IFNULL(users2.id, 0) AS associado1_usuario_id,
            ".($terceiro ? "IFNULL(users3.name, '')" : "''")." AS associado2_usuario,
            ".($terceiro ? "IFNULL(users3.id, 0)" : "0")." AS associado2_usuario_id
        ";
    }

    public function join_users($tabela, $cont) {
        return "LEFT JOIN users AS users".$cont. " ON users".$cont.".id = ".$tabela.".id_usuario_editando";
    }

    public function from($tabela) {
        return "FROM ".$tabela." ".$this->join_users($tabela, 1);
    }

    public function join_n_para_um($coluna_nome, $tabela, $fk) {
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

    public function join_um_para_n($coluna_nome, $tabela, $alias, $fk, $pk) {
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

    public function obter_mensagem($msg, $consulta) {
        $id_pessoa = intval($consulta["id_pessoa"]);
        if (!$id_pessoa) return $msg;        
        $titulo = $this->srv_nomear($id_pessoa); 
        $msg = str_replace("a pessoa", ($consulta["pessoa_associado"] == "S" ? "o" : "e")." ".$titulo, $msg);
        $msg = str_replace("o pessoa", ($consulta["pessoa_associado"] == "S" ? "o" : "e")." ".$titulo, $msg);
        return $msg;
    }
}