DELIMITER $$

/* -----------------------------------------
   1) atualizar_mat_vcomodatos
------------------------------------------ */
CREATE PROCEDURE atualizar_mat_vcomodatos(IN p_id_maquina INT)
BEGIN
    DECLARE v_tab_p TEXT DEFAULT 'pessoas';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao atualizar mat_vcomodatos';
    END;

    SET @query =
        'SELECT
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
            ON comodatos.id_empresa = minhas_empresas.id_empresa';

    IF p_id_maquina IS NULL OR p_id_maquina = 0 THEN
        SET @query = CONCAT(@query, ' JOIN maquinas ON maquinas.id = comodatos.id_maquina');
    END IF;

    SET @query = CONCAT(@query, ' WHERE (CURDATE() >= comodatos.inicio AND CURDATE() < comodatos.fim) AND ');
    IF p_id_maquina IS NULL OR p_id_maquina = 0 THEN
        SET @query = CONCAT(@query, 'maquinas.lixeira = 0');
    ELSE
        SET @query = CONCAT(@query, 'comodatos.id_maquina = ', p_id_maquina);
    END IF;

    START TRANSACTION;

    IF p_id_maquina IS NULL OR p_id_maquina = 0 THEN
        SET @delete_sql = 'DELETE FROM mat_vcomodatos';
    ELSE
        SET @delete_sql = CONCAT('DELETE FROM mat_vcomodatos WHERE id_maquina = ', p_id_maquina);
    END IF;

    PREPARE st_del FROM @delete_sql;
    EXECUTE st_del;
    DEALLOCATE PREPARE st_del;

    SET @insert_sql = CONCAT('INSERT INTO mat_vcomodatos ', @query);
    PREPARE st_ins FROM @insert_sql;
    EXECUTE st_ins;
    DEALLOCATE PREPARE st_ins;

    COMMIT;
END$$

/* -----------------------------------------
   2) atualizar_mat_vatbaux
------------------------------------------ */
CREATE PROCEDURE atualizar_mat_vatbaux(IN p_chave CHAR(1), IN p_valor TEXT, IN apenas_ativos CHAR(1))
BEGIN
    DECLARE v_tab_p TEXT DEFAULT 'pessoas';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao atualizar mat_vatbaux';
    END;

    IF apenas_ativos = 'S' THEN
        SET v_tab_p = 'vativos';
    END IF;

    SET @query = CONCAT(
    'SELECT
        tab.*,
        vatbreal.id_setor,
        vatbreal.lixeira
    FROM (
        SELECT 
            p.id AS id_pessoa, 
            vatbreal.id AS id_atribuicao, 
            vatbreal.cod_produto AS cod, 
            IFNULL(produtos.referencia, '''') AS ref,
            ''PP'' AS src 
        FROM vatbreal 
        JOIN ', v_tab_p, ' AS p
            ON p.id = vatbreal.id_pessoa 
        JOIN produtos
            ON produtos.cod_externo = vatbreal.cod_produto 
        JOIN mat_vcomodatos
            ON mat_vcomodatos.id_pessoa = p.id
        JOIN comodatos_produtos AS cp
            ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id
        WHERE cp.lixeira = 0

        UNION ALL 
        SELECT 
            p.id AS id_pessoa, 
            vatbreal.id AS id_atribuicao, 
            IFNULL(produtos.cod_externo, '''') AS cod, 
            vatbreal.referencia AS ref,
            ''PR'' AS src 
        FROM vatbreal 
        JOIN ', v_tab_p, ' AS p
            ON p.id = vatbreal.id_pessoa 
        JOIN produtos
            ON produtos.referencia = vatbreal.referencia
        JOIN mat_vcomodatos
            ON mat_vcomodatos.id_pessoa = p.id
        JOIN comodatos_produtos AS cp
            ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id
        WHERE cp.lixeira = 0

        UNION ALL 
        SELECT 
            p.id AS id_pessoa, 
            vatbreal.id AS id_atribuicao, 
            vatbreal.cod_produto AS cod, 
            IFNULL(produtos.referencia, '''') AS ref,
            ''SP'' AS src 
        FROM vatbreal 
        JOIN ', v_tab_p, ' AS p
            ON p.id_setor = vatbreal.id_setor
        JOIN produtos
            ON produtos.cod_externo = vatbreal.cod_produto
        JOIN mat_vcomodatos
            ON mat_vcomodatos.id_pessoa = p.id
        JOIN comodatos_produtos AS cp
            ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id
        WHERE cp.lixeira = 0

        UNION ALL 
        SELECT 
            p.id AS id_pessoa, 
            vatbreal.id AS id_atribuicao, 
            IFNULL(produtos.cod_externo, '''') AS cod, 
            vatbreal.referencia AS ref,
            ''SR'' AS src 
        FROM vatbreal 
        JOIN ', v_tab_p, ' AS p
            ON p.id_setor = vatbreal.id_setor 
        JOIN produtos
            ON produtos.referencia = vatbreal.referencia
        JOIN mat_vcomodatos
            ON mat_vcomodatos.id_pessoa = p.id
        JOIN comodatos_produtos AS cp
            ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id
        WHERE cp.lixeira = 0

        UNION ALL 
        SELECT 
            p.id AS id_pessoa, 
            vatbreal.id AS id_atribuicao, 
            vatbreal.cod_produto AS cod, 
            IFNULL(produtos.referencia, '''') AS ref,
            ''MP'' AS src
        FROM vatbreal 
        JOIN mat_vcomodatos
            ON mat_vcomodatos.id_maquina = vatbreal.id_maquina
        JOIN ', v_tab_p, ' AS p
            ON p.id = mat_vcomodatos.id_pessoa 
        JOIN produtos
            ON produtos.cod_externo = vatbreal.cod_produto
        JOIN comodatos_produtos AS cp
            ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id
        WHERE cp.lixeira = 0

        UNION ALL 
        SELECT 
            p.id AS id_pessoa, 
            vatbreal.id AS id_atribuicao, 
            IFNULL(produtos.cod_externo, '''') AS cod, 
            vatbreal.referencia AS ref,
            ''MR'' AS src 
        FROM vatbreal 
        JOIN mat_vcomodatos
            ON mat_vcomodatos.id_maquina = vatbreal.id_maquina 
        JOIN ', v_tab_p, ' AS p
            ON p.id = mat_vcomodatos.id_pessoa 
        JOIN produtos
            ON produtos.referencia = vatbreal.referencia
        JOIN comodatos_produtos AS cp
            ON cp.id_comodato = mat_vcomodatos.id AND cp.id_produto = produtos.id
        WHERE cp.lixeira = 0
    ) AS tab
    JOIN produtos
        ON (produtos.cod_externo = tab.cod AND tab.src LIKE ''%P'')
            OR (produtos.referencia = tab.ref AND tab.src LIKE ''%R'') 
    JOIN vatbreal
        ON vatbreal.id = tab.id_atribuicao');

    IF p_chave <> 'M' AND p_chave <> 'P' THEN
        SET @query = CONCAT(@query, ' JOIN ', v_tab_p, ' AS p ON p.id = tab.id_pessoa WHERE ');
        IF p_chave = 'T' THEN
            SET @query = CONCAT(@query, ' 1=1');
        ELSEIF p_chave = 'S' THEN
            SET @query = CONCAT(@query, ' p.id_setor IN (', p_valor, ')');
        ELSE
            SET @query = CONCAT(@query, ' 1=1');
        END IF;
    ELSEIF p_chave = 'M' THEN
        SET @query = CONCAT(@query, ' JOIN mat_vcomodatos ON mat_vcomodatos.id_pessoa = tab.id_pessoa WHERE mat_vcomodatos.id_maquina IN (', p_valor, ')');
    ELSE
        SET @query = CONCAT(@query, ' WHERE tab.id_pessoa IN (', p_valor, ')');
    END IF;

    SET @query = CONCAT(@query, ' AND produtos.lixeira = 0 GROUP BY tab.id_pessoa, tab.id_atribuicao, tab.cod, tab.ref, tab.src, vatbreal.id_setor, vatbreal.lixeira');

    START TRANSACTION;

    IF p_chave = 'M' THEN
        SET @delete_sql = CONCAT(
            'DELETE mat_vatbaux
             FROM mat_vatbaux
             JOIN mat_vcomodatos
                ON mat_vcomodatos.id_pessoa = mat_vatbaux.id_pessoa
             WHERE mat_vcomodatos.id_maquina IN (', p_valor, ')'
        );
    ELSEIF p_chave = 'S' OR p_chave = 'P' THEN
        SET @delete_sql = CONCAT('DELETE FROM mat_vatbaux WHERE ', IF(p_chave = 'S', 'id_setor', 'id_pessoa'), ' IN (', p_valor, ')');
    ELSE
        SET @delete_sql = 'DELETE FROM mat_vatbaux';
    END IF;

    PREPARE st_del2 FROM @delete_sql;
    EXECUTE st_del2;
    DEALLOCATE PREPARE st_del2;

    SET @insert_sql = CONCAT('INSERT INTO mat_vatbaux ', @query);
    PREPARE st_ins2 FROM @insert_sql;
    EXECUTE st_ins2;
    DEALLOCATE PREPARE st_ins2;

    SET @delete_sql2 = '
        DELETE mat_vatbaux
        FROM mat_vatbaux
        JOIN excecoes
            ON (excecoes.id_setor = mat_vatbaux.id_setor OR excecoes.id_pessoa = mat_vatbaux.id_pessoa)
                AND mat_vatbaux.id_atribuicao = excecoes.id_atribuicao
        WHERE excecoes.lixeira = 0
          AND excecoes.rascunho = ''S''
    ';

    PREPARE st_del3 FROM @delete_sql2;
    EXECUTE st_del3;
    DEALLOCATE PREPARE st_del3;

    SET @delete_sql3 = '
        DELETE mat_vatbaux
        FROM mat_vatbaux
        JOIN users
            ON users.id_pessoa = mat_vatbaux.id_pessoa
        JOIN vatbreal
            ON vatbreal.id = mat_vatbaux.id_atribuicao
        WHERE vatbreal.gerado = 1 AND users.admin = 1
    ';

    PREPARE st_del4 FROM @delete_sql3;
    EXECUTE st_del4;
    DEALLOCATE PREPARE st_del4;

    COMMIT;
END$$

/* -----------------------------------------
   3) atualizar_mat_vatribuicoes
------------------------------------------ */
CREATE PROCEDURE atualizar_mat_vatribuicoes(IN p_chave CHAR(1), IN p_valor TEXT, IN apenas_ativos CHAR(1))
BEGIN
    DECLARE v_join TEXT DEFAULT '';
    DECLARE v_where TEXT DEFAULT '1';
    DECLARE v_base_select TEXT DEFAULT '';
    DECLARE v_blocks TEXT DEFAULT '';
    DECLARE v_prev_notexists TEXT DEFAULT '';
    DECLARE v_prev_for_mr TEXT DEFAULT '';
    DECLARE v_tab_p TEXT DEFAULT 'pessoas';

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao atualizar mat_vatribuicoes';
    END;

    IF apenas_ativos = 'S' THEN
        SET v_tab_p = 'vativos';
    END IF;

    -- join / filtro por chave
    IF p_chave = 'M' THEN
        SET v_join = 'JOIN mat_vcomodatos ON mat_vcomodatos.id_pessoa = x.id_pessoa';
        SET v_where = CONCAT('mat_vcomodatos.id_maquina IN (', p_valor, ')');
    ELSEIF p_chave = 'S' THEN
        SET v_join = CONCAT('JOIN ', v_tab_p, ' AS p ON p.id = x.id_pessoa');
        SET v_where = CONCAT('p.id_setor IN (', p_valor, ')');
    ELSEIF p_chave = 'P' THEN
        SET v_where = CONCAT('x.id_pessoa IN (', p_valor, ')');
    END IF;

    -- parte comum do SELECT (x, join, left join, e filtro base x.lixeira = 0)
    SET v_base_select = CONCAT(
        'SELECT x.id_pessoa, x.id_atribuicao, filho.id_atribuicao AS id_associado
         FROM mat_vatbaux AS x ',
         v_join,
        ' LEFT JOIN mat_vatbaux AS filho
             ON ((x.ref = filho.ref AND x.ref <> '''') OR x.cod = filho.cod)
                AND x.id_pessoa = filho.id_pessoa
         WHERE x.lixeira = 0 AND '
    );

    -- PP: sem condições NOT EXISTS adicionais
    SET v_blocks = CONCAT(v_base_select, 'x.src = ''PP'' AND ', v_where);

    -- PR: acrescenta NOT EXISTS(pp)
    SET v_prev_notexists = CONCAT(
        'NOT EXISTS (SELECT 1 FROM mat_vatbaux mv FORCE INDEX (idx_mat_vatbaux_pp)
                      WHERE mv.id_pessoa = x.id_pessoa
                        AND mv.cod = x.cod
                        AND mv.lixeira = 0
                        AND mv.src = ''PP'')'
    );
    SET v_blocks = CONCAT(v_blocks,
                          ' UNION ALL ',
                          v_base_select, v_prev_notexists, ' AND x.src = ''PR'' AND ', v_where);

    -- SP: acumula NOT EXISTS(pp) + NOT EXISTS(pr)
    SET v_prev_notexists = CONCAT(
        v_prev_notexists,
        ' AND NOT EXISTS (SELECT 1 FROM mat_vatbaux mv FORCE INDEX (idx_mat_vatbaux_pr)
                           WHERE mv.id_pessoa = x.id_pessoa
                             AND mv.ref = x.ref
                             AND mv.lixeira = 0
                             AND mv.src = ''PR'')'
    );
    SET v_blocks = CONCAT(v_blocks,
                          ' UNION ALL ',
                          v_base_select, v_prev_notexists, ' AND x.src = ''SP'' AND ', v_where);

    -- SR: adiciona NOT EXISTS(sp) (note que idx_mat_vatbaux_sp usa id_setor + cod)
    SET v_prev_notexists = CONCAT(
        v_prev_notexists,
        ' AND NOT EXISTS (SELECT 1 FROM mat_vatbaux mv FORCE INDEX (idx_mat_vatbaux_sp)
                           WHERE mv.id_setor = x.id_setor
                             AND mv.cod = x.cod
                             AND mv.lixeira = 0
                             AND mv.src = ''SP'')'
    );
    SET v_blocks = CONCAT(v_blocks,
                          ' UNION ALL ',
                          v_base_select, v_prev_notexists, ' AND x.src = ''SR'' AND ', v_where);

    -- MR: acrescenta NOT EXISTS(sr) e o NOT EXISTS com vatbreal / mat_vcomodatos / produtos
    SET v_prev_notexists = CONCAT(
        v_prev_notexists,
        ' AND NOT EXISTS (SELECT 1 FROM mat_vatbaux mv FORCE INDEX (idx_mat_vatbaux_sr)
                           WHERE mv.id_setor = x.id_setor
                             AND mv.ref = x.ref
                             AND mv.lixeira = 0
                             AND mv.src = ''SR'')'
    );

    SET v_prev_for_mr = CONCAT(
        v_prev_notexists,
        ' AND NOT EXISTS (
             SELECT 1
             FROM vatbreal a2
             JOIN mat_vcomodatos v ON v.id_maquina = a2.id_maquina
             JOIN produtos p2 ON p2.cod_externo = a2.cod_produto
             WHERE v.id_pessoa = x.id_pessoa
               AND p2.cod_externo = x.cod
               AND a2.lixeira = 0
               AND p2.lixeira = 0
           )'
    );

    SET v_blocks = CONCAT(v_blocks,
                          ' UNION ALL ',
                          v_base_select, v_prev_for_mr, ' AND x.src = ''MR'' AND ', v_where);

    -- monta query final (distinct)
    SET @query = CONCAT('SELECT DISTINCT * FROM (', v_blocks, ') AS tab');

    START TRANSACTION;

    -- deletar conforme p_chave (mantive a lógica original)
    IF p_chave = 'P' THEN
        SET @delete_sql = CONCAT('DELETE FROM mat_vatribuicoes WHERE id_pessoa IN (', p_valor, ')');
    ELSEIF p_chave = 'S' THEN
        SET @delete_sql = CONCAT(
            'DELETE mat_vatribuicoes
             FROM mat_vatribuicoes
             JOIN pessoas
                ON pessoas.id = mat_vatribuicoes.id_pessoa
             WHERE pessoas.id_setor IN (', p_valor, ')'
        );
    ELSEIF p_chave = 'M' THEN
        SET @delete_sql = CONCAT(
            'DELETE mat_vatribuicoes
             FROM mat_vatribuicoes
             JOIN mat_vcomodatos
                ON mat_vcomodatos.id_pessoa = mat_vatribuicoes.id_pessoa
             WHERE mat_vcomodatos.id_maquina IN (', p_valor, ')'
        );
    ELSE
        SET @delete_sql = 'DELETE FROM mat_vatribuicoes';
    END IF;

    PREPARE st_del FROM @delete_sql;
    EXECUTE st_del;
    DEALLOCATE PREPARE st_del;

    SET @insert_sql = CONCAT('INSERT INTO mat_vatribuicoes ', @query);
    PREPARE st_ins FROM @insert_sql;
    EXECUTE st_ins;
    DEALLOCATE PREPARE st_ins;

    COMMIT;
END$$

/* -----------------------------------------
   4) atualizar_mat_vretiradas_vultretirada (unificada)
------------------------------------------ */
CREATE PROCEDURE atualizar_mat_vretiradas_vultretirada(
    IN p_chave CHAR(1),
    IN p_valor TEXT,
    IN p_tipo CHAR(1), -- 'R' = mat_vretiradas ; 'U' = mat_vultretirada
    IN apenas_ativos CHAR(1)
)
BEGIN
    DECLARE v_tab_p TEXT DEFAULT 'pessoas';
    DECLARE v_where TEXT DEFAULT '1';
    DECLARE v_join_mat_vcomodatos TEXT DEFAULT '';
    DECLARE v_select_part TEXT;
    DECLARE v_from_part TEXT;
    DECLARE v_left_join_part TEXT;
    DECLARE v_group_by_part TEXT;
    DECLARE v_target_table VARCHAR(64);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erro ao atualizar mat_vretiradas_vultretirada';
    END;

    IF apenas_ativos = 'S' THEN
        SET v_tab_p = 'vativos';
    END IF;

    IF p_chave = 'P' THEN
        SET v_where = CONCAT('p.id IN (', p_valor, ')');
    ELSEIF p_chave = 'S' THEN
        SET v_where = CONCAT('p.id_setor IN (', p_valor, ')');
    ELSEIF p_chave = 'M' THEN
        SET v_where = CONCAT('mat_vcomodatos.id_maquina IN (', p_valor, ')');
        SET v_join_mat_vcomodatos = ' JOIN mat_vcomodatos ON mat_vcomodatos.id_pessoa = p.id ';
    ELSE
        SET v_where = '1';
    END IF;

    IF p_tipo = 'R' THEN
        SET v_target_table = 'mat_vretiradas';
        SET v_select_part = '
            SELECT
                mat_vatribuicoes.id_pessoa,
                mat_vatribuicoes.id_atribuicao,
                p.id_setor,
                IFNULL(SUM(retiradas.qtd), 0) AS valor
        ';
        SET v_from_part = CONCAT('
            FROM vatbreal
            JOIN mat_vatribuicoes
                ON mat_vatribuicoes.id_atribuicao = vatbreal.id
            JOIN ', v_tab_p, ' AS p
                ON p.id = mat_vatribuicoes.id_pessoa
        ');
        SET v_left_join_part = '
            LEFT JOIN retiradas
                ON retiradas.id_atribuicao = vatbreal.id
                    AND retiradas.id_pessoa = p.id
                    AND p.id_empresa IN (0, retiradas.id_empresa)
                    AND retiradas.data >= vatbreal.data
                    AND retiradas.data > DATE_SUB(CURDATE(), INTERVAL vatbreal.validade DAY)
                    AND retiradas.id_supervisor IS NULL
        ';
        SET v_group_by_part = 'GROUP BY mat_vatribuicoes.id_pessoa, mat_vatribuicoes.id_atribuicao, p.id_setor';
    ELSE
        SET v_target_table = 'mat_vultretirada';
        SET v_select_part = '
            SELECT
                mat_vatribuicoes.id_pessoa,
                mat_vatribuicoes.id_atribuicao,
                p.id_setor,
                MAX(retiradas.data) AS data
        ';
        SET v_from_part = CONCAT('
            FROM vatbreal
            JOIN mat_vatribuicoes
                ON mat_vatribuicoes.id_atribuicao = vatbreal.id
            JOIN vatbreal AS associadas
                ON associadas.id = mat_vatribuicoes.id_associado
            JOIN ', v_tab_p, ' AS p
                ON p.id = mat_vatribuicoes.id_pessoa
        ');
        SET v_left_join_part = '
            LEFT JOIN retiradas
                ON retiradas.id_atribuicao = associadas.id
                    AND retiradas.id_pessoa = p.id
                    AND p.id_empresa IN (0, retiradas.id_empresa)
                    AND retiradas.id_supervisor IS NULL
        ';
        SET v_group_by_part = 'GROUP BY mat_vatribuicoes.id_pessoa, mat_vatribuicoes.id_atribuicao, p.id_setor';
    END IF;

    SET @query = CONCAT(
        v_select_part,
        v_from_part,
        v_join_mat_vcomodatos,
        v_left_join_part,
        ' WHERE ', v_where, ' ',
        v_group_by_part
    );

    START TRANSACTION;

    IF p_chave = 'P' THEN
        SET @delete_sql = CONCAT('DELETE FROM ', v_target_table, ' WHERE id_pessoa IN (', p_valor, ')');
    ELSEIF p_chave = 'S' THEN
        SET @delete_sql = CONCAT(
            'DELETE ', v_target_table, '
             FROM ', v_target_table, '
             JOIN pessoas
                ON pessoas.id = ', v_target_table, '.id_pessoa
             WHERE pessoas.id_setor IN (', p_valor, ')'
        );
    ELSEIF p_chave = 'M' THEN
        SET @delete_sql = CONCAT(
            'DELETE ', v_target_table, '
             FROM ', v_target_table, '
             JOIN mat_vcomodatos
                ON mat_vcomodatos.id_pessoa = ', v_target_table, '.id_pessoa
             WHERE mat_vcomodatos.id_maquina IN (', p_valor, ')'
        );
    ELSE
        SET @delete_sql = CONCAT('DELETE FROM ', v_target_table);
    END IF;

    PREPARE st_del4 FROM @delete_sql;
    EXECUTE st_del4;
    DEALLOCATE PREPARE st_del4;

    SET @insert_sql = CONCAT('INSERT INTO ', v_target_table, ' ', @query);
    PREPARE st_ins4 FROM @insert_sql;
    EXECUTE st_ins4;
    DEALLOCATE PREPARE st_ins4;

    COMMIT;
END$$

CREATE PROCEDURE excluir_atribuicao_sem_retirada()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    DELETE a
    FROM atribuicoes a
    LEFT JOIN retiradas r ON r.id_atribuicao = a.id
    WHERE r.id IS NULL
        AND a.lixeira = 1;
    DELETE l
    FROM log l
    LEFT JOIN atribuicoes a2 ON a2.id = l.fk
    WHERE a2.id IS NULL
        AND l.tabela = 'atribuicoes';
    COMMIT;
END $$

CREATE PROCEDURE limpar_usuario_editando()
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;

    START TRANSACTION;
    UPDATE categorias SET id_usuario_editando = 0;
    UPDATE empresas SET id_usuario_editando = 0;
    UPDATE pessoas SET id_usuario_editando = 0;
    UPDATE produtos SET id_usuario_editando = 0;
    UPDATE setores SET id_usuario_editando = 0;
    COMMIT;
END $$

CREATE PROCEDURE refazer_ids()
BEGIN
    -- ------------------------------------------------------------------------------
    -- ETAPA DE DECLARAÇÕES: Todas as variáveis, cursores e handlers vêm primeiro.
    -- ------------------------------------------------------------------------------
    
    -- Variáveis de configuração e controle de loops
    DECLARE v_lista_tabelas TEXT DEFAULT 'atribuicoes,categorias,comodatos,comodatos_produtos,estoque,excecoes,log,maquinas,permissoes,pessoas,previas,produtos,retiradas,setores,solicitacoes,solicitacoes_produtos';
    DECLARE v_tabela_atual VARCHAR(64);
    DECLARE v_tabelas_restantes TEXT;
    DECLARE v_delimitador_pos INT;
    DECLARE v_colunas TEXT;
    
    -- Variáveis para o cursor de atualização de FKs
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE v_main_table, v_fk_column, v_aux_table VARCHAR(64);
    
    -- Declaração do Cursor para ler a tabela de configuração de FKs
    DECLARE cur_fk CURSOR FOR SELECT main_table, fk_column, aux_table FROM fk_configuracao_updates;
    
    -- Handler para o cursor saber quando chegou ao fim dos registros
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    -- Handler de erro geral para garantir o ROLLBACK da transação
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL; -- Re-lança o erro para o usuário saber o que aconteceu
    END;

    -- ------------------------------------------------------------------------------
    -- INÍCIO DOS COMANDOS EXECUTÁVEIS
    -- ------------------------------------------------------------------------------

    -- Inicia a transação
    START TRANSACTION;

    -- ETAPA 0: Criar e popular a "lista/vetor" de chaves estrangeiras
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
        ('previas', 'id_produto', 'produtos'),
        ('produtos', 'id_categoria', 'categorias'),
        ('retiradas', 'id_atribuicao', 'atribuicoes'),
        ('retiradas', 'id_comodato', 'comodatos'),
        ('retiradas', 'id_pessoa', 'pessoas'),
        ('retiradas', 'id_supervisor', 'pessoas'),
        ('retiradas', 'id_produto', 'produtos'),
        ('retiradas', 'id_setor', 'setores'),
        ('solicitacoes', 'id_comodato', 'comodatos'),
        ('solicitacoes_produtos', 'id_produto', 'produtos'),
        ('solicitacoes_produtos', 'id_produto_orig', 'produtos'),
        ('solicitacoes_produtos', 'id_solicitacao', 'solicitacoes');

    -- ETAPA 1: Criar as novas tabelas (com sufixo '2')
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

        SELECT GROUP_CONCAT(CONCAT('`', column_name, '`')) INTO v_colunas
        FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = v_tabela_atual AND column_name != 'id';
        
        SET @sql = CONCAT('CREATE TABLE ', v_tabela_atual, '2 AS SELECT (@row_number:=@row_number+1) AS id, `id` AS id_antigo, ', v_colunas, ' FROM `', v_tabela_atual, '`, (SELECT @row_number:=0) AS t');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('ALTER TABLE `', v_tabela_atual, '2` ADD PRIMARY KEY(id)');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

        SET @sql = CONCAT('ALTER TABLE `', v_tabela_atual, '2` CHANGE id id INT NOT NULL AUTO_INCREMENT');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END WHILE;

    -- ETAPA 2.1: Atualizar chaves estrangeiras dinamicamente usando o cursor
    OPEN cur_fk;
    fk_update_loop: LOOP
        FETCH cur_fk INTO v_main_table, v_fk_column, v_aux_table;
        IF v_done THEN
            LEAVE fk_update_loop;
        END IF;
        
        SET @sql = CONCAT('UPDATE `', v_main_table, '2` AS main JOIN `', v_aux_table, '2` AS aux ON aux.id_antigo = main.`', v_fk_column, '` SET main.`', v_fk_column, '` = aux.id');
        PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur_fk;

    -- ETAPA 2.2: Tratar caso especial da tabela `log` (FK polimórfica)
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
            SET @sql = CONCAT('UPDATE log2 AS main JOIN `', v_tabela_atual, '2` AS aux ON aux.id_antigo = main.fk SET main.fk = aux.id WHERE main.tabela = ''', v_tabela_atual, '''');
            PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
        END IF;
    END WHILE;

    SET @sql = 'UPDATE log2 AS main JOIN pessoas2 AS aux ON aux.id_antigo = main.id_pessoa SET main.id_pessoa = aux.id';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = 'UPDATE users AS main JOIN pessoas2 AS aux ON aux.id_antigo = main.id_pessoa SET main.id_pessoa = aux.id';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    SET @sql = 'UPDATE usrbkp AS main JOIN pessoas2 AS aux ON aux.id_antigo = main.id_pessoa SET main.id_pessoa = aux.id';
    PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

    -- ETAPA 3: Remover tabelas antigas e renomear as novas
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
    
    COMMIT;

END$$

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

END$$

DELIMITER ;