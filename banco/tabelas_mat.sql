CREATE TABLE mat_vcomodatos (
    id INT,
    id_pessoa INT,
    id_maquina INT,
    travar_estq INT,
    id_empresa INT
);
ALTER TABLE mat_vcomodatos
    ADD INDEX idx_mat_vcomodatos_id (id),
    ADD INDEX idx_mat_vcomodatos_maquina (id_maquina),
    ADD INDEX idx_mat_vcomodatos_pessoa (id_pessoa);
CREATE TABLE mat_vatbaux (
    id_pessoa INT,
    id_atribuicao INT,
    cod VARCHAR(8),
    ref VARCHAR(64),
    src VARCHAR(2),
    id_setor INT,
    lixeira TINYINT
);
ALTER TABLE mat_vatbaux
    ADD INDEX idx_mat_vatbaux_pp (id_pessoa, cod, src, lixeira),
    ADD INDEX idx_mat_vatbaux_pr (id_pessoa, ref, src, lixeira),
    ADD INDEX idx_mat_vatbaux_sp (id_setor, cod, src, lixeira),
    ADD INDEX idx_mat_vatbaux_sr (id_setor, ref, src, lixeira);
CREATE TABLE mat_vatribuicoes (
    id_pessoa INT,
    id_atribuicao INT,
    id_associado INT
);
ALTER TABLE mat_vatribuicoes
    ADD INDEX idx_aa_id_atr_pessoa (id_atribuicao, id_pessoa),
    ADD INDEX idx_aa_id_pessoa (id_pessoa),
    ADD INDEX idx_aa_id_associado (id_associado);
CREATE TABLE mat_vretiradas (
    id_pessoa INT,
    id_atribuicao INT,
    id_setor INT,
    valor NUMERIC(10,5)
);
ALTER TABLE mat_vretiradas
    ADD INDEX idx_mat_vretiradas_atr_pessoa_data (id_atribuicao, id_pessoa, valor),
    ADD INDEX idx_mat_vretiradas_pessoa (id_pessoa),
    ADD INDEX idx_mat_vretiradas_setor (id_setor);
CREATE TABLE mat_vultretirada (
    id_pessoa INT,
    id_atribuicao INT,
    id_setor INT,
    data DATE
);
ALTER TABLE mat_vultretirada
    ADD INDEX idx_mat_vultretirada_atr_pessoa_data (id_atribuicao, id_pessoa, data),
    ADD INDEX idx_mat_vultretirada_pessoa (id_pessoa),
    ADD INDEX idx_mat_vultretirada_setor (id_setor);
CREATE TABLE mat_vatbaux2 (
    id INT,
    pr_chave ENUM('P', 'R'),
    pr_valor VARCHAR(64),
    validade INT,
    obrigatorio TINYINT,
    referencia VARCHAR(64),
    cod_produto VARCHAR(8),
    qtd NUMERIC(10,5),
    data DATE
);
ALTER TABLE mat_vatbaux2
    ADD INDEX idx_mat_vatbaux2_id (id),
    ADD INDEX idx_mat_vatbaux2_pr (pr_chave, pr_valor);