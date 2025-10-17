DROP VIEW IF EXISTS vestoque;
CREATE VIEW vestoque AS (
    SELECT
        SUM(CASE
            WHEN (es = 'E') THEN qtd
            ELSE qtd * -1
        END) AS qtd,
        id_cp

    FROM estoque

    GROUP BY id_cp
);
DROP VIEW IF EXISTS vprodaux;
CREATE VIEW vprodaux AS (
    SELECT
        id,
        cod_externo,
        CONCAT(IFNULL(CONCAT(cod_externo, ' - '), ''), descr) AS descr,
        referencia,
        tamanho,
        id_categoria,
        lixeira

    FROM produtos
);
DROP VIEW IF EXISTS vativos;
CREATE VIEW vativos AS (
    SELECT
        pessoas.id,
        pessoas.id_setor,
        pessoas.id_empresa,
        SUM(comodatos.atb_todos) AS atb_todos,
        GROUP_CONCAT(comodatos.id_maquina) AS maquinas
    
    FROM pessoas

    JOIN empresas
        ON pessoas.id_empresa IN (empresas.id, empresas.id_matriz)

    JOIN comodatos
        ON comodatos.id_empresa = empresas.id

    WHERE (CURDATE() >= comodatos.inicio AND CURDATE() < comodatos.fim)
      AND pessoas.lixeira = 0
      AND empresas.lixeira = 0

    GROUP BY
        pessoas.id,
        pessoas.id_setor,
        pessoas.id_empresa
);
DROP VIEW IF EXISTS vatbold;
CREATE VIEW vatbold AS (
    SELECT
        id,
        CASE
            WHEN cod_produto IS NOT NULL THEN 'P'
            ELSE 'R'
        END AS pr_chave,
        CASE
            WHEN cod_produto IS NOT NULL THEN cod_produto
            ELSE referencia
        END AS pr_valor,
        CASE
            WHEN id_pessoa IS NOT NULL THEN 'P'
            WHEN id_setor IS NOT NULL THEN 'S'
            ELSE 'M'
        END AS psm_chave,
        CASE
            WHEN id_pessoa IS NOT NULL THEN id_pessoa
            WHEN id_setor IS NOT NULL THEN id_setor
            ELSE id_maquina
        END AS psm_valor,
        validade,
        obrigatorio,
        referencia,
        cod_produto,
        qtd,
        data,
        id_empresa,
        id_empresa_autor,
        id_usuario,
        rascunho

    FROM atribuicoes

    WHERE lixeira = 0
      AND rascunho <> 'R'
);
DROP VIEW IF EXISTS vatbreal;
CREATE VIEW vatbreal AS (
    SELECT
        id,
        cod_produto,
        referencia,
        id_pessoa,
        id_setor,
        id_maquina,
        gerado,
        data,
        validade,
        lixeira

    FROM atribuicoes

    WHERE rascunho = 'S'
);
DROP VIEW IF EXISTS vprodutosmaq;
CREATE VIEW vprodutosmaq AS (
    SELECT
        mat_vcomodatos.id_pessoa,
        mat_vcomodatos.id_maquina,
        cp.id_produto,
        IFNULL(vestoque.qtd, 0) AS qtd,
        mat_vcomodatos.travar_estq

    FROM mat_vcomodatos

    JOIN comodatos_produtos AS cp
        ON cp.id_comodato = mat_vcomodatos.id

    LEFT JOIN vestoque
        ON vestoque.id_cp = cp.id

    WHERE cp.lixeira = 0

    UNION ALL (
        SELECT
            pessoas.id AS id_pessoa,
            NULL AS id_maquina,
            produtos.id AS id_produto,
            0 AS qtd,
            0 AS travar_estq

        FROM pessoas

        CROSS JOIN produtos

        WHERE pessoas.id_empresa = 0
          AND pessoas.lixeira = 0
          AND produtos.lixeira = 0
    )
);
DROP VIEW IF EXISTS vprodutosgeral;
CREATE VIEW vprodutosgeral AS (
    SELECT
        id_pessoa,
        id_produto,
        SUM(qtd) AS qtd,
        FLOOR(SUM(travar_estq) / COUNT(travar_estq)) AS travar_estq

    FROM vprodutosmaq

    GROUP BY
        id_pessoa,
        id_produto
);