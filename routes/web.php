<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ValoresController;
use App\Http\Controllers\SetoresController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\PessoasController;
use App\Http\Controllers\ProdutosController;
use App\Http\Controllers\AtribuicoesController;
use App\Http\Controllers\RetiradasController;
use App\Http\Controllers\MaquinasController;
use App\Http\Controllers\RelatoriosController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware("auth")->group(function () {
    Route::get("/",             [DashboardController::class, "iniciar"]);
    Route::get("/autocomplete", [HomeController::class, "autocomplete"]);

    Route::group(["prefix" => "dashboard"], function() {
        Route::get("/dados",                           [DashboardController::class, "dados"]);
        Route::get("/maquinas",                        [DashboardController::class, "maquinas"]);
        Route::get("/ultimas-retiradas",               [DashboardController::class, "det_ultimas_retiradas"]);
        Route::get("/retiradas-por-setor",             [DashboardController::class, "det_retiradas_por_setor"]);
        Route::get("/retiradas-por-pessoa",            [DashboardController::class, "det_retiradas_por_pessoa"]);
        Route::get("/retiradas-em-atraso/{id_pessoa}", [DashboardController::class, "produtos_em_atraso"]);
    });

    Route::group(["prefix" => "valores/{alias}"], function() {
        Route::get ("/",             [ValoresController::class, "ver"]);
        Route::get ("/listar",       [ValoresController::class, "listar"]);
        Route::get ("/consultar",    [ValoresController::class, "consultar"]);
        Route::get ("/mostrar/{id}", [ValoresController::class, "mostrar"]);
        Route::get ("/aviso/{id}",   [ValoresController::class, "aviso"]);
        Route::post("/salvar",       [ValoresController::class, "salvar"]);
        Route::post("/excluir",      [ValoresController::class, "excluir"]);
    });

    Route::group(["prefix" => "setores"], function() {
        Route::get ("/",               [SetoresController::class, "ver"]);
        Route::get ("/listar",         [SetoresController::class, "listar"]);
        Route::get ("/consultar",      [SetoresController::class, "consultar"]);
        Route::get ("/usuarios/{id}",  [SetoresController::class, "usuarios"]);
        Route::get ("/pessoas/{id}",   [SetoresController::class, "pessoas"]);
        Route::get ("/mostrar/{id}",   [SetoresController::class, "mostrar"]);
        Route::get ("/aviso/{id}",     [SetoresController::class, "aviso"]);
        Route::get ("/primeiro-admin", [SetoresController::class, "primeiro_admin"]);
        Route::post("/salvar",         [SetoresController::class, "salvar"]);
        Route::post("/excluir",        [SetoresController::class, "excluir"]);
    });

    Route::group(["prefix" => "empresas"], function() {
        Route::get ("/",             [EmpresasController::class, "ver"]);
        Route::get ("/listar",       [EmpresasController::class, "listar"]);
        Route::get ("/todas",        [EmpresasController::class, "todas"]);
        Route::get ("/consultar",    [EmpresasController::class, "consultar"]);
        Route::get ("/mostrar/{id}", [EmpresasController::class, "mostrar"]);
        Route::get ("/aviso/{id}",   [EmpresasController::class, "aviso"]);
        Route::post("/salvar",       [EmpresasController::class, "salvar"]);
        Route::post("/excluir",      [EmpresasController::class, "excluir"]);
    });

    Route::group(["prefix" => "colaboradores"], function() {
        Route::get ("/pagina/{tipo}",   [PessoasController::class, "ver"]);
        Route::get ("/listar",          [PessoasController::class, "listar"]);
        Route::get ("/consultar",       [PessoasController::class, "consultar"]);
        Route::get ("/mostrar/{id}",    [PessoasController::class, "mostrar"]);
        Route::get ("/aviso/{id}",      [PessoasController::class, "aviso"]);
        Route::get ("/modal",           [PessoasController::class, "modal"]);
        Route::post("/salvar",          [PessoasController::class, "salvar"]);
        Route::post("/alterar-empresa", [PessoasController::class, "alterar_empresa"]);
        Route::post("/excluir",         [PessoasController::class, "excluir"]);
        Route::post("/supervisor",      [PessoasController::class, "supervisor"]);
    });

    Route::group(["prefix" => "produtos"], function() {
        Route::get ("/",             [ProdutosController::class, "ver"]);
        Route::get ("/listar",       [ProdutosController::class, "listar"]);
        Route::get ("/consultar",    [ProdutosController::class, "consultar"]);
        Route::get ("/mostrar/{id}", [ProdutosController::class, "mostrar"]);
        Route::get ("/aviso/{id}",   [ProdutosController::class, "aviso"]);
        Route::get ("/validade",     [ProdutosController::class, "validade"]);
        Route::post("/salvar",       [ProdutosController::class, "salvar"]);
        Route::post("/excluir",      [ProdutosController::class, "excluir"]);
    });

    Route::group(["prefix" => "atribuicoes"], function() {
        Route::get ("/listar",        [AtribuicoesController::class, "listar"]);
        Route::get ("/mostrar/{id}",  [AtribuicoesController::class, "mostrar"]);
        Route::get ("/produtos/{id}", [AtribuicoesController::class, "produtos"]);
        Route::post("/salvar",        [AtribuicoesController::class, "salvar"]);
        Route::post("/excluir",       [AtribuicoesController::class, "excluir"]);
    });

    Route::group(["prefix" => "retiradas"], function() {
        Route::get ("/consultar", [RetiradasController::class, "consultar"]);
        Route::post("/salvar",    [RetiradasController::class, "salvar"]);
        Route::post("/desfazer",  [RetiradasController::class, "desfazer"]);
    });

    Route::group(["prefix" => "maquinas"], function() {
        Route::group(["prefix" => "estoque"], function() {
            Route::post("/",          [MaquinasController::class, "estoque"]);
            Route::get ("/consultar", [MaquinasController::class, "consultar_estoque"]);
        });
        Route::group(["prefix" => "comodato"], function() {
            Route::get ("/consultar", [MaquinasController::class, "consultar_comodato"]);
            Route::post("/criar",     [MaquinasController::class, "criar_comodato"]);
            Route::post("/encerrar",  [MaquinasController::class, "encerrar_comodato"]);
        });
    });

    Route::group(["prefix" => "relatorios"], function() {
        Route::get("/comodatos", [RelatoriosController::class, "comodatos"]);
        Route::get("/ranking",   [RelatoriosController::class, "ranking"]);
        Route::group(["prefix" => "bilateral"], function() {
            Route::get("/",          [RelatoriosController::class, "bilateral"]);
            Route::get("/consultar", [RelatoriosController::class, "bilateral_consultar"]);
        });
        Route::group(["prefix" => "controle"], function() {
            Route::get("/",          [RelatoriosController::class, "controle"]);
            Route::get("/consultar", [RelatoriosController::class, "controle_consultar"]);
            Route::get("/existe",    [RelatoriosController::class, "controle_existe"]);
            Route::get("/pessoas",   [RelatoriosController::class, "controle_pessoas"]);
        });
        Route::group(["prefix" => "extrato"], function() {
            Route::get("/",          [RelatoriosController::class, "extrato"]);
            Route::get("/consultar", [RelatoriosController::class, "extrato_consultar"]);
        });
        Route::group(["prefix" => "retiradas"], function() {
            Route::get("/",          [RelatoriosController::class, "retiradas"]);
            Route::get("/consultar", [RelatoriosController::class, "retiradas_consultar"]);
        });
    });
});

require __DIR__.'/auth.php';