<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Empresas;
use App\Models\Setores;
use App\Models\Maquinas;

class EmpresasController extends Controller {
    private function validar_cnpj($cnpj) {
        $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
        if (strlen($cnpj) != 14) return false;
        if (preg_match('/(\d)\1{13}/', $cnpj)) return false;
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) return false;
        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }

    private function busca($param) {
        return DB::table("empresas")
                    ->select(
                        "id",
                        "nome_fantasia",
                        "id_matriz"
                    )
                    ->where(function($sql) use($param) {
                        $empresa_usuario = Empresas::find($this->obter_empresa()); // App\Http\Controllers\Controller.php
                        if ($empresa_usuario !== null) {
                            if ($param == "F" && !intval($empresa_usuario->id_matriz)) $sql->where("id_matriz", $empresa_usuario->id);
                            else $sql->where("id", $param == "M" ? !intval($empresa_usuario->id_matriz) ? $empresa_usuario->id : $empresa_usuario->id_matriz : $empresa_usuario->id);
                        } else $sql->where("id_matriz", $param == "M" ? "=" : ">", 0);
                    })
                    ->where("lixeira", 0)
                    ->orderBy("nome_fantasia")
                    ->get();
    }

    private function aviso_main($id) {
        $resultado = new \stdClass;
        $nome = Empresas::find($id)->nome_fantasia;
        if (
            DB::table("pessoas")
                ->where("id_empresa", $id)
                ->where("lixeira", 0)
                ->exists()
        ) {
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem pessoas vinculadas a essa empresa.";
            $resultado->permitir = 0;
        } else if (
            DB::table("comodatos")
                ->whereRaw("CURDATE() >= inicio AND CURDATE() < fim")
                ->where("id_empresa", $id)
                ->exists()
        ) {
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem máquinas comodatadas para essa empresa.";
            $resultado->permitir = 0;
        } else {
            $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
            $resultado->permitir = 1;
        }
        return $resultado;
    }

    public function ver() {
        return view("empresas");
    }

    public function listar() {
        $id_emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
        $resultado = new \stdClass;
        $resultado->inicial = $this->busca("M");
        $resultado->final = $this->busca("F");
        $resultado->matriz_editavel = $id_emp ? sizeof(DB::table("empresas")->where("id_matriz", $id_emp)->where("lixeira", 0)->get()) > 0 ? 1 : 0 : 1;
        return json_encode($resultado);
    }

    public function todas() {
        $matrizes = DB::table("empresas")
                        ->where("id_matriz", 0)
                        ->where("lixeira", 0)
                        ->orderBy("id", "asc")
                        ->get();
        foreach($matrizes as $matriz) {
            $filiais = DB::table("empresas")
                           ->where("id_matriz", $matriz->id)
                           ->where("lixeira", 0)
                           ->orderBy("id", "asc")
                           ->get();
            $matriz->filiais = $filiais;
        }
        return json_encode($matrizes);
    }

    public function consultar(Request $request) {
        if (!$request->id &&
            DB::table("empresas")
                ->where("lixeira", 0)
                ->where("cnpj", $request->cnpj)
                ->exists()
        ) return "R";
        if (
            DB::table("empresas")
                ->where("lixeira", 0)
                ->where("id_matriz", $request->id)
                ->exists()
        ) return "F";
        return "A";
    }

    public function mostrar($id) {
        return json_encode(Empresas::find($id));
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function salvar(Request $request) {
        if (!trim($request->cnpj)) return 400;
        if (!trim($request->razao_social)) return 400;
        if (!trim($request->nome_fantasia)) return 400;
        if ($this->consultar($request) == "R") return 401;
        $linha = Empresas::firstOrNew(["id" => $request->id]);
        if (
            $request->id &&
            !$this->comparar_texto($request->cnpj, $linha->cnpj) && // App\Http\Controllers\Controller.php
            !$this->comparar_texto($request->razao_social, $linha->razao_social) && // App\Http\Controllers\Controller.php
            !$this->comparar_texto($request->nome_fantasia, $linha->nome_fantasia) // App\Http\Controllers\Controller.php
        ) return 400;
        if (!$this->validar_cnpj($request->cnpj)) return 400;
        $linha->nome_fantasia = mb_strtoupper($request->nome_fantasia);
        $linha->razao_social = mb_strtoupper($request->razao_social);
        $linha->cnpj = $request->cnpj;
        $linha->id_matriz = $request->id_matriz ? $request->id_matriz : 0;
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "empresas", $linha->id); // App\Http\Controllers\Controller.php
        if (!$request->id) {
            $setores = [
                "ADMINISTRADORES" => ["financeiro", "atribuicoes", "retiradas", "pessoas", "usuarios", "solicitacoes", "supervisor"],
                "RECURSOS HUMANOS" => ["atribuicoes", "retiradas", "pessoas", "usuarios"],
                "FINANCEIRO" => ["financeiro", "usuarios", "solicitacoes"],
                "SEGURANÇA DO TRABALHO" => ["atribuicoes", "retiradas", "pessoas", "usuarios", "supervisor"]
            ];
            foreach ($setores as $setor => $permissoes) {
                $m_setor = new Setores;
                $m_setor->descr = $setor;
                $m_setor->id_empresa = $linha->id;
                $m_setor->cria_usuario = 1;
                $m_setor->save();
                $lista_permissoes = array();
                foreach ($permissoes as $permissao) $lista_permissoes += [$permissao => 1];
                $m_setor->permissoes()->create($lista_permissoes);
            }
            $this->log_inserir_lote("C", "setores", "", "SYS"); // App\Http\Controllers\Controller.php
            $this->log_inserir_lote("C", "permissoes", "", "SYS"); // App\Http\Controllers\Controller.php
        }
        if ($request->maq_igual == "S") {
            $maquina = new Maquinas;
            $maquina->descr = $linha->nome_fantasia;
            $maquina->save();
            $this->log_inserir("C", "maquinas", $maquina->id);
            return redirect("/maquinas");
        }
        return redirect("/empresas?grupo=".$request->id_matriz);
    }

    public function excluir(Request $request) {
        if (!$this->aviso_main($request->id)->permitir) return 401;
        $linha = Empresas::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "empresas", $linha->id); // App\Http\Controllers\Controller.php
        return 200;
    }
}