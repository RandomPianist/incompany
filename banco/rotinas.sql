DELIMITER $$

-- =================================================================================
--  1) ATUALIZAR_MAT_VCOMODATOS
--  Atualiza a visão materializada de comodatos ativos por pessoa.
-- =================================================================================
CREATE PROCEDURE atualizar_mat_vcomodatos(IN p_id_maquina INT)
BEGIN
    DECLARE v_tab_p TEXT DEFAULT 'pessoas';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao atualizar mat_vcomodatos';
    END;

    SET @query = '
        SELECT
            comodatos.id,
            minhas_empresas.id_pessoa,
            comodatos.id_maquina,
            comodatos.travar_estq,
            comodatos.id_empresa

        FROM (
            SELECT
                p.id AS id_pessoa,
                p.id_empresa
            FROM pessoas AS p
            JOIN empresas
                ON p.id_empresa IN (empresas.id, empresas.id_matriz)
            WHERE p.lixeira = 0
              AND empresas.lixeira = 0
        ) AS minhas_empresas
        
        JOIN comodatos
            ON comodatos.id_empresa = minhas_empresas.id_empresa
    ';

    IF p_id_maquina IS NULL OR p_id_maquina = 0 THEN
        SET @query = CONCAT(@query, ' JOIN maquinas ON maquinas.id = comodatos.id_maquina');
    END IF;

    SET @query = CONCAT(@query, ' WHERE (CURDATE() >= comodatos.inicio AND CURDATE() < comodatos.fim) AND ');
    IF p_id_maquina IS NULL OR p_id_maquina = 0 THEN
        SET @query = CONCAT(@query, 'maquinas.lixeira = 0');
    ELSE
        SET @query = CONCAT(@query, 'comodatos.id_maquina = ', p_id_maquina);
    END IF;

    IF p_id_maquina IS NULL OR p_id_maquina = 0 THEN
        SET @delete_sql = 'DELETE FROM mat_vcomodatos';
    ELSE
        SET @delete_sql = CONCAT('DELETE FROM mat_vcomodatos WHERE id_maquina = ', p_id_maquina);
    END IF;

    PREPARE st_del FROM @delete_sql;
    EXECUTE st_del;
    DEALLOCATE PREPARE st_del;

    SET @insert_sql = CONCAT('INSERT INTO mat_vcomodatos ', @query, ' GROUP BY
        comodatos.id,
        minhas_empresas.id_pessoa,
        comodatos.id_maquina,
        comodatos.travar_estq,
        comodatos.id_empresa
    ');
    PREPARE st_ins FROM @insert_sql;
    EXECUTE st_ins;
    DEALLOCATE PREPARE st_ins;
END$$

-- =================================================================================
--  2) ATUALIZAR_MAT_VRETIRADAS_VULTRETIRADA
--  Atualiza as materializadas de retiradas (soma e última data).
-- =================================================================================
CREATE PROCEDURE atualizar_mat_vretiradas_vultretirada(
    IN p_chave CHAR(1),
    IN p_valor TEXT,
    IN p_tipo CHAR(1), -- 'R' = mat_vretiradas ; 'U' = mat_vultretirada
    IN p_apenas_ativos CHAR(1),
    IN p_id_pessoa INT
)
BEGIN
    DECLARE v_target_table VARCHAR(64);
    DECLARE v_pessoas_table VARCHAR(64);
    DECLARE v_where_clause TEXT;

    -- Define a tabela de pessoas a ser usada
    IF p_apenas_ativos = 'S' THEN
        SET v_pessoas_table = 'vativos';
    ELSE
        SET v_pessoas_table = 'pessoas';
    END IF;
    
    -- Define a tabela alvo para a operação
    IF p_tipo = 'R' THEN
        SET v_target_table = 'mat_vretiradas';
    ELSE
        SET v_target_table = 'mat_vultretirada';
    END IF;

    -- =================================================================
    -- PASSO 1: EXECUTAR O DELETE DENTRO DO ESCOPO ESPECIFICADO
    -- =================================================================
    IF p_chave = 'P' THEN
        SET @sql_delete = CONCAT('DELETE FROM ', v_target_table, ' WHERE id_pessoa IN (', p_valor, ')');
    ELSEIF p_chave = 'S' THEN
        SET @sql_delete = CONCAT(
            'DELETE t FROM ', v_target_table, ' AS t
             JOIN ', v_pessoas_table, ' AS p ON p.id = t.id_pessoa
             WHERE p.id_setor IN (', p_valor, ') AND (p.id = ', p_id_pessoa, ' OR ', p_id_pessoa, ' = 0)');
    ELSEIF p_chave = 'M' THEN
        SET @sql_delete = CONCAT(
            'DELETE t FROM ', v_target_table, ' AS t
             JOIN mat_vcomodatos AS mc ON mc.id_pessoa = t.id_pessoa
             WHERE mc.id_maquina IN (', p_valor, ') AND (mc.id_pessoa = ', p_id_pessoa, ' OR ', p_id_pessoa, ' = 0)');
    ELSE
        SET @sql_delete = CONCAT('DELETE FROM ', v_target_table);
    END IF;

    PREPARE st_del FROM @sql_delete;
    EXECUTE st_del;
    DEALLOCATE PREPARE st_del;

    -- =================================================================
    -- PASSO 2: CONSTRUIR A LÓGICA DE FILTRO PARA A SUBQUERY
    -- =================================================================
    IF p_id_pessoa > 0 THEN
      SET v_where_clause = CONCAT(' AND p.id = ', p_id_pessoa);
    ELSEIF p_chave = 'P' THEN
        SET v_where_clause = CONCAT(' AND p.id IN (', p_valor, ')');
    ELSEIF p_chave = 'S' THEN
        SET v_where_clause = CONCAT(' AND p.id_setor IN (', p_valor, ')');
    ELSEIF p_chave = 'M' THEN
        SET v_where_clause = CONCAT(' AND mvc.id_maquina IN (', p_valor, ')');
    ELSE
        SET v_where_clause = '';
    END IF;

    -- =================================================================
    -- PASSO 3: CONSTRUIR A QUERY FONTE (ATRIBUIÇÕES BASE)
    -- =================================================================
    SET @atribuicoes_base_sql = CONCAT('
        (
            SELECT p.id AS id_pessoa, vat.id AS id_atribuicao, prod.id AS id_produto, vat.lixeira, 1 AS grandeza
            FROM vatbreal vat JOIN ', v_pessoas_table, ' p ON p.id = vat.id_pessoa JOIN produtos prod ON prod.cod_externo = vat.cod_produto
            JOIN mat_vcomodatos mvc ON mvc.id_pessoa = p.id JOIN comodatos_produtos cp ON cp.id_comodato = mvc.id AND cp.id_produto = prod.id
            LEFT JOIN users u ON u.id_pessoa = p.id LEFT JOIN excecoes e ON (e.id_setor = p.id_setor OR e.id_pessoa = p.id) AND vat.id = e.id_atribuicao AND e.rascunho = ''S''
            WHERE (u.admin = 0 OR u.id IS NULL OR vat.gerado = 0) AND e.id IS NULL AND cp.lixeira = 0 AND prod.lixeira = 0', v_where_clause, '
            UNION ALL
            SELECT p.id, vat.id, prod.id, vat.lixeira, 2 FROM vatbreal vat JOIN ', v_pessoas_table, ' p ON p.id = vat.id_pessoa JOIN produtos prod ON prod.referencia = vat.referencia
            JOIN mat_vcomodatos mvc ON mvc.id_pessoa = p.id JOIN comodatos_produtos cp ON cp.id_comodato = mvc.id AND cp.id_produto = prod.id
            LEFT JOIN users u ON u.id_pessoa = p.id LEFT JOIN excecoes e ON (e.id_setor = p.id_setor OR e.id_pessoa = p.id) AND vat.id = e.id_atribuicao AND e.rascunho = ''S''
            WHERE (u.admin = 0 OR u.id IS NULL OR vat.gerado = 0) AND e.id IS NULL AND cp.lixeira = 0 AND prod.lixeira = 0', v_where_clause, '
            UNION ALL
            SELECT p.id, vat.id, prod.id, vat.lixeira, 3 FROM vatbreal vat JOIN ', v_pessoas_table, ' p ON p.id_setor = vat.id_setor JOIN produtos prod ON prod.cod_externo = vat.cod_produto
            JOIN mat_vcomodatos mvc ON mvc.id_pessoa = p.id JOIN comodatos_produtos cp ON cp.id_comodato = mvc.id AND cp.id_produto = prod.id
            LEFT JOIN users u ON u.id_pessoa = p.id LEFT JOIN excecoes e ON (e.id_setor = p.id_setor OR e.id_pessoa = p.id) AND vat.id = e.id_atribuicao AND e.rascunho = ''S''
            WHERE (u.admin = 0 OR u.id IS NULL OR vat.gerado = 0) AND e.id IS NULL AND cp.lixeira = 0 AND prod.lixeira = 0', v_where_clause, '
            UNION ALL
            SELECT p.id, vat.id, prod.id, vat.lixeira, 4 FROM vatbreal vat JOIN ', v_pessoas_table, ' p ON p.id_setor = vat.id_setor JOIN produtos prod ON prod.referencia = vat.referencia
            JOIN mat_vcomodatos mvc ON mvc.id_pessoa = p.id JOIN comodatos_produtos cp ON cp.id_comodato = mvc.id AND cp.id_produto = prod.id
            LEFT JOIN users u ON u.id_pessoa = p.id LEFT JOIN excecoes e ON (e.id_setor = p.id_setor OR e.id_pessoa = p.id) AND vat.id = e.id_atribuicao AND e.rascunho = ''S''
            WHERE (u.admin = 0 OR u.id IS NULL OR vat.gerado = 0) AND e.id IS NULL AND cp.lixeira = 0 AND prod.lixeira = 0', v_where_clause, '
            UNION ALL
            SELECT p.id, vat.id, prod.id, vat.lixeira, 5 FROM vatbreal vat JOIN mat_vcomodatos mvc ON mvc.id_maquina = vat.id_maquina JOIN ', v_pessoas_table, ' p ON p.id = mvc.id_pessoa JOIN produtos prod ON prod.cod_externo = vat.cod_produto
            JOIN comodatos_produtos cp ON cp.id_comodato = mvc.id AND cp.id_produto = prod.id
            LEFT JOIN users u ON u.id_pessoa = p.id LEFT JOIN excecoes e ON (e.id_setor = p.id_setor OR e.id_pessoa = p.id) AND vat.id = e.id_atribuicao AND e.rascunho = ''S''
            WHERE (u.admin = 0 OR u.id IS NULL OR vat.gerado = 0) AND e.id IS NULL AND cp.lixeira = 0 AND prod.lixeira = 0', v_where_clause, '
            UNION ALL
            SELECT p.id, vat.id, prod.id, vat.lixeira, 6 FROM vatbreal vat JOIN mat_vcomodatos mvc ON mvc.id_maquina = vat.id_maquina JOIN ', v_pessoas_table, ' p ON p.id = mvc.id_pessoa JOIN produtos prod ON prod.referencia = vat.referencia
            JOIN comodatos_produtos cp ON cp.id_comodato = mvc.id AND cp.id_produto = prod.id
            LEFT JOIN users u ON u.id_pessoa = p.id LEFT JOIN excecoes e ON (e.id_setor = p.id_setor OR e.id_pessoa = p.id) AND vat.id = e.id_atribuicao AND e.rascunho = ''S''
            WHERE (u.admin = 0 OR u.id IS NULL OR vat.gerado = 0) AND e.id IS NULL AND cp.lixeira = 0 AND prod.lixeira = 0', v_where_clause, '
        )
    ');

    -- =================================================================
    -- PASSO 4: EXECUTAR O INSERT FINAL
    -- =================================================================
    IF p_tipo = 'R' THEN
        SET @sql_insert = CONCAT('
            INSERT INTO mat_vretiradas (id_pessoa, id_atribuicao, id_setor, valor)
            SELECT
                atrib.id_pessoa, atrib.id_atribuicao, p.id_setor, IFNULL(SUM(ret.qtd), 0) AS valor
            FROM
                (
                    SELECT ab.id_pessoa, ab.id_atribuicao, ab.id_produto
                    FROM ', @atribuicoes_base_sql, ' AS ab
                    JOIN (
                        SELECT id_pessoa, id_produto, MIN(grandeza) AS min_grandeza
                        FROM ', @atribuicoes_base_sql, ' AS sub_ab
                        WHERE sub_ab.lixeira = 0 GROUP BY id_pessoa, id_produto
                    ) AS prio ON ab.id_pessoa = prio.id_pessoa AND ab.id_produto = prio.id_produto AND ab.grandeza = prio.min_grandeza
                ) AS atrib
            JOIN vatbreal v ON v.id = atrib.id_atribuicao
            JOIN ', v_pessoas_table, ' AS p ON p.id = atrib.id_pessoa
            JOIN retiradas ret ON ret.id_atribuicao = v.id AND ret.id_pessoa = p.id AND p.id_empresa IN (0, ret.id_empresa)
                                  AND ret.data >= v.data AND ret.data > DATE_SUB(CURDATE(), INTERVAL v.validade DAY) AND ret.id_supervisor IS NULL
            GROUP BY atrib.id_pessoa, atrib.id_atribuicao, p.id_setor
        ');
    ELSE
        SET @sql_insert = CONCAT('
            INSERT INTO mat_vultretirada (id_pessoa, id_atribuicao, id_setor, data)
            SELECT
                atrib.id_pessoa, atrib.id_atribuicao, p.id_setor, MAX(ret.data) AS data
            FROM
                (
                    SELECT
                        prio_atb.id_pessoa, prio_atb.id_atribuicao, assoc_atb.id_atribuicao AS id_associado
                    FROM
                        (
                            SELECT ab.id_pessoa, ab.id_atribuicao, ab.id_produto
                            FROM ', @atribuicoes_base_sql, ' AS ab
                            JOIN (
                                SELECT id_pessoa, id_produto, MIN(grandeza) AS min_grandeza
                                FROM ', @atribuicoes_base_sql, ' AS sub_ab
                                WHERE sub_ab.lixeira = 0 GROUP BY id_pessoa, id_produto
                            ) AS prio ON ab.id_pessoa = prio.id_pessoa AND ab.id_produto = prio.id_produto AND ab.grandeza = prio.min_grandeza
                        ) AS prio_atb
                    JOIN ', @atribuicoes_base_sql, ' AS assoc_atb ON prio_atb.id_pessoa = assoc_atb.id_pessoa AND prio_atb.id_produto = assoc_atb.id_produto
                    GROUP BY prio_atb.id_pessoa, prio_atb.id_atribuicao, assoc_atb.id_atribuicao
                ) AS atrib
            JOIN vatbreal v_assoc ON v_assoc.id = atrib.id_associado
            JOIN ', v_pessoas_table, ' AS p ON p.id = atrib.id_pessoa
            JOIN retiradas ret ON ret.id_atribuicao = v_assoc.id AND ret.id_pessoa = p.id AND p.id_empresa IN (0, ret.id_empresa) AND ret.id_supervisor IS NULL
            GROUP BY atrib.id_pessoa, atrib.id_atribuicao, p.id_setor
        ');
    END IF;

    PREPARE st_ins FROM @sql_insert;
    EXECUTE st_ins;
    DEALLOCATE PREPARE st_ins;

END$$

-- =================================================================================
--  3) EXCLUIR_ATRIBUICAO_SEM_RETIRADA
--  Limpa atribuições marcadas como lixeira que não possuem retiradas.
-- =================================================================================
CREATE PROCEDURE excluir_atribuicao_sem_retirada()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        RESIGNAL;
    END;

    DELETE a
    FROM atribuicoes a
    LEFT JOIN retiradas r ON r.id_atribuicao = a.id
    WHERE r.id IS NULL
      AND a.lixeira = 1 AND a.gerado = 0;
    DELETE l
    FROM log l
    LEFT JOIN atribuicoes a2 ON a2.id = l.fk
    WHERE a2.id IS NULL
      AND l.tabela = 'atribuicoes';
END $$

-- =================================================================================
--  4) LIMPAR_USUARIO_EDITANDO
--  Reseta o campo 'id_usuario_editando' em várias tabelas.
-- =================================================================================
CREATE PROCEDURE limpar_usuario_editando()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        RESIGNAL;
    END;

    UPDATE categorias SET id_usuario_editando = 0;
    UPDATE empresas SET id_usuario_editando = 0;
    UPDATE pessoas SET id_usuario_editando = 0;
    UPDATE produtos SET id_usuario_editando = 0;
    UPDATE setores SET id_usuario_editando = 0;

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
    DELETE FROM excbkp;
    DELETE FROM atbbkp;
END $$

-- =================================================================================
--  5) REFAZER_IDS
--  Recria os IDs de várias tabelas para remover gaps e atualiza as FKs.
-- =================================================================================
CREATE PROCEDURE refazer_ids()
BEGIN
    -- ------------------------------------------------------------------
    -- DECLARAÇÕES
    -- ------------------------------------------------------------------
    DECLARE v_lista_tabelas TEXT DEFAULT 'atribuicoes,categorias,comodatos,comodatos_produtos,estoque,excecoes,log,maquinas,permissoes,pessoas,previas,retiradas,setores,solicitacoes,solicitacoes_produtos,dedos';
    DECLARE v_tabela_atual VARCHAR(64);
    DECLARE v_tabelas_restantes TEXT;
    DECLARE v_delimitador_pos INT;
    DECLARE v_colunas TEXT;

    DECLARE v_done INT DEFAULT FALSE;
    DECLARE v_main_table VARCHAR(64);
    DECLARE v_fk_column VARCHAR(64);
    DECLARE v_aux_table VARCHAR(64);

    DECLARE cur_fk CURSOR FOR SELECT main_table, fk_column, aux_table FROM fk_configuracao_updates;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        RESIGNAL;
    END;

    -- ------------------------------------------------------------------
    -- ETAPA 0: Criar e popular fk_configuracao_updates (temporária)
    -- ------------------------------------------------------------------
    CREATE TEMPORARY TABLE IF NOT EXISTS fk_configuracao_updates (
        main_table VARCHAR(64),
        fk_column VARCHAR(64),
        aux_table VARCHAR(64)
    );
    TRUNCATE TABLE fk_configuracao_updates;

    INSERT INTO fk_configuracao_updates (main_table, fk_column, aux_table) VALUES
        ('atribuicoes', 'id_pessoa', 'pessoas'),
        ('atribuicoes', 'id_setor', 'setores'),
        ('atribuicoes', 'id_maquina', 'maquinas'),
        ('comodatos', 'id_maquina', 'maquinas'),
        ('comodatos_produtos', 'id_comodato', 'comodatos'),
        ('comodatos_produtos', 'id_produto', 'produtos'),
        ('estoque', 'id_cp', 'comodatos_produtos'),
        ('excecoes', 'id_atribuicao', 'atribuicoes'),
        ('excecoes', 'id_pessoa', 'pessoas'),
        ('excecoes', 'id_setor', 'setores'),
        ('permissoes', 'id_setor', 'setores'),
        ('pessoas', 'id_setor', 'setores'),
        ('previas', 'id_comodato', 'comodatos'),
        ('produtos', 'id_categoria', 'categorias'),
        ('retiradas', 'id_atribuicao', 'atribuicoes'),
        ('retiradas', 'id_comodato', 'comodatos'),
        ('retiradas', 'id_pessoa', 'pessoas'),
        ('retiradas', 'id_supervisor', 'pessoas'),
        ('retiradas', 'id_setor', 'setores'),
        ('solicitacoes', 'id_comodato', 'comodatos'),
        ('solicitacoes_produtos', 'id_solicitacao', 'solicitacoes'),
        ('dedos', 'id_pessoa', 'pessoas');

    -- ------------------------------------------------------------------
    -- ETAPA 1: Criar as novas tabelas (sufixo '2')
    -- ------------------------------------------------------------------
    SET v_tabelas_restantes = v_lista_tabelas;
    WHILE v_tabelas_restantes IS NOT NULL AND v_tabelas_restantes != '' DO
        SET v_delimitador_pos = INSTR(v_tabelas_restantes, ',');
        IF v_delimitador_pos > 0 THEN
            SET v_tabela_atual = SUBSTRING(v_tabelas_restantes, 1, v_delimitador_pos - 1);
            SET v_tabelas_restantes = SUBSTRING(v_tabelas_restantes, v_delimitador_pos + 1);
        ELSE
            SET v_tabela_atual = v_tabelas_restantes;
            SET v_tabelas_restantes = NULL;
        END IF;

        SELECT GROUP_CONCAT(CONCAT('`', column_name, '`') ORDER BY ordinal_position)
            INTO v_colunas
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = v_tabela_atual
          AND column_name != 'id';

        -- DROP se existir (sua versão mais recente adicionou isso)
        SET @sql = CONCAT('DROP TABLE IF EXISTS `', v_tabela_atual, '2`');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('
            CREATE TABLE `', v_tabela_atual, '2` AS 
                SELECT
                    (@row_number:=@row_number+1) AS id,
                    `id` AS id_antigo',
                    IF(v_colunas IS NULL OR v_colunas = '', '', CONCAT(', ', v_colunas)),
                ' FROM `', v_tabela_atual, '`,
                    (SELECT @row_number:=0) AS t
        ');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('ALTER TABLE `', v_tabela_atual, '2` ADD PRIMARY KEY(id)');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('ALTER TABLE `', v_tabela_atual, '2` CHANGE id id INT NOT NULL AUTO_INCREMENT');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END WHILE;

    -- ------------------------------------------------------------------
    -- ETAPA 2.1: Atualizar chaves estrangeiras dinamicamente usando cursor
    -- ------------------------------------------------------------------
    SET v_done = FALSE;
    OPEN cur_fk;
    fk_update_loop: LOOP
        FETCH cur_fk INTO v_main_table, v_fk_column, v_aux_table;
        IF v_done THEN
            LEAVE fk_update_loop;
        END IF;

        SET @sql = CONCAT('
            UPDATE `', v_main_table, '2` AS main
            JOIN `', v_aux_table, '2` AS aux
                ON aux.id_antigo = main.`', v_fk_column, '`
            SET main.`', v_fk_column, '` = aux.id
        ');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur_fk;

    -- ------------------------------------------------------------------
    -- ETAPA 2.2: Tratar caso especial da tabela `log`
    -- ------------------------------------------------------------------
    SET v_tabelas_restantes = v_lista_tabelas;
    WHILE v_tabelas_restantes IS NOT NULL AND v_tabelas_restantes != '' DO
        SET v_delimitador_pos = INSTR(v_tabelas_restantes, ',');
        IF v_delimitador_pos > 0 THEN
            SET v_tabela_atual = SUBSTRING(v_tabelas_restantes, 1, v_delimitador_pos - 1);
            SET v_tabelas_restantes = SUBSTRING(v_tabelas_restantes, v_delimitador_pos + 1);
        ELSE
            SET v_tabela_atual = v_tabelas_restantes;
            SET v_tabelas_restantes = NULL;
        END IF;

        IF v_tabela_atual != 'log' THEN
            SET @sql = CONCAT('
                UPDATE log2 AS main
                JOIN `', v_tabela_atual, '2` AS aux
                    ON aux.id_antigo = main.fk
                SET main.fk = aux.id
                WHERE main.tabela = ''', v_tabela_atual, '''
            ');
            PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        END IF;
    END WHILE;

    SET @sql = '
        UPDATE log2 AS main
        JOIN pessoas2 AS aux
            ON aux.id_antigo = main.id_pessoa
        SET main.id_pessoa = aux.id
    ';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = '
        UPDATE users AS main
        JOIN pessoas2 AS aux
            ON aux.id_antigo = main.id_pessoa
        SET main.id_pessoa = aux.id
    ';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = '
        UPDATE usrbkp AS main
        JOIN pessoas2 AS aux
            ON aux.id_antigo = main.id_pessoa
        SET main.id_pessoa = aux.id
    ';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    -- ------------------------------------------------------------------
    -- ETAPA 3: Remover tabelas antigas e renomear as novas
    -- ------------------------------------------------------------------
    SET v_tabelas_restantes = v_lista_tabelas;
    WHILE v_tabelas_restantes IS NOT NULL AND v_tabelas_restantes != '' DO
        SET v_delimitador_pos = INSTR(v_tabelas_restantes, ',');
        IF v_delimitador_pos > 0 THEN
            SET v_tabela_atual = SUBSTRING(v_tabelas_restantes, 1, v_delimitador_pos - 1);
            SET v_tabelas_restantes = SUBSTRING(v_tabelas_restantes, v_delimitador_pos + 1);
        ELSE
            SET v_tabela_atual = v_tabelas_restantes;
            SET v_tabelas_restantes = NULL;
        END IF;

        SET @sql = CONCAT('DROP TABLE `', v_tabela_atual, '`');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('RENAME TABLE `', v_tabela_atual, '2` TO `', v_tabela_atual, '`');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('ALTER TABLE `', v_tabela_atual, '` DROP COLUMN `id_antigo`');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END WHILE;

    -- ------------------------------------------------------------------
    -- ETAPA 4: Remover registros órfãos
    -- ------------------------------------------------------------------
    SET v_done = FALSE;
    OPEN cur_fk;
    fk_update_loop: LOOP
        FETCH cur_fk INTO v_main_table, v_fk_column, v_aux_table;
        IF v_done THEN
            LEAVE fk_update_loop;
        END IF;

        SET @sql = CONCAT('
            CREATE TABLE `', v_main_table, '_temp` AS
                SELECT `', v_main_table, '`.`id`
                FROM `', v_main_table, '`
                LEFT JOIN `', v_aux_table, '` AS aux
                    ON aux.id = `', v_main_table, '`.`', v_fk_column, '`
                WHERE IFNULL(`', v_main_table, '`.`', v_fk_column, '`,0) <> 0
                AND aux.id IS NULL
        ');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('
            DELETE `', v_main_table, '`
            FROM `', v_main_table, '`
            JOIN `', v_main_table, '_temp`
                ON `', v_main_table, '_temp`.id = `', v_main_table, '`.id
        ');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('
            DELETE FROM log
            WHERE log.tabela = ''', v_main_table, ''' AND log.fk IN (
                SELECT id
                FROM `', v_main_table, '_temp`
            )
        ');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('DROP TABLE `', v_main_table, '_temp`');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur_fk;

END$$

-- =================================================================================
--  6) REINDEXAR
--  Remove e recria os índices do banco de dados para otimização.
-- =================================================================================
CREATE PROCEDURE reindexar()
BEGIN
    -- Handler para ignorar o erro "índice não existe" (código 1091) e continuar
    DECLARE CONTINUE HANDLER FOR 1091 BEGIN END;
    
    -- Handler geral para reverter a transação em caso de outros erros graves
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        -- Se precisar de transações, coloque START TRANSACTION no início e ROLLBACK aqui.
        -- Como DROP/CREATE INDEX causam commits implícitos, a transação tem efeito limitado.
        RESIGNAL;
    END;

    -- --- ETAPA 1: Removendo os índices existentes (com a sintaxe correta) ---
    DROP INDEX idx_pessoas_id_empresa_lixeira ON pessoas;
    DROP INDEX idx_pessoas_id_setor_lixeira ON pessoas;
    DROP INDEX idx_maquinas_lixeira ON maquinas;
    DROP INDEX idx_produtos_cod_lixeira ON produtos;
    DROP INDEX idx_produtos_ref_lixeira ON produtos;
    DROP INDEX idx_comodatos_id_empresa_inicio_fim ON comodatos;
    DROP INDEX idx_comodatos_id_maquina ON comodatos;
    DROP INDEX idx_mp_id_comodato ON comodatos_produtos;
    DROP INDEX idx_mp_id_produto ON comodatos_produtos;
    DROP INDEX idx_estoque_id_cp ON estoque;
    DROP INDEX idx_atr_created_date ON atribuicoes;
    DROP INDEX idx_atr_id_pessoa_cod_lixeira ON atribuicoes;
    DROP INDEX idx_atr_id_pessoa_ref_lixeira ON atribuicoes;
    DROP INDEX idx_atr_id_setor_cod_lixeira ON atribuicoes;
    DROP INDEX idx_atr_id_setor_ref_lixeira ON atribuicoes;
    DROP INDEX idx_atr_id_maquina_cod_lixeira ON atribuicoes;
    DROP INDEX idx_atr_id_maquina_ref_lixeira ON atribuicoes;
    DROP INDEX idx_atr_cod_ref ON atribuicoes;
    DROP INDEX idx_ret_atr_pessoa_empresa_data ON retiradas;
    DROP INDEX idx_ret_atr_pessoa_empresa_data_sup ON retiradas;
    DROP INDEX idx_pre_ret_id_pessoa ON pre_retiradas;
    DROP INDEX idx_pre_ret_id_produto ON pre_retiradas;

    -- --- ETAPA 2: Criando os índices novamente ---
    CREATE INDEX idx_pessoas_id_empresa_lixeira ON pessoas(id_empresa, lixeira);
    CREATE INDEX idx_pessoas_id_setor_lixeira ON pessoas(id_setor, lixeira);
    CREATE INDEX idx_maquinas_lixeira ON maquinas(lixeira);
    CREATE INDEX idx_produtos_cod_lixeira ON produtos(cod_externo, lixeira);
    CREATE INDEX idx_produtos_ref_lixeira ON produtos(referencia, lixeira);
    CREATE INDEX idx_comodatos_id_empresa_inicio_fim ON comodatos(id_empresa, inicio, fim);
    CREATE INDEX idx_comodatos_id_maquina ON comodatos(id_maquina);
    CREATE INDEX idx_mp_id_comodato ON comodatos_produtos(id_comodato);
    CREATE INDEX idx_mp_id_produto ON comodatos_produtos(id_produto);
    CREATE INDEX idx_estoque_id_cp ON estoque(id_cp);
    CREATE INDEX idx_atr_created_date ON atribuicoes(data);
    CREATE INDEX idx_atr_id_pessoa_cod_lixeira ON atribuicoes(id_pessoa, cod_produto, lixeira);
    CREATE INDEX idx_atr_id_pessoa_ref_lixeira ON atribuicoes(id_pessoa, referencia, lixeira);
    CREATE INDEX idx_atr_id_setor_cod_lixeira ON atribuicoes(id_setor, cod_produto, lixeira);
    CREATE INDEX idx_atr_id_setor_ref_lixeira ON atribuicoes(id_setor, referencia, lixeira);
    CREATE INDEX idx_atr_id_maquina_cod_lixeira ON atribuicoes(id_maquina, cod_produto, lixeira);
    CREATE INDEX idx_atr_id_maquina_ref_lixeira ON atribuicoes(id_maquina, referencia, lixeira);
    CREATE INDEX idx_atr_cod_ref ON atribuicoes(cod_produto, referencia);
    CREATE INDEX idx_ret_atr_pessoa_empresa_data ON retiradas(id_atribuicao, id_pessoa, id_empresa, data);
    CREATE INDEX idx_ret_atr_pessoa_empresa_data_sup ON retiradas(id_atribuicao, id_pessoa, id_empresa, data, id_supervisor);
    CREATE INDEX idx_pre_ret_id_pessoa ON pre_retiradas(id_pessoa);
    CREATE INDEX idx_pre_ret_id_produto ON pre_retiradas(id_produto);

END$$

DELIMITER ;