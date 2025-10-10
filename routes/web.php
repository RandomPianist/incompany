<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CategoriasController;
use App\Http\Controllers\MaquinasController;
use App\Http\Controllers\SetoresController;
use App\Http\Controllers\EmpresasController;
use App\Http\Controllers\PessoasController;
use App\Http\Controllers\ProdutosController;
use App\Http\Controllers\AtribuicoesController;
use App\Http\Controllers\ExcecoesController;
use App\Http\Controllers\RetiradasController;
use App\Http\Controllers\RelatoriosController;
use App\Http\Controllers\PreviasController;
use App\Http\Controllers\SolicitacoesController;
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
    Route::get ("/",                         [HomeController::class, "iniciar"])->name("home");
    Route::get ("/permissoes",               [HomeController::class, "permissoes"]);
    Route::get ("/autocomplete",             [HomeController::class, "autocomplete"]);
    Route::get ("/obter-descr",              [HomeController::class, "obter_descr"]);
    Route::get ("/consultar-geral",          [HomeController::class, "consultar_geral"]);
    Route::get ("/pode-abrir/{tabela}/{id}", [HomeController::class, "pode_abrir"]);
    Route::post("/descartar",                [HomeController::class, "descartar"]);

    Route::group(["prefix" => "dashboard"], function() {
        Route::get("/dados",                           [DashboardController::class, "dados"]);
        Route::get("/maquinas",                        [DashboardController::class, "maquinas"]);
        Route::get("/ultimas-retiradas",               [DashboardController::class, "det_ultimas_retiradas"]);
        Route::get("/retiradas-por-setor",             [DashboardController::class, "det_retiradas_por_setor"]);
        Route::get("/retiradas-por-pessoa",            [DashboardController::class, "det_retiradas_por_pessoa"]);
        Route::get("/retiradas-em-atraso/{id_pessoa}", [DashboardController::class, "produtos_em_atraso"]);
    });

    Route::group(["prefix" => "categorias"], function() {
        Route::get ("/",             [CategoriasController::class, "ver"])->name("categorias");
        Route::get ("/listar",       [CategoriasController::class, "listar"]);
        Route::get ("/consultar",    [CategoriasController::class, "consultar"]);
        Route::get ("/mostrar/{id}", [CategoriasController::class, "mostrar"]);
        Route::get ("/aviso/{id}",   [CategoriasController::class, "aviso"]);
        Route::post("/salvar",       [CategoriasController::class, "salvar"]);
        Route::post("/excluir",      [CategoriasController::class, "excluir"]);
    });

    Route::group(["prefix" => "solicitacoes"], function() {
        Route::get ("/",                        [SolicitacoesController::class, "ver"])->name("solicitacoes");
        Route::get ("/meus-comodatos",          [SolicitacoesController::class, "meus_comodatos"]);
        Route::get ("/mostrar",                 [SolicitacoesController::class, "mostrar"]);
        Route::get ("/aviso/{id_comodato}",     [SolicitacoesController::class, "aviso"]);
        Route::get ("/consultar/{id_comodato}", [SolicitacoesController::class, "consultar"]);
        Route::post("/criar",                   [SolicitacoesController::class, "criar"]);
        Route::post("/cancelar",                [SolicitacoesController::class, "cancelar"]);
    });

    Route::group(["prefix" => "previas"], function() {
        Route::get ("/preencher", [PreviasController::class, "preencher"]);
        Route::post("/salvar",    [PreviasController::class, "salvar"]);
        Route::post("/excluir",   [PreviasController::class, "excluir"]);
    });

    Route::group(["prefix" => "setores"], function() {
        Route::get ("/",                [SetoresController::class, "ver"])->name("setores");
        Route::get ("/listar",          [SetoresController::class, "listar"]);
        Route::get ("/consultar",       [SetoresController::class, "consultar"]);
        Route::get ("/permissoes/{id}", [SetoresController::class, "permissoes"]);
        Route::get ("/usuarios/{id}",   [SetoresController::class, "usuarios"]);
        Route::get ("/pessoas/{id}",    [SetoresController::class, "pessoas"]);
        Route::get ("/mostrar/{id}",    [SetoresController::class, "mostrar"]);
        Route::get ("/mostrar2/{id}",   [SetoresController::class, "mostrar"]);
        Route::get ("/aviso/{id}",      [SetoresController::class, "aviso"]);
        Route::post("/salvar",          [SetoresController::class, "salvar"]);
        Route::post("/excluir",         [SetoresController::class, "excluir"]);
    });

    Route::group(["prefix" => "empresas"], function() {
        Route::get ("/",             [EmpresasController::class, "ver"])->name("empresas");
        Route::get ("/listar",       [EmpresasController::class, "listar"]);
        Route::get ("/todas",        [EmpresasController::class, "todas"]);
        Route::get ("/minhas",       [EmpresasController::class, "minhas"]);
        Route::get ("/consultar",    [EmpresasController::class, "consultar"]);
        Route::get ("/mostrar/{id}", [EmpresasController::class, "mostrar"]);
        Route::get ("/aviso/{id}",   [EmpresasController::class, "aviso"]);
        Route::get ("/setores/{id}", [EmpresasController::class, "setores"]);
        Route::post("/salvar",       [EmpresasController::class, "salvar"]);
        Route::post("/excluir",      [EmpresasController::class, "excluir"]);
    });

    Route::group(["prefix" => "colaboradores"], function() {
        Route::get ("/pagina/{tipo}",   [PessoasController::class, "ver"])->name("pessoas");
        Route::get ("/listar",          [PessoasController::class, "listar"]);
        Route::get ("/consultar",       [PessoasController::class, "consultar"]);
        Route::get ("/consultar2",      [PessoasController::class, "consultar2"]);
        Route::get ("/mostrar/{id}",    [PessoasController::class, "mostrar"]);
        Route::get ("/mostrar2/{id}",   [PessoasController::class, "mostrar2"]);
        Route::get ("/aviso/{id}",      [PessoasController::class, "aviso"]);
        Route::post("/senha",           [PessoasController::class, "senha"]);
        Route::post("/salvar",          [PessoasController::class, "salvar"]);
        Route::post("/alterar-empresa", [PessoasController::class, "alterar_empresa"]);
        Route::post("/excluir",         [PessoasController::class, "excluir"]);
    });

    Route::group(["prefix" => "produtos"], function() {
        Route::get ("/",              [ProdutosController::class, "ver"])->name("produtos");
        Route::get ("/listar",        [ProdutosController::class, "listar"]);
        Route::get ("/consultar",     [ProdutosController::class, "consultar"]);
        Route::get ("/mostrar/{id}",  [ProdutosController::class, "mostrar"]);
        Route::get ("/mostrar2/{id}", [ProdutosController::class, "mostrar2"]);
        Route::get ("/aviso/{id}",    [ProdutosController::class, "aviso"]);
        Route::get ("/validade",      [ProdutosController::class, "validade"]);
        Route::post("/salvar",        [ProdutosController::class, "salvar"]);
        Route::post("/excluir",       [ProdutosController::class, "excluir"]);
        Route::group(["prefix" => "maquina"], function() {
            Route::post("/",          [ProdutosController::class, "maquina"]);
            Route::get ("/consultar", [ProdutosController::class, "consultar_maquina"]);
            Route::get ("/listar",    [ProdutosController::class, "listar_maquina"]);
        });
    });

    Route::group(["prefix" => "atribuicoes"], function() {
        Route::get ("/listar",        [AtribuicoesController::class, "listar"]);
        Route::get ("/permissao",     [AtribuicoesController::class, "permissao"]);
        Route::get ("/mostrar/{id}",  [AtribuicoesController::class, "mostrar"]);
        Route::get ("/produtos/{id}", [AtribuicoesController::class, "produtos"]);
        Route::get ("/grade/{id}",    [AtribuicoesController::class, "grade"]);
        Route::post("/salvar",        [AtribuicoesController::class, "salvar"]);
        Route::post("/excluir",       [AtribuicoesController::class, "excluir"]);
        Route::post("/recalcular",    [AtribuicoesController::class, "recalcular"]);
        Route::post("/descartar",     [AtribuicoesController::class, "descartar"]);
        Route::group(["prefix" => "excecoes"], function() {
            Route::get ("/listar/{id_atribuicao}", [ExcecoesController::class, "listar"]);
            Route::get ("/mostrar/{id}",           [ExcecoesController::class, "mostrar"]);
            Route::post("/salvar",                 [ExcecoesController::class, "salvar"]);
            Route::post("/excluir",                [ExcecoesController::class, "excluir"]);
        });
    });

    Route::group(["prefix" => "retiradas"], function() {
        Route::get ("/consultar",            [RetiradasController::class, "consultar"]);
        Route::get ("/proximas/{id_pessoa}", [RetiradasController::class, "proximas"]);
        Route::post("/salvar",               [RetiradasController::class, "salvar"]);
        Route::post("/desfazer",             [RetiradasController::class, "desfazer"]);
    });

    Route::group(["prefix" => "maquinas"], function() {
        Route::get ("/",             [MaquinasController::class, "ver"])->name("maquinas");
        Route::get ("/preco",        [MaquinasController::class, "preco"]);
        Route::get ("/listar",       [MaquinasController::class, "listar"]);
        Route::get ("/consultar",    [MaquinasController::class, "consultar"]);
        Route::get ("/mostrar/{id}", [MaquinasController::class, "mostrar"]);
        Route::get ("/aviso/{id}",   [MaquinasController::class, "aviso"]);
        Route::post("/salvar",       [MaquinasController::class, "salvar"]);
        Route::post("/excluir",      [MaquinasController::class, "excluir"]);
        Route::group(["prefix" => "estoque"], function() {
            Route::post("/",          [MaquinasController::class, "estoque"]);
            Route::get ("/consultar", [MaquinasController::class, "consultar_estoque"]);
        });
        Route::group(["prefix" => "produto"], function() {
            Route::post("/",               [MaquinasController::class, "produto"]);
            Route::get ("/consultar",      [MaquinasController::class, "consultar_produto"]);
            Route::get ("/verificar-novo", [MaquinasController::class, "verificar_novo_cp"]);
        });
        Route::group(["prefix" => "comodato"], function() {
            Route::get ("/mostrar/{id_maquina}", [MaquinasController::class, "mostrar_comodato"]);
            Route::get ("/consultar",            [MaquinasController::class, "consultar_comodato"]);
            Route::post("/criar",                [MaquinasController::class, "criar_comodato"]);
            Route::post("/editar",               [MaquinasController::class, "editar_comodato"]);
            Route::post("/encerrar",             [MaquinasController::class, "encerrar_comodato"]);
        });
    });

    Route::group(["prefix" => "relatorios"], function() {
        Route::get("/comodatos",        [RelatoriosController::class, "comodatos"]);
        Route::get("/ranking",          [RelatoriosController::class, "ranking"]);
        Route::get("/sugestao",         [RelatoriosController::class, "sugestao"]);
        Route::get("/solicitacao/{id}", [RelatoriosController::class, "solicitacao"]);
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