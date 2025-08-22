CREATE DATABASE kxsafe;
USE kxsafe;

CREATE TABLE valores (
  	id INT AUTO_INCREMENT PRIMARY KEY,
  	seq INT,
	descr VARCHAR(32),
	alias VARCHAR(16),
    id_externo INT,
	lixeira TINYINT DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE setores (
	id INT AUTO_INCREMENT PRIMARY KEY,
	descr VARCHAR(32),
	cria_usuario TINYINT DEFAULT 0,
	id_empresa INT,
	lixeira TINYINT DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE empresas (
	id INT AUTO_INCREMENT PRIMARY KEY,
	razao_social VARCHAR(128),
	nome_fantasia VARCHAR(64),
	cnpj VARCHAR(32),
	lixeira TINYINT DEFAULT 0,
	id_matriz INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	cod_externo VARCHAR(20)
);

CREATE TABLE pessoas (
	id INT AUTO_INCREMENT PRIMARY KEY,
	nome VARCHAR(64),
	cpf VARCHAR(16),
	lixeira TINYINT DEFAULT 0,
	id_setor INT,
	id_empresa INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	funcao VARCHAR(64),
	admissao DATE,
	senha INT,
	foto VARCHAR(512),
	foto64 TEXT,
	supervisor TINYINT DEFAULT 0,
	biometria TEXT
);

CREATE TABLE users (
	id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255),
    email_verified_at TIMESTAMP,
    password VARCHAR(255),
    remember_token VARCHAR(100),
    id_pessoa INT,
    admin TINYINT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE produtos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	descr VARCHAR(256),
	preco NUMERIC(8,2),
    prmin NUMERIC(8,2),
	validade INT,
	lixeira TINYINT DEFAULT 0,
	ca VARCHAR(16),
	foto VARCHAR(512),
	cod_externo VARCHAR(8),
	id_categoria INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	referencia VARCHAR(64),
	tamanho VARCHAR(32),
	detalhes TEXT,
	validade_ca DATE,
	consumo TINYINT,
	cod_fab VARCHAR(30)
);

CREATE TABLE maquinas_produtos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	descr VARCHAR(16),
	minimo NUMERIC(10,5),
	maximo NUMERIC(10,5),
	id_maquina INT,
	id_produto INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	preco NUMERIC(8,2)
);

CREATE TABLE estoque (
	id INT AUTO_INCREMENT PRIMARY KEY,
	es CHAR,
	descr VARCHAR(16),
	qtd NUMERIC(10,5),
    preco NUMERIC(8,2),
	id_mp INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE comodatos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	inicio DATE,
	fim DATE,
	fim_orig DATE,
	id_maquina INT,
	id_empresa INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE atribuicoes (
	id INT AUTO_INCREMENT PRIMARY KEY,
	pessoa_ou_setor_chave VARCHAR(1),
	pessoa_ou_setor_valor INT,
	produto_ou_referencia_chave VARCHAR(1),
	produto_ou_referencia_valor VARCHAR(256),
	qtd NUMERIC(10,5),
	validade INT,
	obrigatorio TINYINT DEFAULT 0,
	id_empresa INT DEFAULT 0,
	lixeira TINYINT DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE retiradas (
	id INT AUTO_INCREMENT PRIMARY KEY,
	qtd NUMERIC(10,5),
	id_atribuicao INT,
	id_comodato INT,
	id_pessoa INT,
	id_supervisor INT,
	id_produto INT,
	observacao TEXT,
	data DATE,
    hora VARCHAR(8),
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	gerou_pedido CHAR,
	numero_ped INT,
	biometria_ou_senha VARCHAR(1),
    ca VARCHAR(16),
    preco NUMERIC(8,2),
	id_empresa INT,
    id_setor
);

CREATE TABLE solicitacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status VARCHAR(1), -- (A)berta, (C)ancelada, (E)m andamento, (R)ecusada, (F)inalizada
    avisou TINYINT DEFAULT 0,
    data DATE,
    usuario_erp VARCHAR(32),
    usuario_erp2 VARCHAR(32),
    usuario_web VARCHAR(64),
    id_comodato INT,
    id_externo INT, -- FTANTF.Recnum
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE solicitacoes_produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produto_orig INT,
    qtd_orig NUMERIC(10,5),
    preco_orig NUMERIC(8,2),
    id_produto INT,
    qtd NUMERIC(10,5),
    preco NUMERIC(8,2),
    origem VARCHAR(4),
    obs VARCHAR(256),
    id_solicitacao INT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE previas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_comodato INT,
    id_produto INT,
    id_pessoa INT,
    qtd NUMERIC(10,5),
    confirmado TINYINT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE log (
	id INT AUTO_INCREMENT PRIMARY KEY,
	id_pessoa INT,
	nome VARCHAR(64),
    origem VARCHAR(4),
    data DATE,
    hms VARCHAR(8),
	acao CHAR,
	tabela VARCHAR(32),
	fk INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100),
    id_pessoa INT,
    admin TINYINT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE VIEW vestoque AS (
	SELECT
		SUM(CASE
			WHEN (es = 'E') THEN qtd
			ELSE qtd * -1
		END) AS qtd,
		id_mp
	
	FROM estoque

	GROUP BY id_mp
);

CREATE VIEW vmp AS (
    SELECT
        mp.id_produto,
        mp.id_maquina,
        IFNULL(vestoque.qtd, 0) AS qtd

    FROM maquinas_produtos AS mp

    LEFT JOIN vestoque
        ON vestoque.id_mp = mp.id
);

CREATE VIEW vprodutos AS (
    SELECT
        minhas_empresas.id_pessoa,
        produtos.id AS id_produto,
        mp.id AS id_mp,
        vestoque.qtd
    
    FROM produtos
    
    JOIN maquinas_produtos AS mp
        ON mp.id_produto = produtos.id

    JOIN comodatos
        ON comodatos.id_maquina = mp.id_maquina

    JOIN vestoque
        ON vestoque.id_mp = mp.id

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

    JOIN pessoas
        ON pessoas.id = minhas_empresas.id_pessoa

    JOIN empresas
        ON empresas.id = minhas_empresas.id_empresa

    WHERE vestoque.qtd > 0
      AND produtos.lixeira = 0
      AND empresas.lixeira = 0
      AND pessoas.lixeira = 0
      AND ((DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(CURDATE()), '-01')) BETWEEN comodatos.inicio AND comodatos.fim) OR (CURDATE() BETWEEN comodatos.inicio AND comodatos.fim))

    GROUP BY
        minhas_empresas.id_pessoa,
        produtos.id,
        mp.id,
        vestoque.qtd

    UNION ALL (
        SELECT
            pessoas.id AS id_pessoa,
            produtos.id AS id_produto,
            mp.id AS id_mp,
            vestoque.qtd

        FROM pessoas

        CROSS JOIN maquinas_produtos AS mp

        JOIN produtos
            ON produtos.id = mp.id_produto

        JOIN vestoque
            ON vestoque.id_mp = mp.id

        WHERE pessoas.id_empresa = 0
          AND pessoas.lixeira = 0
          AND produtos.lixeira = 0

        GROUP BY
            pessoas.id,
            produtos.id,
            mp.id,
            vestoque.qtd
    )
);

CREATE VIEW vatbaux AS (
    SELECT
        p.id AS id_pessoa,
        a.id AS id_atribuicao,
        prod.cod_externo AS cod,
        IFNULL(prod.referencia, '') AS ref,
        'PP' AS src
    
    FROM atribuicoes AS a

    JOIN pessoas AS p
        ON p.id = a.pessoa_ou_setor_valor

    JOIN produtos AS prod 
        ON prod.cod_externo = a.produto_ou_referencia_valor

    WHERE prod.lixeira = 0
      AND a.lixeira = 0
      AND a.pessoa_ou_setor_chave = 'P'
      AND a.produto_ou_referencia_chave = 'P'

    UNION ALL (
        SELECT 
            p.id AS id_pessoa,
            a.id AS id_atribuicao,
            NULL AS cod,
            a.produto_ou_referencia_valor AS ref,
            'PR' AS src

        FROM atribuicoes AS a

        JOIN pessoas AS p
            ON p.id = a.pessoa_ou_setor_valor
        
        WHERE a.lixeira = 0
          AND a.pessoa_ou_setor_chave = 'P'
          AND a.produto_ou_referencia_chave = 'R'
    )

    UNION ALL (
        SELECT 
            p.id AS id_pessoa,
            a.id AS id_atribuicao,
            prod.cod_externo AS cod,
            IFNULL(prod.referencia, '') AS ref,
            'SP' AS src

        FROM atribuicoes AS a
        
        JOIN pessoas AS p 
            ON p.id_setor = a.pessoa_ou_setor_valor

        JOIN produtos AS prod 
            ON prod.cod_externo = a.produto_ou_referencia_valor
        
        WHERE prod.lixeira = 0
          AND a.lixeira = 0
          AND a.pessoa_ou_setor_chave = 'S'
          AND a.produto_ou_referencia_chave = 'P'
    )

    UNION ALL (
        SELECT 
            p.id AS id_pessoa,
            a.id AS id_atribuicao,
            NULL AS cod,
            a.produto_ou_referencia_valor AS ref,
            'SR' AS src
        
        FROM atribuicoes AS a
        
        JOIN pessoas AS p
            ON p.id_setor = a.pessoa_ou_setor_valor

        WHERE a.lixeira = 0
          AND a.pessoa_ou_setor_chave = 'S'
          AND a.produto_ou_referencia_chave = 'R'
    )
);

CREATE VIEW vatribuicoes AS (
    SELECT
        f.id_pessoa,
        f.id_atribuicao,
        assoc.id_associado
    
    FROM (
        SELECT
            x.id_pessoa,
            x.id_atribuicao
    
        FROM vatbaux AS x

        JOIN pessoas
            ON pessoas.id = x.id_pessoa
        
        WHERE (x.src = 'PP'
            OR (
                x.src = 'PR' AND NOT EXISTS (
                    SELECT 1
                    FROM atribuicoes AS a2
                    JOIN produtos AS p2
                        ON p2.cod_externo = a2.produto_ou_referencia_valor
                    WHERE a2.pessoa_ou_setor_chave = 'P'
                      AND a2.produto_ou_referencia_chave = 'P'
                      AND a2.pessoa_ou_setor_valor = x.id_pessoa
                      AND p2.cod_externo = x.cod
                      AND a2.lixeira = 0
                      AND p2.lixeira = 0
                )
            ) OR (
                x.src = 'SP' AND NOT EXISTS (
                    SELECT 1
                    FROM atribuicoes AS a2
                    JOIN produtos AS p2
                        ON p2.cod_externo = a2.produto_ou_referencia_valor
                    WHERE a2.pessoa_ou_setor_chave = 'P'
                      AND a2.produto_ou_referencia_chave = 'P'
                      AND a2.pessoa_ou_setor_valor = x.id_pessoa
                      AND p2.cod_externo = x.cod
                      AND a2.lixeira = 0
                      AND p2.lixeira = 0
                ) AND NOT EXISTS (
                    SELECT 1
                    FROM atribuicoes AS a3
                    JOIN produtos AS p3
                        ON p3.referencia = a3.produto_ou_referencia_valor
                    WHERE a3.pessoa_ou_setor_chave = 'P'
                      AND a3.produto_ou_referencia_chave = 'R'
                      AND a3.pessoa_ou_setor_valor = x.id_pessoa
                      AND p3.referencia = x.ref
                      AND a3.lixeira = 0
                      AND p3.lixeira = 0
                ) 
            ) OR (
                x.src = 'SR' AND NOT EXISTS (
                    SELECT 1
                    FROM atribuicoes AS a2
                    JOIN produtos AS p2
                        ON p2.cod_externo = a2.produto_ou_referencia_valor
                    WHERE a2.pessoa_ou_setor_chave = 'P'
                      AND a2.produto_ou_referencia_chave = 'P'
                      AND a2.pessoa_ou_setor_valor = x.id_pessoa
                      AND p2.cod_externo = x.cod
                      AND a2.lixeira = 0
                      AND p2.lixeira = 0
                ) AND NOT EXISTS (
                    SELECT 1
                    FROM atribuicoes AS a3
                    JOIN produtos AS p3
                        ON p3.referencia = a3.produto_ou_referencia_valor
                    WHERE a3.pessoa_ou_setor_chave = 'P'
                      AND a3.produto_ou_referencia_chave = 'R'
                      AND a3.pessoa_ou_setor_valor = x.id_pessoa
                      AND p3.referencia = x.ref
                      AND a3.lixeira = 0
                      AND p3.lixeira = 0
                ) AND NOT EXISTS (
                    SELECT 1
                    FROM atribuicoes AS a4
                    JOIN produtos AS p4
                        ON p4.cod_externo = a4.produto_ou_referencia_valor
                    WHERE a4.pessoa_ou_setor_chave = 'S'
                      AND a4.produto_ou_referencia_chave = 'P'
                      AND a4.pessoa_ou_setor_valor = pessoas.id_setor
                      AND p4.cod_externo = x.cod
                      AND a4.lixeira = 0
                      AND p4.lixeira = 0
                ) 
            )
        )

        GROUP BY
            x.id_pessoa,
            x.id_atribuicao
    ) AS f

    LEFT JOIN (
        SELECT
            pai.id_pessoa,
            pai.id_atribuicao,
            filho.id_atribuicao AS id_associado

        FROM vatbaux AS pai

        JOIN vatbaux AS filho
            ON pai.id_pessoa = filho.id_pessoa AND ((pai.ref = filho.ref AND pai.ref <> '') OR pai.cod = filho.cod)

        JOIN atribuicoes AS a1
            ON a1.id = pai.id_atribuicao

        JOIN atribuicoes AS a2
            ON a2.id = filho.id_atribuicao

        WHERE a1.lixeira = 0
          AND a2.lixeira = 0
    ) AS assoc ON f.id_pessoa = assoc.id_pessoa AND f.id_atribuicao = assoc.id_atribuicao

    JOIN pessoas
        ON pessoas.id = f.id_pessoa

    WHERE pessoas.lixeira = 0

    ORDER BY
        f.id_pessoa,
        f.id_atribuicao
);

CREATE VIEW vpendentes AS (
    SELECT
        vatribuicoes.id_pessoa,

        atribuicoes.validade,
        atribuicoes.id AS id_atribuicao,
        atribuicoes.obrigatorio,
        atribuicoes.produto_ou_referencia_chave,
        
        produtos.id AS id_produto,
        CASE
            WHEN atribuicoes.produto_ou_referencia_chave = 'R' THEN produtos.referencia
            ELSE produtos.id
        END AS chave_produto,
        CASE
            WHEN atribuicoes.produto_ou_referencia_chave = 'R' THEN produtos.referencia
            ELSE produtos.descr
        END AS nome_produto,
        produtos.referencia,
        produtos.descr,
        produtos.detalhes,
        produtos.cod_externo AS codbar,
        IFNULL(produtos.tamanho, '') AS tamanho,
        IFNULL(produtos.foto, '') AS foto,
        
        CASE
            WHEN ((DATE_ADD(atbgrp.data, INTERVAL atribuicoes.validade DAY) <= CURDATE()) OR (atbgrp.data IS NULL)) THEN
                ROUND(CASE
                    WHEN vestoque.qtd < (atribuicoes.qtd - calc_qtd.valor) THEN vestoque.qtd
                    ELSE (atribuicoes.qtd - calc_qtd.valor)
                END)
            ELSE 0
        END AS qtd,
        IFNULL(DATE_FORMAT(atbgrp.data, '%d/%m/%Y'), '') AS ultima_retirada,
        DATE_FORMAT(IFNULL(DATE_ADD(atbgrp.data, INTERVAL atribuicoes.validade DAY), CURDATE()), '%d/%m/%Y') AS proxima_retirada,
        DATE(IFNULL(DATE_ADD(atbgrp.data, INTERVAL atribuicoes.validade DAY), atribuicoes.created_at)) AS proxima_retirada_real,
        CASE
            WHEN ((DATE_ADD(atbgrp.data, INTERVAL atribuicoes.validade DAY) <= CURDATE()) OR (atbgrp.data IS NULL)) THEN 1
            ELSE 0
        END AS esta_pendente
        
    FROM atribuicoes

    JOIN vatribuicoes
        ON vatribuicoes.id_atribuicao = atribuicoes.id
        
    JOIN produtos
        ON (produtos.cod_externo = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'P')
            OR (produtos.referencia = atribuicoes.produto_ou_referencia_valor AND atribuicoes.produto_ou_referencia_chave = 'R')

    JOIN (
        SELECT * FROM vprodutos WHERE qtd > 0
    ) AS lim_prod ON lim_prod.id_produto = produtos.id AND lim_prod.id_pessoa = vatribuicoes.id_pessoa
    
    JOIN vestoque
        ON vestoque.id_mp = lim_prod.id_mp

    JOIN (
        SELECT
            vatribuicoes.id_pessoa,
            vatribuicoes.id_atribuicao,
            IFNULL(SUM(retiradas.qtd), 0) AS valor
            
        FROM atribuicoes
        
        JOIN vatribuicoes
            ON vatribuicoes.id_atribuicao = atribuicoes.id
            
        JOIN pessoas
            ON pessoas.id = vatribuicoes.id_pessoa
        
        LEFT JOIN retiradas
            ON retiradas.id_atribuicao = atribuicoes.id
                AND retiradas.id_pessoa = pessoas.id
                AND (retiradas.id_empresa = pessoas.id_empresa OR pessoas.id_empresa = 0)
                AND retiradas.data >= DATE(atribuicoes.created_at)
                AND retiradas.id_supervisor IS NULL
        
        GROUP BY
            vatribuicoes.id_pessoa,
            vatribuicoes.id_atribuicao
    ) AS calc_qtd ON calc_qtd.id_atribuicao = atribuicoes.id AND calc_qtd.id_pessoa = vatribuicoes.id_pessoa

    JOIN (
        SELECT
            vatribuicoes.id_pessoa,
            vatribuicoes.id_atribuicao,
            MAX(retiradas.data) AS data
            
        FROM atribuicoes
        
        JOIN vatribuicoes
            ON vatribuicoes.id_atribuicao = atribuicoes.id
            
        JOIN atribuicoes AS associadas
            ON associadas.id = vatribuicoes.id_associado
            
        JOIN pessoas
            ON pessoas.id = vatribuicoes.id_pessoa
            
        LEFT JOIN retiradas
            ON retiradas.id_atribuicao = associadas.id
                AND retiradas.id_pessoa = pessoas.id
                AND (retiradas.id_empresa = pessoas.id_empresa OR pessoas.id_empresa = 0)
                AND retiradas.id_supervisor IS NULL
                
        GROUP BY
            vatribuicoes.id_atribuicao,
            vatribuicoes.id_pessoa
    ) AS atbgrp ON atbgrp.id_atribuicao = atribuicoes.id AND atbgrp.id_pessoa = vatribuicoes.id_pessoa

    WHERE (atribuicoes.qtd - calc_qtd.valor) > 0

    GROUP BY
        vatribuicoes.id_pessoa,
        atribuicoes.id,
        atribuicoes.obrigatorio,
        atribuicoes.validade,
        atribuicoes.qtd,
        atribuicoes.created_at,
        produtos.id,
        produtos.referencia,
        produtos.descr,
        produtos.detalhes,
        produtos.cod_externo,
        produtos.tamanho,
        produtos.foto,
        atbgrp.data,
        vestoque.qtd,
        calc_qtd.valor,
        atribuicoes.produto_ou_referencia_chave,
        CASE
            WHEN atribuicoes.produto_ou_referencia_chave = 'R' THEN produtos.referencia
            ELSE produtos.id
        END,
        CASE
            WHEN atribuicoes.produto_ou_referencia_chave = 'R' THEN produtos.referencia
            ELSE produtos.descr
        END
);

ALTER TABLE estoque
  ADD INDEX idx_estoque_id_mp (id_mp),
  ADD INDEX idx_estoque_idmp_es_qtd (id_mp, es, qtd);

ALTER TABLE maquinas_produtos
  ADD INDEX idx_mp_id_produto (id_produto),
  ADD INDEX idx_mp_id_maquina (id_maquina);

ALTER TABLE comodatos
  ADD INDEX idx_comodatos_id_maquina (id_maquina),
  ADD INDEX idx_comodatos_id_empresa (id_empresa),
  ADD INDEX idx_comodatos_maquina_empresa_inicio_fim (id_maquina, id_empresa, inicio, fim);

ALTER TABLE produtos
  ADD INDEX idx_produtos_codext_lixeira (cod_externo, lixeira),
  ADD INDEX idx_produtos_referencia_lixeira (referencia, lixeira);

ALTER TABLE pessoas
  ADD INDEX idx_pessoas_id_empresa (id_empresa),
  ADD INDEX idx_pessoas_id_setor (id_setor),
  ADD INDEX idx_pessoas_lixeira (lixeira);

ALTER TABLE empresas
  ADD INDEX idx_empresas_id_matriz (id_matriz),
  ADD INDEX idx_empresas_lixeira (lixeira);

ALTER TABLE atribuicoes
  ADD INDEX idx_atr_pessoa_valor_chaves (pessoa_ou_setor_valor, pessoa_ou_setor_chave, produto_ou_referencia_chave, produto_ou_referencia_valor, lixeira),
  ADD INDEX idx_atr_prodref_valor_chave (produto_ou_referencia_valor, produto_ou_referencia_chave, lixeira);

ALTER TABLE retiradas
  ADD INDEX idx_retiradas_atb_pessoa_empresa_data_super (id_atribuicao, id_pessoa, id_empresa, data, id_supervisor);