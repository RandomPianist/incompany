SELECT
    produtos.id,
    atribuicoes.validade,
    atbgrp.validade AS validadegrp,
    CASE
        WHEN atribuicoes.qtd < SUM(estq.quantidade) THEN ROUND(atribuicoes.qtd)
        ELSE ROUND(SUM(estq.quantidade))
    END AS qtd,
    CASE
        WHEN SUM(atbgrp.qtd) < SUM(estq.quantidade) THEN ROUND(SUM(atbgrp.qtd))
        ELSE ROUND(SUM(estq.quantidade))
    END AS qtdgrp,
    CASE
        WHEN atribuicoes.produto_ou_referencia_chave = 'P' THEN produtos.descr
        ELSE produtos.referencia
    END AS produto

FROM atribuicoes

JOIN produtos
    ON (produtos.cod_externo = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'P')
        OR (produtos.referencia = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'R')

JOIN pessoas
    ON (pessoas.id = atribuicoes.pessoa_ou_setor_valor AND atribuicoes.pessoa_ou_setor_chave = 'P')
        OR (pessoas.id_setor = atribuicoes.pessoa_ou_setor_valor AND atribuicoes.pessoa_ou_setor_chave = 'S')

JOIN (
    SELECT id_atribuicao FROM vatribuicoes WHERE id_pessoa = 178
) AS lim ON lim.id_atribuicao = atribuicoes.id

JOIN (
    SELECT
        minhas_empresas.id_pessoa,
        comodatos.id_maquina

    FROM comodatos

    JOIN (
        SELECT
            id AS id_pessoa,
            id_empresa
        
        FROM pessoas

        UNION ALL (
            SELECT
                pessoas.id AS id_pessoa,
                filiais.id AS id_empresa

            FROM pessoas

            JOIN empresas AS filiais
                ON filiais.id_matriz = pessoas.id_empresa
        )
    ) AS minhas_empresas ON minhas_empresas.id_empresa = comodatos.id_empresa

    WHERE ((DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01')) BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim))
) AS minhas_maquinas ON minhas_maquinas.id_pessoa = pessoas.id

JOIN (
    SELECT
        mp.id_produto,
        mp.id_maquina,
        IFNULL(SUM(
            CASE
                WHEN estoque.es = 'E' THEN estoque.qtd
                ELSE estoque.qtd * -1
            END
        ), 0) AS quantidade

    FROM maquinas_produtos AS mp

    LEFT JOIN estoque
        ON estoque.id_mp = mp.id

    GROUP BY
        id_produto,
        id_maquina
) AS estq ON estq.id_maquina = minhas_maquinas.id_maquina AND estq.id_produto = produtos.id

LEFT JOIN (
    SELECT
        id_atribuicao,
        id_pessoa,
        MAX(data) AS data

    FROM retiradas

    WHERE id_supervisor IS NULL

    GROUP BY
        id_atribuicao,
        id_pessoa
) AS ret ON ret.id_atribuicao = atribuicoes.id AND ret.id_pessoa = pessoas.id

LEFT JOIN (
    SELECT
        tab.id_atribuicao,
        SUM(atribuicoes.qtd) AS qtd,
        MIN(atribuicoes.validade) AS validade,
        MAX(ret.data) AS data
    
    FROM (
        SELECT * FROM vatribuicoes WHERE id_pessoa = 178
    ) AS tab

    JOIN atribuicoes
        ON REPLACE(tab.associados, CONCAT('|', atribuicoes.id, '|'), '') <> tab.associados

    JOIN produtos
        ON (produtos.cod_externo = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'P')
            OR (produtos.referencia = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'R')

    JOIN pessoas
        ON (pessoas.id = atribuicoes.pessoa_ou_setor_valor AND atribuicoes.pessoa_ou_setor_chave = 'P')
            OR (pessoas.id_setor = atribuicoes.pessoa_ou_setor_valor AND atribuicoes.pessoa_ou_setor_chave = 'S')

    LEFT JOIN (
        SELECT
            retiradas.id_atribuicao,
            retiradas.id_pessoa,
            MAX(retiradas.data) AS data

        FROM retiradas

        JOIN atribuicoes
            ON atribuicoes.id = retiradas.id_atribuicao

        WHERE retiradas.id_supervisor IS NULL

        GROUP BY
            retiradas.id_atribuicao,
            retiradas.id_pessoa
    ) AS ret ON ret.id_atribuicao = atribuicoes.id AND ret.id_pessoa = pessoas.id

    WHERE produtos.lixeira = 0

    GROUP BY tab.id_atribuicao
) AS atbgrp ON atbgrp.id_atribuicao = atribuicoes.id

WHERE (
    ret.data IS NULL OR DATE_ADD(ret.data, INTERVAL atribuicoes.validade DAY) < CURDATE() OR
    atbgrp.data IS NULL OR DATE_ADD(atbgrp.data, INTERVAL atbgrp.validade DAY) < CURDATE()
) AND atribuicoes.obrigatorio = 1
  AND produtos.lixeira = 0
  AND pessoas.id = 178

GROUP BY
    produtos.id,
    atribuicoes.validade,
    atbgrp.validade,
    atribuicoes.qtd,
    CASE
        WHEN atribuicoes.produto_ou_referencia_chave = 'P' THEN produtos.descr
        ELSE produtos.referencia
    END

HAVING SUM(estq.quantidade) > 0

ORDER BY CASE
    WHEN atribuicoes.qtd < SUM(estq.quantidade) THEN ROUND(atribuicoes.qtd)
    ELSE ROUND(SUM(estq.quantidade))
END DESC;

SELECT
    pessoas.id,
    pessoas.nome,
    pessoas.foto,
    SUM(atribuicoes.qtd - IFNULL(ret.qtd, 0)) AS qtd,
    SUM(atbgrp.qtd) AS qtdgrp

FROM atribuicoes

JOIN produtos
    ON (produtos.cod_externo = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'P')
        OR (produtos.referencia = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'R')

JOIN pessoas
    ON (pessoas.id = atribuicoes.pessoa_ou_setor_valor AND atribuicoes.pessoa_ou_setor_chave = 'P')
        OR (pessoas.id_setor = atribuicoes.pessoa_ou_setor_valor AND atribuicoes.pessoa_ou_setor_chave = 'S')

JOIN (
    SELECT
        id_atribuicao,
        id_pessoa,
        associados
    FROM atribuicoes_associadas AS aa
    JOIN pessoas
        ON pessoas.id = aa.id_pessoa
    WHERE pessoas.lixeira = 0
        AND pessoas.id_empresa IN (
            SELECT id
            FROM empresas
            WHERE empresas.id = 11
            UNION ALL (
                SELECT filiais.id
                FROM empresas AS filiais
                WHERE filiais.id_matriz = 11
            )
        )
    GROUP BY
        id_atribuicao,
        id_pessoa,
        associados
) AS lim1 ON lim1.id_atribuicao = atribuicoes.id AND lim1.id_pessoa = pessoas.id

LEFT JOIN (
    SELECT
        retiradas.id_atribuicao,
        retiradas.id_pessoa,
        SUM(retiradas.qtd) AS qtd,
        MAX(retiradas.data) AS ultima_retirada,
        DATE_ADD(MAX(retiradas.data), INTERVAL atribuicoes.validade DAY) AS proxima_retirada

    FROM retiradas

    JOIN atribuicoes
        ON atribuicoes.id = retiradas.id_atribuicao

    WHERE retiradas.id_supervisor IS NULL

    GROUP BY
        retiradas.id_atribuicao,
        retiradas.id_pessoa,
        atribuicoes.validade
) AS ret ON ret.id_atribuicao = atribuicoes.id AND ret.id_pessoa = pessoas.id

LEFT JOIN (
    SELECT
        tab.id_atribuicao,
        tab.id_pessoa,
        (SUM(atribuicoes.qtd) - IFNULL(SUM(ret.qtd), 0)) AS qtd,
        IFNULL(DATE_FORMAT(MAX(ret.ultima_retirada), '%d/%m/%Y'), '') AS ultima_retirada,
        DATE_FORMAT(
            CASE
                WHEN (SUM(atribuicoes.qtd) - IFNULL(SUM(ret.qtd), 0)) > 0 THEN CURDATE()
                ELSE MIN(ret.proxima_retirada)
            END,
            '%d/%m/%Y'
        ) AS proxima_retirada
    
    FROM (
        SELECT
            id_atribuicao,
            id_pessoa,
            associados
        FROM atribuicoes_associadas AS aa
        JOIN pessoas
            ON pessoas.id = aa.id_pessoa
        WHERE pessoas.lixeira = 0
            AND pessoas.id_empresa IN (
                SELECT id
                FROM empresas
                WHERE empresas.id = 11
                UNION ALL (
                    SELECT filiais.id
                    FROM empresas AS filiais
                    WHERE filiais.id_matriz = 11
                )
            )
        GROUP BY
            id_atribuicao,
            id_pessoa,
            associados
    ) AS tab

    JOIN atribuicoes
        ON REPLACE(tab.associados, CONCAT('|', atribuicoes.id, '|'), '') <> tab.associados

    JOIN produtos
        ON (produtos.cod_externo = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'P')
            OR (produtos.referencia = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'R')

    JOIN pessoas
        ON (pessoas.id = atribuicoes.pessoa_ou_setor_valor AND atribuicoes.pessoa_ou_setor_chave = 'P')
            OR (pessoas.id_setor = atribuicoes.pessoa_ou_setor_valor AND atribuicoes.pessoa_ou_setor_chave = 'S')

    LEFT JOIN (
        SELECT
            retiradas.id_atribuicao,
            retiradas.id_pessoa,
            SUM(retiradas.qtd) AS qtd,
            MAX(retiradas.data) AS ultima_retirada,
            DATE_ADD(MAX(retiradas.data), INTERVAL MIN(atribuicoes.validade) DAY) AS proxima_retirada

        FROM retiradas

        JOIN atribuicoes
            ON atribuicoes.id = retiradas.id_atribuicao

        WHERE retiradas.id_supervisor IS NULL

        GROUP BY
            retiradas.id_atribuicao,
            retiradas.id_pessoa
    ) AS ret ON ret.id_atribuicao = atribuicoes.id AND ret.id_pessoa = pessoas.id

    GROUP BY
        tab.id_atribuicao,
        tab.id_pessoa
) AS atbgrp ON atbgrp.id_atribuicao = atribuicoes.id AND atbgrp.id_pessoa = pessoas.id

WHERE (atbgrp.proxima_retirada <= CURDATE() OR (
    CASE
        WHEN (atribuicoes.qtd - IFNULL(ret.qtd, 0)) > 0 THEN CURDATE()
        ELSE ret.proxima_retirada
    END
) <= CURDATE()) AND atribuicoes.obrigatorio = 1

GROUP BY
    pessoas.id,
    pessoas.nome,
    pessoas.foto;