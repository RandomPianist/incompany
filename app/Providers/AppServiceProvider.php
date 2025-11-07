<?php

namespace App\Providers;

use DB;
use Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use App\Models\Empresas;
use App\Models\Pessoas;
use App\Http\Traits\GlobaisTrait;

class AppServiceProvider extends ServiceProvider
{
    use GlobaisTrait;
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        View::share("root_url", config("app.root_url"));

        if (strpos(Route::getCurrentRoute(), "api") === false) {
            View::composer('*', function ($view) {
                if (Auth::user() !== null) {
                    $consulta = DB::table("atribuicoes")
                        ->selectRaw("MAX(qtd) AS qtd")
                        ->get();
                    $max_atb = sizeof($consulta) ? $consulta[0]->qtd : 0;
                    $emp = Empresas::find($this->obter_empresa()); // App\Http\Traits\GlobaisTrait.php
                    $view->with([
                        'admin' => $emp === null,
                        'empresa_descr' => $emp !== null ? $emp->nome_fantasia : "Todas",
                        'max_atb' => $max_atb,
                        'pessoa' => Pessoas::find(Auth::user()->id_pessoa)
                    ]);
                }
            });

            View::composer(['produtos', 'setores', 'empresas', 'maquinas', 'categorias'], function ($view) {
                $view->with('ultima_atualizacao', $this->log_consultar($view->getName())); // App\Http\Traits\GlobaisTrait.php
            });
        }
    }
}
