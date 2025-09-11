SET GLOBAL event_scheduler = ON;
DELIMITER $$

CREATE EVENT IF NOT EXISTS atualizar
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURRENT_DATE, '02:00:00')
DO
BEGIN
    CALL atualizar_mat_vcomodatos(0);
    CALL atualizar_mat_vatbaux('T', '(0)', 'S');
    CALL atualizar_mat_vatribuicoes('T', '(0)', 'S');
    CALL atualizar_mat_vretiradas_vultretirada('T', '(0)', 'R', 'S');
    CALL atualizar_mat_vretiradas_vultretirada('T', '(0)', 'U', 'S');
END$$

DELIMITER ;