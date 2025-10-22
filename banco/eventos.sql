SET GLOBAL event_scheduler = ON;
DELIMITER $$

CREATE EVENT IF NOT EXISTS atualizar
ON SCHEDULE EVERY 1 DAY
STARTS TIMESTAMP(CURRENT_DATE, '02:00:00')
DO
BEGIN
    SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;
    START TRANSACTION;
    DELETE FROM mat_vultretirada;
    -- CALL refazer_ids();
    -- CALL refazer_ids();
    -- CALL reindexar();
    CALL limpar_usuario_editando();
    CALL atualizar_mat_vcomodatos(0);
    CALL atualizar_mat_vretiradas_vultretirada('T', '(0)', 'R', 'S', 0);
    CALL atualizar_mat_vretiradas_vultretirada('T', '(0)', 'U', 'S', 0);
    COMMIT;
    TRUNCATE TABLE excbkp;
    TRUNCATE TABLE atbbkp;
    TRUNCATE TABLE pre_retiradas;
END$$

DELIMITER ;