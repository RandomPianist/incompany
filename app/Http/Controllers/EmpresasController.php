<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use Illuminate\Http\Request;
use App\Models\Empresas;
use App\Models\Pessoas;

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
                        $id_emp = Pessoas::find(Auth::user()->id_pessoa)->id_empresa;
                        if (intval($id_emp)) {
                            $empresa_usuario = Empresas::find($id_emp);
                            if ($param == "filial" && !intval($empresa_usuario->id_matriz)) $sql->where("id_matriz", $empresa_usuario->id);
                            else $sql->where("id", $param == "matriz" ? !intval($empresa_usuario->id_matriz) ? $empresa_usuario->id : $empresa_usuario->id_matriz : $empresa_usuario->id);
                        } else $sql->where("id_matriz", $param == "matriz" ? "=" : ">", 0);
                    })
                    ->where("lixeira", 0)
                    ->orderBy("nome_fantasia")
                    ->get();
    }

    private function aviso_main($id) {
        $resultado = new \stdClass;
        $nome = Empresas::find($id)->nome_fantasia;
        if (sizeof(
            DB::table("pessoas")
                ->where("id_empresa", $id)
                ->where("lixeira", 0)
                ->get()
        )) {
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem pessoas vinculadas a essa empresa.";
            $resultado->permitir = 0;
        } else if (sizeof(
            DB::table("comodatos")
                ->whereRaw("CURDATE() >= inicio AND CURDATE() < fim")
                ->where("id_empresa", $id)
                ->get()
        )) {
            $resultado->aviso = "Não é possível excluir ".$nome." porque existem máquinas comodatadas para essa empresa.";
            $resultado->permitir = 0;
        } else {
            $resultado->aviso = "Tem certeza que deseja excluir ".$nome."?";
            $resultado->permitir = 1;
        }
        return $resultado;
    }

    public function ver() {
        $ultima_atualizacao = $this->log_consultar("empresas");
        $pode_criar_matriz = !intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
        return view("empresas", compact("ultima_atualizacao", "pode_criar_matriz"));
    }

    public function listar() {
        $id_emp = intval(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
        $resultado = new \stdClass;
        $resultado->inicial = $this->busca("matriz");
        $resultado->final = $this->busca("filial");
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
        if (sizeof(
            DB::table("empresas")
                ->where("lixeira", 0)
                ->where("cnpj", $request->cnpj)
                ->get()
        ) && !$request->id) return "1";
        return "0";
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
        if (intval($this->consultar($request))) return 401;
        $linha = Empresas::firstOrNew(["id" => $request->id]);
        if (
            $request->id &&
            !$this->comparar_texto($request->cnpj, $linha->cnpj) &&
            !$this->comparar_texto($request->razao_social, $linha->razao_social) &&
            !$this->comparar_texto($request->nome_fantasia, $linha->nome_fantasia)
        ) return 400;
        if (!$this->validar_cnpj($request->cnpj)) return 400;
        $linha->nome_fantasia = mb_strtoupper($request->nome_fantasia);
        $linha->razao_social = mb_strtoupper($request->razao_social);
        $linha->cnpj = $request->cnpj;
        $linha->id_matriz = $request->id_matriz ? $request->id_matriz : 0;
        $linha->save();
        $this->log_inserir($request->id ? "E" : "C", "empresas", $linha->id);
        return redirect("/empresas");
    }

    public function excluir(Request $request) {
        if (!$this->aviso_main($request->id)->permitir) return 401;
        $linha = Empresas::find($request->id);
        $linha->lixeira = 1;
        $linha->save();
        $this->log_inserir("D", "empresas", $linha->id);
    }
}