SET GLOBAL event_scheduler = ON;
DELIMITER $$

CREATE EVENT IF NOT EXISTS atualizar
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURRENT_DATE, '02:00:00')
DO
BEGIN
    TRUNCATE TABLE mat_vultretirada;
    CALL excluir_atribuicao_sem_retirada();
    CALL refazer_ids();
    CALL reindexar();
    CALL limpar_usuario_editando();
    CALL atualizar_mat_vcomodatos(0);
    CALL atualizar_mat_vatbaux('T', '(0)', 'S');
    CALL atualizar_mat_vatribuicoes('T', '(0)', 'S');
    CALL atualizar_mat_vretiradas_vultretirada('T', '(0)', 'R', 'S');
    CALL atualizar_mat_vretiradas_vultretirada('T', '(0)', 'U', 'S');
END$$

DELIMITER ;