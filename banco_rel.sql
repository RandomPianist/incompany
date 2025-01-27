CREATE DATABASE kxsafe;
USE kxsafe;

CREATE TABLE valores (
	id INT AUTO_INCREMENT PRIMARY KEY,
	seq INT,
	descr VARCHAR(32),
	alias VARCHAR(16), -- categorias, maquinas
	lixeira TINYINT DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-------------------------------------------------------------------------------------------------------------------------------------------------------------
-------------------------------------------------------------------------- PESSOAS --------------------------------------------------------------------------
-------------------------------------------------------------------------------------------------------------------------------------------------------------

CREATE TABLE setores (
	id INT AUTO_INCREMENT PRIMARY KEY,
	descr VARCHAR(32),
	cria_usuario TINYINT DEFAULT 0,
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

ALTER TABLE empresas ADD FOREIGN KEY (id_matriz) REFERENCES empresas(id);

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
	biometria TEXT,
	FOREIGN KEY (id_setor) REFERENCES setores(id),
	FOREIGN KEY (id_empresa) REFERENCES empresas(id)
);

CREATE TABLE users (
	id INT AUTO_INCREMENT PRIMARY KEY,
	email VARCHAR(64),
	password VARCHAR(512),
	lixeira TINYINT DEFAULT 0,
	id_pessoa INT,
	admin TINYINT DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_pessoa) REFERENCES pessoas(id)
);

------------------------------------------------------------------------------------------------------------------------------------------------------------
------------------------------------------------------------------------- PRODUTOS -------------------------------------------------------------------------
------------------------------------------------------------------------------------------------------------------------------------------------------------

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
	cod_fab VARCHAR(30),
	FOREIGN KEY (id_categoria) REFERENCES valores(id)
);

ALTER TABLE produtos ADD UNIQUE cod_externo (cod_externo(8));
ALTER TABLE produtos ADD UNIQUE referencia (referencia(64), tamanho(32));

CREATE TABLE maquinas_produtos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	descr VARCHAR(16),
	minimo NUMERIC(10,5),
	maximo NUMERIC(10,5),
	id_maquina INT,
	id_produto INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	preco NUMERIC(8,2),
	FOREIGN KEY (id_maquina) REFERENCES valores(id),
	FOREIGN KEY (id_produto) REFERENCES produtos(id)
);

CREATE TABLE estoque (
	id INT AUTO_INCREMENT PRIMARY KEY,
	es CHAR,
	descr VARCHAR(16),
	qtd NUMERIC(10,5),
	id_mp INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_mp) REFERENCES maquinas_produtos(id)
);

------------------------------------------------------------------------------------------------------------------------------------------------------------
------------------------------------------------------------------------- RESTANTE -------------------------------------------------------------------------
------------------------------------------------------------------------------------------------------------------------------------------------------------

CREATE TABLE comodatos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	inicio DATE,
	fim DATE,
	fim_orig DATE,
	id_maquina INT,
	id_empresa INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_maquina) REFERENCES valores(id),
	FOREIGN KEY (id_empresa) REFERENCES empresas(id)
);

CREATE TABLE atribuicoes (
	id INT AUTO_INCREMENT PRIMARY KEY,
	pessoa_ou_setor_chave CHAR,
	pessoa_ou_setor_valor INT,
	produto_ou_referencia_chave CHAR,
	produto_ou_referencia_valor VARCHAR(64),
	qtd NUMERIC(10,5),
	validade INT,
	obrigatorio TINYINT DEFAULT 0,
	lixeira TINYINT DEFAULT 0,
	id_empresa INT DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (pessoa_ou_setor_valor) REFERENCES pessoas(id),
	FOREIGN KEY (pessoa_ou_setor_valor) REFERENCES setores(id),
	FOREIGN KEY (produto_ou_referencia_valor) REFERENCES produtos(cod_externo),
	FOREIGN KEY (produto_ou_referencia_valor) REFERENCES produtos(referencia),
	FOREIGN KEY (id_empresa) REFERENCES empresas(id)
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
	id_empresa INT,
	FOREIGN KEY (id_atribuicao) REFERENCES atribuicoes(id),
	FOREIGN KEY (id_comodato) REFERENCES comodatos(id),
	FOREIGN KEY (id_pessoa) REFERENCES pessoas(id),
	FOREIGN KEY (id_supervisor) REFERENCES pessoas(id),
	FOREIGN KEY (id_produto) REFERENCES produtos(id)
);

CREATE TABLE log (
	id INT AUTO_INCREMENT PRIMARY KEY,
	id_pessoa INT,
	nome VARCHAR(32),
	acao CHAR,
	tabela VARCHAR(32),
	fk INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_pessoa) REFERENCES pessoas(id),
	FOREIGN KEY (fk) REFERENCES atribuicoes(id),
	FOREIGN KEY (fk) REFERENCES comodatos(id),
	FOREIGN KEY (fk) REFERENCES empresas(id),
	FOREIGN KEY (fk) REFERENCES estoque(id),
	FOREIGN KEY (fk) REFERENCES maquinas_produtos(id),
	FOREIGN KEY (fk) REFERENCES pessoas(id),
	FOREIGN KEY (fk) REFERENCES produtos(id),
	FOREIGN KEY (fk) REFERENCES retiradas(id),
	FOREIGN KEY (fk) REFERENCES setores(id),
	FOREIGN KEY (fk) REFERENCES users(id),
	FOREIGN KEY (fk) REFERENCES valores(id)
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