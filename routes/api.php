<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api2Controller;
use App\Http\Controllers\ProdutosController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(["prefix" => "erp"], function() {
    Route::get ("/empresas",             [ApiController::class, "empresas"]);
    Route::get ("/maquinas",             [ApiController::class, "maquinas"]);
    Route::get ("/produtos-por-maquina", [ApiController::class, "produtos_por_maquina"]);
    Route::get ("/retiradas-periodo",    [ApiController::class, "retiradas_por_periodo"]);
    Route::get ("/produtos",             [ProdutosController::class, "listar"]);
    Route::post("/categorias",           [ApiController::class, "categorias"]);
    Route::post("/produtos",             [ApiController::class, "produtos"]);
    Route::post("/movimentar-estoque",   [ApiController::class, "movimentar_estoque"]);
    Route::post("/gerenciar-estoque",    [ApiController::class, "gerenciar_estoque"]);
    Route::post("/marcar-gerou-pedido",  [ApiController::class, "marcar_gerou_pedido"]);
    Route::post("/associar-empresa",     [ApiController::class, "associar_empresa"]);

    Route::group(["prefix" => "v2"], function() {
        Route::group(["prefix" => "maquinas"], function() {
            Route::post("/",          [Api2Controller::class, "maquinas_por_cliente"]);
            Route::post("/todas",     [Api2Controller::class, "maquinas_todas"]);
            Route::post("/consultar", [Api2Controller::class, "consultar_maquina"]);
            Route::post("/criar",     [Api2Controller::class, "criar"]);
        });
        Route::group(["prefix" => "solicitacoes"], function() {
            Route::post("/",                    [Api2Controller::class, "enviar_solicitacoes"]);
            Route::post("/gravar",              [Api2Controller::class, "gravar_solicitacao"]);
            Route::post("/gravar-inexistentes", [Api2Controller::class, "gravar_inexistentes"]);
            Route::post("/aceitar",             [Api2Controller::class, "aceitar_solicitacao"]);
            Route::post("/recusar",             [Api2Controller::class, "recusar_solicitacao"]);
            Route::post("/enviar",              [Api2Controller::class, "receber_solicitacao"]);
        });
        Route::group(["prefix" => "retiradas"], function() {
            Route::post("/",       [Api2Controller::class, "obter_retiradas"]);
            Route::post("/salvar", [Api2Controller::class, "salvar_retirada"]);
        });
        Route::post("/produtos",     [Api2Controller::class, "produtos"]);
        Route::post("/sincronizar",  [Api2Controller::class, "sincronizar"]);
        Route::post("/pode-faturar", [Api2Controller::class, "pode_faturar"]);
    });
});

Route::group(["prefix" => "app"], function() {
    Route::get ("/pessoas-com-foto",       [ApiController::class, "pessoas_com_foto"]);
    Route::post("/ver-pessoa",             [ApiController::class, "ver_pessoa"]);
    Route::post("/produtos-por-pessoa",    [ApiController::class, "produtos_por_pessoa"]);
    Route::post("/validar",                [ApiController::class, "validar_app"]);
    Route::post("/retirar",                [ApiController::class, "retirar"]);
    Route::post("/retirar-com-supervisao", [ApiController::class, "retirar"]);
    Route::post("/validar-spv",            [ApiController::class, "validar_spv"]);
    Route::post("/biometria",              [ApiController::class, "biometria"]);
    Route::post("/validar-biometria",      [ApiController::class, "validar_biometria"]);

    Route::group(["prefix" => "dashboard"], function() {
        Route::get("/retiradas-por-setor/{id_pessoa}",  [DashboardController::class, "retiradas_por_setor"]);
        Route::get("/retiradas-em-atraso/{id_pessoa}",  [DashboardController::class, "retiradas_em_atraso"]);
        Route::get("/ultimas-retiradas/{id_pessoa}",    [DashboardController::class, "ultimas_retiradas"]);
        Route::get("/produtos-em-atraso/{id_pessoa}",   [DashboardController::class, "produtos_em_atraso"]);
    });

    Route::group(["prefix" => "v2"], function() {
        Route::group(["prefix" => "previas"], function() {
            Route::post("/",       [Api2Controller::class, "enviar_previas"]);
            Route::post("/enviar", [Api2Controller::class, "receber_previa"]);
            Route::post("/limpar", [Api2Controller::class, "limpar_previas"]);
        });
        Route::group(["prefix" => "dedos"], function() {
            Route::post("/",       [Api2Controller::class, "dedos"]);
            Route::post("/pessoa", [Api2Controller::class, "dedos_pessoa"]);
            Route::post("/cpf",    [Api2Controller::class, "dedos_cpf"]);
            Route::post("/salvar", [Api2Controller::class, "salvar_dedos"]);
        });
        Route::post("/retirar", [Api2Controller::class, "retirar"]);
    });
});