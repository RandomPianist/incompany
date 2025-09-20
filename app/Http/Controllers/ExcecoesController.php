<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Excecoes;
use App\Models\Atribuicoes;
use App\Models\Excbkp;

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
        if ($listando) {
            array_push(
                $campos,
                "vatbold.psm_chave",
                "vatbold.id_empresa_autor AS id_empresa",
                "excecoes.rascunho"
            );
        }
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
        ) AND excecoes.rascunho <> 'R'" : " excecoes.id = ".$id;
        $query .= " ORDER BY excecoes.rascunho";
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
        $id_usuario = Auth::user()->id;
        $atb = Atribuicoes::find($request->id_atribuicao);
        $this->backup_atribuicao($atb); // App\Http\Controllers\Controller.php
        $atb->gerado = 0;
        $atb->save();
        $linha = Excecoes::firstOrNew(["id" => $request->id]);
        if ($request->id) {
            $bkp = new Excbkp;
            $bkp->id_pessoa = $linha->id_pessoa;
            $bkp->id_setor = $linha->id_setor;
            $bkp->id_usuario = $linha->id_usuario;
            $bkp->id_usuario_editando = $id_usuario;
            $bkp->id_excecao = $linha->id;
            $bkp->save();
        }
        switch ($request->ps_chave) {
            case "P":
                $linha->id_pessoa = $request->ps_id;
                break;
            case "S":
                $linha->id_setor = $request->ps_id;
                break;
        }
        $linha->id_atribuicao = $request->id_atribuicao;
        $linha->rascunho = $request->id ? "E" : "C";
        $linha->id_usuario = $id_usuario;
        $linha->save();
        return 201;
    }

    public function excluir(Request $request) {
        $linha = Excecoes::find($request->id);
        $linha->rascunho = 'R';
        $linha->id_usuario = Auth::user()->id;
        $linha->save();
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
