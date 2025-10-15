<?php

namespace App\Http\Traits;

use DB;

trait RotinasTrait {
    public function atualizar_mat_vcomodatos($id_maquina) {
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

    public function atualizar_mat_vatbaux($chave, $valor, $apenas_ativos, $id_pessoa = 0) {
        $tabela = $apenas_ativos ? "vativos" : "pessoas";
        if ($chave == "M") {
            $query = "
                DELETE mat_vatbaux
                FROM mat_vatbaux
                JOIN mat_vcomodatos
                    ON mat_vcomodatos.id_pessoa = mat_vatbaux.id_pessoa
                WHERE mat_vcomodatos.id_maquina IN (".$valor.")
            ";
            if ($id_pessoa) $query .= " AND mat_vatbaux.id_pessoa = ".$id_pessoa;
            DB::statement($query);
        } else {
            DB::table("mat_vatbaux")
                    ->where(function($sql) use($chave, $valor, $id_pessoa) {
                        if (in_array($chave, ["P", "S"])) {
                            if ($id_pessoa) $sql->where("id_pessoa", $id_pessoa);
                            else $sql->whereRaw(($chave == "P" ? "id_pessoa" : "id_setor")." IN (".$valor.")");
                        }
                    })
                    ->delete();
        }
        $query = "
            SELECT
                tab.*,
                vatbreal.id_setor,
                vatbreal.lixeira

            FROM (
                SELECT 
                    p.id AS id_pessoa, 
                    vatbreal.id AS id_atribuicao, 
                    vatbreal.cod_produto AS cod, 
                    IFNULL(produtos.referencia, '') AS ref,
                    'PP' AS src 

                FROM vatbreal 
                
                JOIN ".$tabela." AS p
                    ON p.id = vatbreal.id_pessoa 
                
                JOIN produtos
                    ON produtos.cod_externo = vatbreal.cod_produto 
                
                JOIN mat_vcomodatos
                    ON mat_vcomodatos.id_pessoa = p.id
                
                JOIN comodatos_produtos AS cp
                    ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id
                
                WHERE cp.lixeira = 0

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao, 
                        IFNULL(produtos.cod_externo, '') AS cod, 
                        vatbreal.referencia AS ref,
                        'PR' AS src

                    FROM vatbreal

                    JOIN ".$tabela." AS p
                        ON p.id = vatbreal.id_pessoa

                    JOIN produtos
                        ON produtos.referencia = vatbreal.referencia

                    JOIN mat_vcomodatos
                        ON mat_vcomodatos.id_pessoa = p.id

                    JOIN comodatos_produtos AS cp
                        ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id

                    WHERE cp.lixeira = 0
                )

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao, 
                        vatbreal.cod_produto AS cod, 
                        IFNULL(produtos.referencia, '') AS ref,
                        'SP' AS src 

                    FROM vatbreal 

                    JOIN ".$tabela." AS p
                        ON p.id_setor = vatbreal.id_setor

                    JOIN produtos
                        ON produtos.cod_externo = vatbreal.cod_produto

                    JOIN mat_vcomodatos
                        ON mat_vcomodatos.id_pessoa = p.id

                    JOIN comodatos_produtos AS cp
                        ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id

                    WHERE cp.lixeira = 0
                )

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao, 
                        IFNULL(produtos.cod_externo, '') AS cod, 
                        vatbreal.referencia AS ref,
                        'SR' AS src 

                    FROM vatbreal 

                    JOIN ".$tabela." AS p
                        ON p.id_setor = vatbreal.id_setor 

                    JOIN produtos
                        ON produtos.referencia = vatbreal.referencia

                    JOIN mat_vcomodatos
                        ON mat_vcomodatos.id_pessoa = p.id

                    JOIN comodatos_produtos AS cp
                        ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id

                    WHERE cp.lixeira = 0
                )

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao, 
                        vatbreal.cod_produto AS cod, 
                        IFNULL(produtos.referencia, '') AS ref,
                        'MP' AS src

                    FROM vatbreal 
                    
                    JOIN mat_vcomodatos
                        ON mat_vcomodatos.id_maquina = vatbreal.id_maquina

                    JOIN ".$tabela." AS p
                        ON p.id = mat_vcomodatos.id_pessoa 

                    JOIN produtos
                        ON produtos.cod_externo = vatbreal.cod_produto

                    JOIN comodatos_produtos AS cp
                        ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id

                    WHERE cp.lixeira = 0
                )

                UNION ALL (
                    SELECT 
                        p.id AS id_pessoa, 
                        vatbreal.id AS id_atribuicao, 
                        IFNULL(produtos.cod_externo, '') AS cod, 
                        vatbreal.referencia AS ref,
                        'MR' AS src 
                    FROM vatbreal 

                    JOIN mat_vcomodatos
                        ON mat_vcomodatos.id_maquina = vatbreal.id_maquina 

                    JOIN ".$tabela." AS p
                        ON p.id = mat_vcomodatos.id_pessoa 

                    JOIN produtos
                        ON produtos.referencia = vatbreal.referencia
                        
                    JOIN comodatos_produtos AS cp
                        ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id

                    WHERE cp.lixeira = 0
                )
                
            ) AS tab

            JOIN produtos
                ON (produtos.cod_externo = tab.cod AND tab.src LIKE '%P')
                    OR (produtos.referencia = tab.ref AND tab.src LIKE '%R') 

            JOIN vatbreal
                ON vatbreal.id = tab.id_atribuicao
        ";
        if (!in_array($chave, ["M", "P"])) {
            $query .= " JOIN ".$tabela." AS p ON p.id = tab.id_pessoa WHERE ";
            if (!$id_pessoa) $query .= $chave == "S" ? "p.id_setor IN (".$valor.")" : "1";
        } else if ($chave == "M") {
            $query .= " JOIN mat_vcomodatos ON mat_vcomodatos.id_pessoa = tab.id_pessoa WHERE ";
            if (!$id_pessoa) $query .= "mat_vcomodatos.id_maquina IN (".$valor.")";
        } else {
            $query .= " WHERE ";
            if (!$id_pessoa) $query .= "tab.id_pessoa IN (".$valor.")";
        }
        if ($id_pessoa) $query .= "tab.id_pessoa = ".$id_pessoa;
        $query .= " AND produtos.lixeira = 0

            GROUP BY
                tab.id_pessoa,
                tab.id_atribuicao,
                tab.cod,
                tab.ref,
                tab.src,
                vatbreal.id_setor,
                vatbreal.lixeira
        ";
        DB::statement("INSERT INTO mat_vatbaux (".$query.")");
        DB::statement("
            DELETE mat_vatbaux
            FROM mat_vatbaux
            JOIN excecoes
                ON (excecoes.id_setor = mat_vatbaux.id_setor OR excecoes.id_pessoa = mat_vatbaux.id_pessoa)
                    AND mat_vatbaux.id_atribuicao = excecoes.id_atribuicao
            WHERE excecoes.lixeira = 0
              AND excecoes.rascunho = 'S'
        ");
        DB::statement("
            DELETE mat_vatbaux
            FROM mat_vatbaux
            JOIN users
                ON users.id_pessoa = mat_vatbaux.id_pessoa
            JOIN vatbreal
                ON vatbreal.id = mat_vatbaux.id_atribuicao
            WHERE vatbreal.gerado = 1
              AND users.admin = 1
        ");
    }
    
    public function atualizar_mat_vatribuicoes($chave, $valor, $apenas_ativos, $id_pessoa = 0) {
        $where = $id_pessoa ? "mat_vatribuicoes.id_pessoa = ".$id_pessoa : "";
        if ($chave == "S") {
            DB::statement("
                DELETE mat_vatribuicoes
                FROM mat_vatribuicoes
                JOIN pessoas
                    ON pessoas.id = mat_vatribuicoes.id_pessoa
                WHERE ".($where ? $where : "pessoas.id_setor IN (".$valor.")")
            );
        } elseif ($chave == "M") {
            DB::statement("
                DELETE mat_vatribuicoes
                FROM mat_vatribuicoes
                JOIN mat_vcomodatos
                    ON mat_vcomodatos.id_pessoa = mat_vatribuicoes.id_pessoa
                WHERE ".($where ? $where : "mat_vcomodatos.id_maquina IN (".$valor.")")
            );
        } else {
            DB::table("mat_vatribuicoes")
                ->where(function($sql) use($chave, $valor) {
                    if ($chave == "P") $sql->whereRaw("id_pessoa IN (".$valor.")");
                })
                ->delete();
        }

        $join = "";
        $where = $id_pessoa ? "x.id_pessoa = ".$id_pessoa : "1";
        switch($chave) {
            case "M":
                $join = " JOIN mat_vcomodatos ON mat_vcomodatos.id_pessoa = x.id_pessoa ";
                if ($where == "1") $where = "mat_vcomodatos.id_maquina IN (".$valor.")";
                break;
            case "S":
                $join = " JOIN ".($apenas_ativos ? "vativos" : "pessoas")." AS p ON p.id = x.id_pessoa ";
                if ($where == "1") $where = "p.id_setor IN (".$valor.")";
                break;
            case "P":
                if ($where == "1") $where = "x.id_pessoa IN (".$valor.")";
                break;
        }
        
        $base_select = "
            SELECT
                x.id_pessoa,
                x.id_atribuicao,
                filho.id_atribuicao AS id_associado
            
            FROM mat_vatbaux AS x 
            
            ".$join."
            
            LEFT JOIN mat_vatbaux AS filho
                ON ((x.ref = filho.ref AND x.ref <> '') OR x.cod = filho.cod)
                    AND x.id_pessoa = filho.id_pessoa
            
            WHERE x.lixeira = 0 AND
        ";

        $blocks = $base_select." x.src = 'PP' AND ".$where;
        $prev_notexists = " NOT EXISTS (
            SELECT 1
            FROM mat_vatbaux mv FORCE INDEX (idx_mat_vatbaux_pp)
            WHERE mv.id_pessoa = x.id_pessoa
              AND mv.cod = x.cod
              AND mv.lixeira = 0
              AND mv.src = 'PP'
        )";
        $blocks .= " UNION ALL ".$base_select.$prev_notexists." AND x.src = 'PR' AND ".$where;
        $prev_notexists .= " AND NOT EXISTS (
            SELECT 1
            FROM mat_vatbaux mv FORCE INDEX (idx_mat_vatbaux_pr)
            WHERE mv.id_pessoa = x.id_pessoa
              AND mv.ref = x.ref
              AND mv.lixeira = 0
              AND mv.src = 'PR'
        )";
        $blocks .= " UNION ALL ".$base_select.$prev_notexists." AND x.src = 'SP' AND ".$where;
        $prev_notexists .= " AND NOT EXISTS (
            SELECT 1
            FROM mat_vatbaux mv FORCE INDEX (idx_mat_vatbaux_sp)
            WHERE mv.id_setor = x.id_setor
              AND mv.cod = x.cod
              AND mv.lixeira = 0
              AND mv.src = 'SP'
        )";
        $blocks .= " UNION ALL ".$base_select.$prev_notexists." AND x.src = 'SR' AND ".$where;
        $prev_notexists .= "AND NOT EXISTS (
            SELECT 1
            FROM mat_vatbaux mv FORCE INDEX (idx_mat_vatbaux_sr)
            WHERE mv.id_setor = x.id_setor
              AND mv.ref = x.ref
              AND mv.lixeira = 0
              AND mv.src = 'SR'
        )";
        $blocks .= " UNION ALL ".$base_select.$prev_notexists." AND x.src = 'MP' AND ".$where;
        $prev_notexists .= " AND NOT EXISTS (
            SELECT 1
            FROM vatbreal a2
            JOIN mat_vcomodatos v
                ON v.id_maquina = a2.id_maquina
            JOIN produtos p2
                ON p2.cod_externo = a2.cod_produto
            WHERE v.id_pessoa = x.id_pessoa
              AND p2.cod_externo = x.cod
              AND a2.lixeira = 0
              AND p2.lixeira = 0
        )";
        $blocks .= " UNION ALL ".$base_select.$prev_notexists." AND x.src = 'MR' AND ".$where;
        DB::statement("
            INSERT INTO mat_vatribuicoes (
                SELECT DISTINCT * FROM (".$blocks.") AS tab
            )
        ");
    }

    public function atualizar_mat_vretiradas_vultretirada($chave, $valor, $tipo, $apenas_ativos) {
        $tabela_pessoas = $apenas_ativos ? "vativos" : "pessoas";
        $tabela = $tipo == "R" ? "mat_vretiradas" : "mat_vultretirada";
        $campos = "
            mat_vatribuicoes.id_pessoa,
            mat_vatribuicoes.id_atribuicao,
            p.id_setor
        ";
        $query = "
            SELECT
                ".$campos.",
                ".($tipo == "R" ? "IFNULL(SUM(retiradas.qtd), 0) AS valor" : "MAX(retiradas.data) AS data")."
            
            FROM vatbreal

            JOIN mat_vatribuicoes
                ON mat_vatribuicoes.id_atribuicao = vatbreal.id
            
            JOIN ".$tabela_pessoas." AS p
                ON p.id = mat_vatribuicoes.id_pessoa
        ";
        if ($chave == "M") {
            $query .= "
                JOIN mat_vcomodatos
                    ON mat_vcomodatos.id_pessoa = p.id
            ";
        }
        if ($tipo == "U") {
            $query .= "
                JOIN vatbreal AS associadas
                    ON associadas.id = mat_vatribuicoes.id_associado

                LEFT JOIN retiradas
                    ON retiradas.id_atribuicao = associadas.id
                        AND retiradas.id_pessoa = p.id
                        AND p.id_empresa IN (0, retiradas.id_empresa)
                        AND retiradas.id_supervisor IS NULL
            ";
        } else {
            $query .= "
                LEFT JOIN retiradas
                    ON retiradas.id_atribuicao = vatbreal.id
                        AND retiradas.id_pessoa = p.id
                        AND p.id_empresa IN (0, retiradas.id_empresa)
                        AND retiradas.data >= vatbreal.data
                        AND retiradas.data > DATE_SUB(CURDATE(), INTERVAL vatbreal.validade DAY)
                        AND retiradas.id_supervisor IS NULL
            ";
        }
        $query .= " GROUP BY ".$campos;
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
                        ON ".$tabela_pessoas." = ".$tabela.".id_pessoa
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

    public function excluir_atribuicao_sem_retirada() {
        DB::statement("
            DELETE a
            FROM atribuicoes a
            LEFT JOIN retiradas r ON r.id_atribuicao = a.id
            WHERE r.id IS NULL
              AND a.lixeira = 1
        ");
        DB::statement("
            DELETE l
            FROM log l
            LEFT JOIN atribuicoes a2 ON a2.id = l.fk
            WHERE a2.id IS NULL
              AND l.tabela = 'atribuicoes'
        ");
    }
}