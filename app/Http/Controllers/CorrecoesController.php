<?php

namespace App\Http\Controllers;

use DB;

class CorrecoesController extends Controller {
    private function gerar_log_main($data, $acao, $tabela, $fk) {
        $campos = "id_pessoa, nome, origem, data, hms, acao, tabela, fk, created_at, updated_at";
        $log = DB::table("logbkp")
                ->where("origem", "<>", "COR")
                ->where("acao", $acao)
                ->where("tabela", $tabela)
                ->value("id");
        if ($log === null) {
            DB::statement("
                INSERT INTO log (nome, origem, data, acao, tabela, fk) VALUES ('CORREÇÃO', 'COR', '".$data."', '".$acao."', '".$tabela."', ".$fk.")
            ");
        } else DB::statement("INSERT INTO log (".$campos.") SELECT ".$campos." FROM logbkp WHERE id = ".$log);
    }

    private function executar($tabelas, $tem_lixeira = true) {
        $campos = "
            id,
            DATE(created_at) AS criado,
            DATE(updated_at) AS atualizado
        ";
        $campos2 = "id_pessoa, nome, origem, data, hms, acao, tabela, fk, created_at, updated_at";
        if ($tem_lixeira) $campos .= ", lixeira";
        foreach ($tabelas as $nome_tabela) {
            $tabela = DB::table($nome_tabela)
                        ->select(DB::raw($campos))
                        ->get();
            foreach ($tabela as $linha) {
                $this->gerar_log_main($linha->criado, "C", $nome_tabela, $linha->id);
                if ($tem_lixeira) {
                    if (intval($linha->lixeira)) $this->gerar_log_main($linha->criado, "D", $nome_tabela, $linha->id);
                }
            }
            DB::statement("INSERT INTO log (".$campos2.") SELECT ".$campos2." FROM logbkp WHERE tabela = '".$tabela."' AND origem <> 'COR' AND acao = 'E'");
        }
    }

    public function gerar_log() {
        DB::statement("DROP TABLE IF EXISTS logbkp");
        DB::statement("CREATE TABLE logbkp AS SELECT * FROM log");
        DB::statement("ALTER TABLE logbkp ADD PRIMARY KEY (id)");
        DB::statement("ALTER TABLE logbkp CHANGE id id INT NOT NULL AUTO_INCREMENT");
        DB::table("log")->truncate();
        $this->executar(["atribuicoes", "categorias", "empresas", "maquinas", "pessoas", "produtos", "setores"]);
        $this->executar(["comodatos", "comodatos_produtos", "dedos", "estoque", "excecoes", "permissoes", "previas", "retiradas", "users", "solicitacoes", "solicitacoes_produtos"], false);
    }
}