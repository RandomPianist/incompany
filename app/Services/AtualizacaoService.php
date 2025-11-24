<?php

namespace App\Services;

use DB;

class AtualizacaoService {
    public function excluir_atribuicao_sem_retirada() {
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

    public function atualizar_mat_vcomodatos($id_maquina) {
        if (!intval($id_maquina)) return;
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
}