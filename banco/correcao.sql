ALTER TABLE permissoes DROP COLUMN supervisor;
ALTER TABLE setores ADD COLUMN supervisor TINYINT DEFAULT 0 AFTER descr;
UPDATE setores SET supervisor = 1 WHERE descr IN ('SEGURANÇA DO TRABALHO', 'ADMINISTRADORES');

ALTER TABLE pessoas ADD COLUMN visitante TINYINT DEFAULT 0 AFTER supervisor;
UPDATE pessoas SET visitante = 1 WHERE cpf = '00000000000';
ALTER TABLE setores ADD COLUMN visitante TINYINT DEFAULT 0 AFTER supervisor;
INSERT INTO setores (descr, visitante, id_empresa) (
    SELECT
        'VISITANTES',
        1,
        empresas.id

    FROM empresas
);
INSERT INTO permissoes (id_setor) (
    SELECT setores.id
    FROM setores
    LEFT JOIN permissoes
        ON permissoes.id_setor = setores.id
    WHERE permissoes.id IS NULL
);
INSERT INTO log (acao, origem, tabela, id_pessoa, fk, data) (
    SELECT
        'C',
        'SYS',
        'setores',
        1,
        setores.id,
        '2025-11-08'

    FROM setores

    LEFT JOIN log
        ON log.fk = setores.id AND log.tabela = 'setores'

    WHERE log.id IS NULL
);
INSERT INTO log (acao, origem, tabela, id_pessoa, fk, data) (
    SELECT
        'C',
        'SYS',
        'permissoes',
        1,
        permissoes.id,
        '2025-11-08'

    FROM permissoes

    LEFT JOIN log
        ON log.fk = permissoes.id AND log.tabela = 'permissoes'

    WHERE log.id IS NULL
);