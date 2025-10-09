SET GLOBAL event_scheduler = ON;
DELIMITER $$

CREATE EVENT IF NOT EXISTS atualizar
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURRENT_DATE, '02:00:00')
DO
BEGIN
    START TRANSACTION;
    DELETE FROM mat_vultretirada;
    UPDATE atribuicoes SET rascunho = 'S' WHERE rascunho IN ('E', 'R');
    UPDATE excecoes SET rascunho = 'S' WHERE rascunho IN ('E', 'R');
    UPDATE atribuicoes
    JOIN atbbkp
        ON atbbkp.id_atribuicao = atribuicoes.id
    SET
        atribuicoes.qtd = atbbkp.qtd,
        atribuicoes.data = atbbkp.data,
        atribuicoes.validade = atbbkp.validade,
        atribuicoes.obrigatorio = atbbkp.obrigatorio,
        atribuicoes.gerado = atbbkp.gerado,
        atribuicoes.id_usuario = 0;
    UPDATE excecoes
    JOIN excbkp
        ON excbkp.id_excecao = excecoes.id
    SET
        excecoes.id_pessoa = excbkp.id_pessoa,
        excecoes.id_setor = excbkp.id_setor,
        excecoes.id_usuario = 0;
    CALL excluir_atribuicao_sem_retirada();
    CALL refazer_ids();
    CALL reindexar();
    CALL limpar_usuario_editando();
    CALL atualizar_mat_vcomodatos(0);
    CALL atualizar_mat_vatbaux('T', '(0)', 'S');
    CALL atualizar_mat_vatribuicoes('T', '(0)', 'S');
    CALL atualizar_mat_vretiradas_vultretirada('T', '(0)', 'R', 'S');
    CALL atualizar_mat_vretiradas_vultretirada('T', '(0)', 'U', 'S');
    COMMIT;
    TRUNCATE TABLE excbkp;
    TRUNCATE TABLE atbbkp;
END$$

DELIMITER ;