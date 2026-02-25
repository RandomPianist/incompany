<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\Api2Controller;
use App\Http\Controllers\ErpController;
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
    Route::group(["prefix" => "maquinas"], function() {
        Route::post("/listar",    [ErpController::class, "maquinas_listar"]);
        Route::post("/consultar", [ErpController::class, "maquinas_consultar"]);
        Route::post("/salvar",    [ErpController::class, "maquinas_salvar"]);
        Route::post("/inativar",  [ErpController::class, "maquinas_inativar"]);
    });

    Route::group(["prefix" => "produtos"], function() {
        Route::post("/listar",   [ErpController::class, "produtos_listar"]);
        Route::post("/salvar",   [ErpController::class, "produtos_salvar"]);
        Route::post("/inativar", [ErpController::class, "produtos_inativar"]);
        Route::post("/estoque",  [ErpController::class, "estoque"]);
    });
});

Route::group(["prefix" => "app"], function() {
    Route::get ("/teste",                  [ApiController::class, "teste"]);
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
    });
});