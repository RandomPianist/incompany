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
CREATE TABLE mat_vretiradas (
    id_pessoa INT,
    id_atribuicao INT,
    id_setor INT,
    valor NUMERIC(10,5)
);
ALTER TABLE mat_vretiradas
    ADD INDEX idx_mat_vretiradas_atr_pessoa (id_atribuicao, id_pessoa),
    ADD INDEX idx_mat_vretiradas_pessoa (id_pessoa),
    ADD INDEX idx_mat_vretiradas_setor (id_setor);
CREATE TABLE mat_vultretirada (
    id_pessoa INT,
    id_atribuicao INT,
    id_setor INT,
    data DATE
);
ALTER TABLE mat_vultretirada
    ADD INDEX idx_mat_vultretirada_atr_pessoa (id_atribuicao, id_pessoa),
    ADD INDEX idx_mat_vultretirada_pessoa (id_pessoa),
    ADD INDEX idx_mat_vultretirada_setor (id_setor);