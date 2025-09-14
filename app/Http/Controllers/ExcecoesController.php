<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Excecoes;
use App\Models\Atribuicoes;

class ExcecoesController extends Controller {
    private function consulta($listando, $id) {
        $campos = [
            "excecoes.id",
            "excecoes.id_atribuicao",
            "CASE
                WHEN (excecoes.id_setor IS NOT NULL) THEN excecoes.id_setor
                ELSE excecoes.id_pessoa
            END AS ps_id",
            "CASE
                WHEN (excecoes.id_setor IS NOT NULL) THEN 'S'
                ELSE 'P'
            END AS ps_chave",
            "CASE
                WHEN (excecoes.id_setor IS NOT NULL) THEN setores.descr
                ELSE pessoas.nome
            END AS ps_valor"
        ];
        if ($listando) array_push($campos, "vatbold.psm_chave", "vatbold.id_empresa_autor AS id_empresa");
        $query = "SELECT ".implode(", ", $campos)." FROM excecoes ";
        if ($listando) $query .= " JOIN vatbold ON vatbold.id = excecoes.id_atribuicao ";
        $query .= "
            LEFT JOIN pessoas
                ON pessoas.id = excecoes.id_pessoa

            LEFT JOIN setores
                ON setores.id = excecoes.id_setor

            WHERE
        ";
        $query .= $listando ? " excecoes.id_atribuicao = ".$id." AND excecoes.lixeira = 0 AND (
            pessoas.lixeira = 0 OR pessoas.id IS NULL
        ) AND (
            setores.lixeira = 0 OR setores.id IS NULL
        )" : " excecoes.id = ".$id;
        return DB::select(DB::raw($query));
    }

    public function salvar(Request $request) {
        if (!sizeof(
            DB::table($request->ps_chave == "P" ? "pessoas" : "setores")
                ->where($request->ps_chave == "P" ? "nome" : "descr", $request->ps_valor)
                ->where("id", $request->ps_id)
                ->where("lixeira", 0)
                ->get()
        )) return 404;
        if (sizeof(
            DB::table("excecoes")
                ->where("id_atribuicao", $request->id_atribuicao)
                ->where($request->ps_chave == "P" ? "id_pessoa" : "id_setor", $request->ps_id)
                ->get()
        ) && !intval($request->id)) return 403;
        $linha = Excecoes::firstOrNew(["id" => $request->id]);
        switch ($request->ps_chave) {
            case "P":
                $linha->id_pessoa = $request->ps_id;
                break;
            case "S":
                $linha->id_setor = $request->ps_id;
                break;
        }
        $linha->id_atribuicao = $request->id_atribuicao;
        $linha->save();
        $this->atualizar_atribuicoes($this->obter_atb_ant($request->id_atribuicao)); // App\Http\Controllers\Controller.php
        $this->log_inserir($request->id ? "E" : "C", "excecoes", $linha->id); // App\Http\Controllers\Controller.php
        return 201;
    }

    public function excluir(Request $request) {
        $linha = Excecoes::find($request->id);
        $ant = $this->obter_atb_ant($linha->id_atribuicao); // App\Http\Controllers\Controller.php
        $linha->lixeira = 1;
        $linha->save();
        $this->atualizar_atribuicoes($ant); // App\Http\Controllers\Controller.php
        $this->log_inserir("D", "excecoes", $linha->id); // App\Http\Controllers\Controller.php
        $this->excluir_atribuicao_sem_retirada(); // App\Http\Controllers\Controller.php
    }

    public function listar($id_atribuicao) {
        return json_encode($this->atribuicao_listar( // App\Http\Controllers\Controller.php
            $this->consulta(true, $id_atribuicao)
        ));
    }

    public function mostrar($id) {
        return json_encode($this->consulta(false, $id)[0]);
    }
}
