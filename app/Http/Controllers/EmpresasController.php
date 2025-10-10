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

    private function aviso_main($id) {
        $resultado = $this->pode_abrir_main("empresas", $id, "excluir"); // App\Http\Controllers\Controller.php
        if (!$resultado->permitir) return $resultado;
        $resultado = new \stdClass;
        $emp = Empresas::find($id);
        $nome = "<b>".$emp->nome_fantasia."</b>";
        $resultado->permitir = 0;
        if ($emp->pessoas()->exists()) {
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem pessoas vinculadas a essa empresa.";
        } elseif (
            DB::table("comodatos")
                ->whereRaw("CURDATE() >= inicio AND CURDATE() < fim")
                ->where("id_empresa", $id)
                ->exists()
        ) {
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem máquinas comodatadas para essa empresa.";
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
        $id_emp = $this->obter_empresa();
        $resultado = new \stdClass;
        $resultado->inicial = $this->busca_emp("M"); // App\Http\Controllers\Controller.php
        $resultado->final = $this->busca_emp("F"); // App\Http\Controllers\Controller.php
        $resultado->matriz_editavel = $id_emp ? Empresas::find($id_emp)->filiais()->exists() ? 1 : 0 : 1;
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
        if (!$request->id && Empresas::where("lixeira", 0)->where("cnpj", $request->cnpj)->exists()) return "R";
        return "A";
    }

    public function consultar2(Request $request) {
        return $this->empresa_consultar($request) && $request->empresa ? "erro" : "ok"; // App\Http\Controllers\Controller.php
    }

    public function mostrar($id) {
        return json_encode($this->alterar_usuario_editando("empresas", $id)); // App\Http\Controllers\Controller.php
    }

    public function aviso($id) {
        return json_encode($this->aviso_main($id));
    }

    public function salvar(Request $request) {
        $emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
        if ($this->verifica_vazios($request, ["cnpj", "razao_social", "nome_fantasia", "cidade"])) return 400; // App\Http\Controllers\Controller.php
        if ($this->consultar($request) == "R") return 401;
        if ($emp && $emp != $request->id_matriz && $emp != $request->id) return 401;
        $linha = Empresas::firstOrNew(["id" => $request->id]);
        if (
            $request->id &&
            !$this->comparar_texto($request->cnpj, $linha->cnpj) && // App\Http\Controllers\Controller.php
            !$this->comparar_texto($request->cidade, $linha->cidade) && // App\Http\Controllers\Controller.php
            !$this->comparar_texto($request->razao_social, $linha->razao_social) && // App\Http\Controllers\Controller.php
            !$this->comparar_texto($request->nome_fantasia, $linha->nome_fantasia) // App\Http\Controllers\Controller.php
        ) return 400;
        if (!$this->validar_cnpj($request->cnpj)) return 400;
        $linha->nome_fantasia = mb_strtoupper($request->nome_fantasia);
        $linha->razao_social = mb_strtoupper($request->razao_social);
        $linha->cnpj = $request->cnpj;
        $linha->cidade = $request->cidade;
        $linha->id_matriz = $request->id_matriz ? $request->id_matriz : 0;
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "empresas", $linha->id); // App\Http\Controllers\Controller.php
        if (!$request->id) {
            $setores = [
                "ADMINISTRADORES" => $this->obter_lista_permissoes(), // App\Http\Controllers\Controller.php
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
        if ($request->maq_igual == "S" && !$emp) {
            $maquina = new Maquinas;
            $maquina->descr = $linha->nome_fantasia;
            $maquina->save();
            $this->log_inserir("C", "maquinas", $maquina->id);
            return redirect("/maquinas");
        }
        return redirect("/empresas?grupo=".$request->id_matriz);
    }

    public function excluir(Request $request) {
        $emp = $this->obter_empresa(); // App\Http\Controllers\Controller.php
        $linha = Empresas::find($request->id);
        if (!$this->aviso_main($request->id)->permitir) return 401;
        if ($emp && $emp != $linha->id && $emp != $linha->id_matriz) return 401;
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "empresas", $linha->id); // App\Http\Controllers\Controller.php
        return 200;
    }

    public function setores($id) {
        $resultado = new \stdClass;
        $permissoes = array();
        $lista = $this->obter_lista_permissoes(); // App\Http\Controllers\Controller.php
        foreach ($lista as $permissao) array_push($permissoes, "(permissoes.".$permissao." = 1 AND minhas_permissoes.".$permissao." = 0)");
        return json_encode(
            DB::table("setores")
                ->select(
                    "setores.id",
                    "setores.descr",
                    "setores.cria_usuario",
                    "permissoes.supervisor",
                    DB::raw("
                        CASE
                            WHEN (".implode(" OR ", $permissoes).") THEN 0
                            ELSE 1
                        END AS ativo
                    ")
                )
                ->crossjoin("permissoes AS minhas_permissoes")
                ->join("permissoes", "permissoes.id_setor", "setores.id")
                ->where("setores.lixeira", 0)
                ->where("setores.id_empresa", $id)
                ->where("minhas_permissoes.id_usuario", Auth::user()->id)
                ->get()
        );
    }

    public function minhas() {
        return json_encode($this->minhas_empresas()); // App\Http\Controllers\Controller.php
    }
}