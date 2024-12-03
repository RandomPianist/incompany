<?php

use App\Http\Controllers\ApiController;
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
    Route::post("/marcar-gerou-pedido",  [ApiController::class, "marcarGerouPedido"]);
    Route::post("/associar-empresa",     [ApiController::class, "associar_empresa"]);
});

Route::group(["prefix" => "app"], function() {
    Route::get ("/pessoas-com-foto",       [ApiController::class, "pessoasComFoto"]);
    Route::post("/ver-pessoa",             [ApiController::class, "verPessoa"]);
    Route::post("/produtos-por-pessoa",    [ApiController::class, "produtosPorPessoa"]);
    Route::post("/validar",                [ApiController::class, "validarApp"]);
    Route::post("/retirar",                [ApiController::class, "retirar"]);
    Route::post("/retirar-com-supervisao", [ApiController::class, "retirarComSupervisao"]);
    Route::post("/validar-spv",            [ApiController::class, "validarSpv"]);

    Route::group(["prefix" => "dashboard"], function() {
        Route::get("/retiradas-por-setor/{id_pessoa}",  [DashboardController::class, "retiradas_por_setor"]);
        Route::get("/retiradas-em-atraso/{id_pessoa}",  [DashboardController::class, "retiradas_em_atraso"]);
        Route::get("/ultimas-retiradas/{id_pessoa}",    [DashboardController::class, "ultimas_retiradas"]);
        Route::get("/produtos-em-atraso/{id_pessoa}",   [DashboardController::class, "produtos"]);
    });
});