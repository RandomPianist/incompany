CREATE DATABASE incompany_;
USE incompany_;

CREATE TABLE empresas (
	id INT AUTO_INCREMENT PRIMARY KEY,
	razao_social VARCHAR(128),
	nome_fantasia VARCHAR(64),
	cnpj VARCHAR(32),
    cidade VARCHAR(32),
	cod_externo VARCHAR(32),
    lixeira TINYINT DEFAULT 0,
	id_matriz INT,
    id_usuario_editando INT DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

ALTER TABLE empresas ADD FOREIGN KEY (id_matriz) REFERENCES empresas(id);

CREATE TABLE setores (
	id INT AUTO_INCREMENT PRIMARY KEY,
	descr VARCHAR(32),
    supervisor TINYINT DEFAULT 0,
    visitante TINYINT DEFAULT 0,
	cria_usuario TINYINT DEFAULT 0,
	lixeira TINYINT DEFAULT 0,
	id_empresa INT,
    id_usuario_editando INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_empresa) REFERENCES empresas(id)
);

CREATE TABLE pessoas (
	id INT AUTO_INCREMENT PRIMARY KEY,
	nome VARCHAR(64),
	cpf VARCHAR(16),
	funcao VARCHAR(64),
	foto VARCHAR(512),
    senha VARCHAR(4),
    admissao DATE,
	foto64 TEXT,
	biometria TEXT,
    telefone VARCHAR(32),
    email VARCHAR(256),
    matricula VARCHAR(32),
    supervisor TINYINT DEFAULT 0,
    visitante TINYINT DEFAULT 0,
    lixeira TINYINT DEFAULT 0,
    id_setor INT,
	id_empresa INT,
    id_usuario_editando INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_setor) REFERENCES setores(id),
	FOREIGN KEY (id_empresa) REFERENCES empresas(id)
);

CREATE TABLE users (
	id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255),
    email_verified_at TIMESTAMP,
    password VARCHAR(255),
    remember_token VARCHAR(100),
    admin TINYINT DEFAULT 0,
    id_pessoa INT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pessoa) REFERENCES pessoas(id)
);

ALTER TABLE empresas ADD FOREIGN KEY (id_usuario_editando) REFERENCES users(id);
ALTER TABLE setores ADD FOREIGN KEY (id_usuario_editando) REFERENCES users(id);
ALTER TABLE pessoas ADD FOREIGN KEY (id_usuario_editando) REFERENCES users(id);

CREATE TABLE maquinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descr VARCHAR(32),
    patrimonio VARCHAR(128),
    id_ant INT,
    lixeira TINYINT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descr VARCHAR(32),
    id_externo INT,
    lixeira TINYINT DEFAULT 0,
    id_usuario_editando INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario_editando) REFERENCES users(id)
);

CREATE TABLE permissoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    financeiro TINYINT DEFAULT 0,
    atribuicoes TINYINT DEFAULT 0,
    retiradas TINYINT DEFAULT 0,
    pessoas TINYINT DEFAULT 0,
    usuarios TINYINT DEFAULT 0,
    solicitacoes TINYINT DEFAULT 0,
    id_usuario INT,
    id_setor INT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES users(id),
    FOREIGN KEY (id_setor) REFERENCES setores(id)
);

CREATE TABLE produtos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	descr VARCHAR(256),
    referencia VARCHAR(64),
    cod_fab VARCHAR(30),
    ca VARCHAR(16),
	validade_ca DATE,
    foto VARCHAR(512),
	tamanho VARCHAR(32),
    detalhes TEXT,
    preco NUMERIC(8,2),
    prmin NUMERIC(8,2),
	validade INT,
	consumo TINYINT,
    cod_externo VARCHAR(8),
    lixeira TINYINT DEFAULT 0,
	id_categoria INT,
    id_usuario_editando INT DEFAULT 0,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_categoria) REFERENCES categorias(id),
    FOREIGN KEY (id_usuario_editando) REFERENCES users(id)
);

ALTER TABLE produtos ADD UNIQUE cod_externo (cod_externo(8));
ALTER TABLE produtos ADD UNIQUE referencia (referencia(64), tamanho(32));

CREATE TABLE comodatos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	inicio DATE,
	fim DATE,
	fim_orig DATE,
    travar_ret TINYINT DEFAULT 1,
    travar_estq TINYINT DEFAULT 1,
    atb_todos TINYINT DEFAULT 0,
    qtd NUMERIC(10,5) DEFAULT 99,
	validade INT DEFAULT 1,
	obrigatorio TINYINT DEFAULT 0,
	id_maquina INT,
	id_empresa INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_maquina) REFERENCES maquinas(id),
	FOREIGN KEY (id_empresa) REFERENCES empresas(id)
);

CREATE TABLE comodatos_produtos (
	id INT AUTO_INCREMENT PRIMARY KEY,
	minimo NUMERIC(10,5),
	maximo NUMERIC(10,5),
	preco NUMERIC(8,2),
    lixeira TINYINT DEFAULT 0,
    id_comodato INT,
	id_produto INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_comodato) REFERENCES comodatos(id),
	FOREIGN KEY (id_produto) REFERENCES produtos(id)
);

CREATE TABLE estoque (
	id INT AUTO_INCREMENT PRIMARY KEY,
	es ENUM('E', 'S'),
    data DATE,
    hms VARCHAR(8),
	descr VARCHAR(16),
	qtd NUMERIC(10,5),
	id_cp INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_cp) REFERENCES comodatos_produtos(id)
);

CREATE TABLE atribuicoes (
	id INT AUTO_INCREMENT PRIMARY KEY,
    
    qtd NUMERIC(10,5),
    data DATE,
	validade INT,
	obrigatorio TINYINT DEFAULT 0,
    gerado TINYINT DEFAULT 0,
    rascunho ENUM('C', 'E', 'R', 'S', 'T') DEFAULT 'S', -- (C)riando, (E)ditando, (R)emovendo, (S)alvo, (T)emporário
    lixeira TINYINT DEFAULT 0,

    id_pessoa INT,
    id_setor INT,
    id_maquina INT,

    cod_produto VARCHAR(8),
    referencia VARCHAR(64),

    id_empresa INT DEFAULT 0,
    id_empresa_autor INT DEFAULT 0,
    id_usuario INT,

	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	
    FOREIGN KEY (id_pessoa) REFERENCES pessoas(id),
    FOREIGN KEY (id_setor) REFERENCES setores(id),
    FOREIGN KEY (id_maquina) REFERENCES maquinas(id),
    FOREIGN KEY (cod_produto) REFERENCES produtos(cod_externo),
    FOREIGN KEY (referencia) REFERENCES produtos(referencia),

	FOREIGN KEY (id_empresa) REFERENCES empresas(id),
    FOREIGN KEY (id_empresa_autor) REFERENCES empresas(id),
    FOREIGN KEY (id_usuario) REFERENCES users(id)
);

CREATE TABLE atbbkp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qtd NUMERIC(10,5),
    data DATE,
	validade INT,
	obrigatorio TINYINT DEFAULT 0,
    gerado TINYINT DEFAULT 0,
    id_usuario INT,
    id_atribuicao INT,
    id_usuario_editando INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_atribuicao) REFERENCES atribuicoes(id),
    FOREIGN KEY (id_usuario_editando) REFERENCES users(id),
    FOREIGN KEY (id_usuario) REFERENCES users(id)
);

CREATE TABLE excecoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_atribuicao INT,
    id_pessoa INT,
    id_setor INT,
    id_usuario INT,
    rascunho ENUM('C', 'E', 'R', 'S', 'T') DEFAULT 'S', -- (C)riando, (E)ditando, (R)emovendo, (S)alvo, (T)emporário
    lixeira TINYINT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_atribuicao) REFERENCES atribuicoes(id),
    FOREIGN KEY (id_pessoa) REFERENCES pessoas(id),
    FOREIGN KEY (id_setor) REFERENCES setores(id),
    FOREIGN KEY (id_usuario) REFERENCES users(id)
);

CREATE TABLE excbkp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pessoa INT,
    id_setor INT,
    id_usuario INT,
    id_excecao INT,
    id_usuario_editando INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pessoa) REFERENCES pessoas(id),
    FOREIGN KEY (id_setor) REFERENCES setores(id),
    FOREIGN KEY (id_usuario) REFERENCES users(id),
    FOREIGN KEY (id_usuario_editando) REFERENCES users(id),
    FOREIGN KEY (id_excecao) REFERENCES excecoes(id)
);

CREATE TABLE retiradas (
	id INT AUTO_INCREMENT PRIMARY KEY,
	qtd NUMERIC(10,5),
	data DATE,
    hms VARCHAR(8),
    observacao TEXT,
    ca VARCHAR(16),
    preco NUMERIC(8,2),
    numero_ped INT,
    biometria LONGTEXT,
    id_atribuicao INT,
	id_comodato INT,
	id_pessoa INT,
	id_supervisor INT,
	id_produto INT,
	id_empresa INT,
    id_setor INT,
	
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	
	FOREIGN KEY (id_atribuicao) REFERENCES atribuicoes(id),
	FOREIGN KEY (id_comodato) REFERENCES comodatos(id),
	FOREIGN KEY (id_pessoa) REFERENCES pessoas(id),
	FOREIGN KEY (id_supervisor) REFERENCES pessoas(id),
	FOREIGN KEY (id_produto) REFERENCES produtos(id),
    FOREIGN KEY (id_empresa) REFERENCES empresas(id),
    FOREIGN KEY (id_setor) REFERENCES setores(id)
);

CREATE TABLE solicitacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    situacao ENUM('A', 'C', 'E', 'R', 'F'), -- (A)berta, (C)ancelada, (E)m andamento, (R)ecusada, (F)inalizada
    avisou TINYINT DEFAULT 0,
    data DATE,
    usuario_erp VARCHAR(32),
    usuario_erp2 VARCHAR(32),
    usuario_web VARCHAR(64),
    id_externo INT, -- FTANTF.Recnum
    id_comodato INT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_comodato) REFERENCES comodatos(id)
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
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_produto) REFERENCES produtos(id),
    FOREIGN KEY (id_produto_orig) REFERENCES produtos(id),
    FOREIGN KEY (id_solicitacao) REFERENCES solicitacoes(id)
);

CREATE TABLE previas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qtd NUMERIC(10,5),
    confirmado TINYINT DEFAULT 0,
    id_comodato INT,
    id_produto INT,
    id_usuario INT,    
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_comodato) REFERENCES comodatos(id),
    FOREIGN KEY (id_produto) REFERENCES produtos(id),
    FOREIGN KEY (id_usuario) REFERENCES users(id)
);

CREATE TABLE log (
	id INT AUTO_INCREMENT PRIMARY KEY,
	id_pessoa INT,
	nome VARCHAR(64),
    origem VARCHAR(4),
    data DATE,
    hms VARCHAR(8),
	acao ENUM('C', 'E', 'D'),
	tabela VARCHAR(32),
	fk INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	FOREIGN KEY (id_pessoa) REFERENCES pessoas(id),
	FOREIGN KEY (fk) REFERENCES atribuicoes(id),
    FOREIGN KEY (fk) REFERENCES categorias(id),
	FOREIGN KEY (fk) REFERENCES comodatos(id),
    FOREIGN KEY (fk) REFERENCES comodatos_produtos(id),
	FOREIGN KEY (fk) REFERENCES empresas(id),
	FOREIGN KEY (fk) REFERENCES estoque(id),
	FOREIGN KEY (fk) REFERENCES maquinas(id),
    FOREIGN KEY (fk) REFERENCES permissoes(id),
	FOREIGN KEY (fk) REFERENCES pessoas(id),
    FOREIGN KEY (fk) REFERENCES previas(id),
	FOREIGN KEY (fk) REFERENCES produtos(id),
	FOREIGN KEY (fk) REFERENCES retiradas(id),
	FOREIGN KEY (fk) REFERENCES setores(id),
    FOREIGN KEY (fk) REFERENCES solicitacoes(id),
    FOREIGN KEY (fk) REFERENCES solicitacoes_produtos(id),
	FOREIGN KEY (fk) REFERENCES users(id)
);

CREATE TABLE usrbkp (
    id INT,
    name VARCHAR(255),
    email VARCHAR(255),
    password VARCHAR(255),
    id_pessoa INT,
    admin TINYINT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    FOREIGN KEY (id_pessoa) REFERENCES pessoas(id)
);

CREATE TABLE dedos (
    id INT AUTO_INCREMENT PRIMARY KEY,
	dedo INT,
    hash LONGTEXT,
    imagem LONGTEXT,
    id_pessoa INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pessoa) REFERENCES pessoas(id)
);

CREATE TABLE pre_retiradas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seq INT,
	id_produto INT,
    id_pessoa INT,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pessoa) REFERENCES pessoas(id),
    FOREIGN KEY (id_produto) REFERENCES produtos(id)
);