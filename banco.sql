CREATE DATABASE kxsafe;
USE kxsafe;

CREATE TABLE valores (
  	id INT AUTO_INCREMENT PRIMARY KEY,
  	seq INT,
	descr VARCHAR(32),
	alias VARCHAR(16),
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

CREATE TABLE produtos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	descr VARCHAR(256),
	preco NUMERIC(8,2),
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
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	gerou_pedido CHAR,
	numero_ped INT,
	biometria_ou_senha VARCHAR(1),
	id_empresa INT
);

CREATE TABLE log (
	id INT AUTO_INCREMENT PRIMARY KEY,
	id_pessoa INT,
	nome VARCHAR(32),
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
        produtos.id AS id_produto
    
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
        produtos.id
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
        assoc.associados
    
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
            CONCAT('|', GROUP_CONCAT(filho.id_atribuicao ORDER BY CASE WHEN pai.id_atribuicao = filho.id_atribuicao THEN 0 ELSE 1 END, filho.id_atribuicao SEPARATOR '|'), '|') AS associados

        FROM vatbaux AS pai

        JOIN vatbaux AS filho
            ON pai.id_pessoa = filho.id_pessoa AND ((pai.ref = filho.ref AND pai.ref <> '') OR pai.cod = filho.cod)

        JOIN atribuicoes AS a1
            ON a1.id = pai.id_atribuicao

        JOIN atribuicoes AS a2
            ON a2.id = filho.id_atribuicao

        WHERE a1.lixeira = 0
          AND a2.lixeira = 0

        GROUP BY
            pai.id_pessoa,
            pai.id_atribuicao
    ) AS assoc ON f.id_pessoa = assoc.id_pessoa AND f.id_atribuicao = assoc.id_atribuicao

    JOIN pessoas
        ON pessoas.id = f.id_pessoa

    WHERE pessoas.lixeira = 0

    ORDER BY
        f.id_pessoa,
        f.id_atribuicao
);

CREATE TABLE atribuicoes_associadas AS SELECT * FROM vatribuicoes;