<?php

namespace App\Services;

use DB;
use App\Models\Pessoas;

class ConcorrenciaService {
    private function campos_usuario($terceiro) {
        return "
            IFNULL(users1.name, '') AS usuario,
            IFNULL(users2.name, '') AS associado1_usuario,
            ".($terceiro ? "IFNULL(users3.name, '')" : "''")." AS associado2_usuario
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
        $colunas = $tabela.".".$coluna_nome.", ".$tabela.".".$fk.", ".$tabela.".id_usuario_editando";
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
        $pessoa = Pessoas::find($id_pessoa);
        $titulo = "";
        if (!intval($pessoa->id_empresa)) $titulo = "administrador";
        else if (DB::table("users")->where("id_pessoa", $id_pessoa)->exists()) $titulo = "usuário";
        else if ($pessoa->supervisor) $titulo = "supervisor";
        else $titulo = "funcionário";
        $msg = str_replace("a pessoa", ($consulta["pessoa_associado"] == "S" ? "e" : "o")." ".$titulo, $msg);
        return $msg;
    }

    public function srv_pode_abrir($tabela, $id, $acao) {
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
        $consulta = (array) DB::select(DB::raw($query))[0];
        if ($consulta["usuario"]) {
            $resultado->permitir = 0;
            $resultado->aviso = $this->obter_mensagem("Não é possível ".$acao." <b>".mb_strtoupper($consulta["titulo"])."</b> porque ess".$consulta["tipo"]." está sendo editad".$consulta["artigo"]." por <b>".mb_strtoupper($consulta["usuario"])."</b>", $consulta);
            return $resultado;
        }
        $msg = "";
        for ($i = 1; $i <= 2; $i++) {
            $chave = "associado".$i;
            if (!$msg && $consulta[$chave."_usuario"]) {
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