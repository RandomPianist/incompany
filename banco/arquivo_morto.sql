CREATE TABLE logbkp AS SELECT * FROM log;
CREATE TABLE log2 AS (
	SELECT log.*
	
	FROM log
	
	JOIN (
		SELECT
			MAX(data) AS data,
			MAX(created_at) AS created_at,
			tabela,
			acao
			
		FROM log
		
		WHERE tabela NOT IN ('retiradas', 'estoque', 'solicitacoes', 'setores') AND origem <> 'SYS'
		
		GROUP BY
			tabela,
			acao
	) AS lim ON lim.data = log.data
		AND lim.created_at = log.created_at
		AND lim.tabela = log.tabela
		AND lim.tabela = log.acao
		
	UNION ALL (
		SELECT *
		
		FROM log
		
		WHERE tabela IN ('retiradas', 'estoque', 'solicitacoes', 'setores') OR origem = 'SYS'
	)
);
DROP TABLE log;
RENAME TABLE log2 TO log;
ALTER TABLE log ADD PRIMARY KEY (id);
ALTER TABLE log MODIFY id int(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE log MODIFY created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE log MODIFY updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;