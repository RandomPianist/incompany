<?php

namespace App\Providers;

use Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use App\Models\Pessoas;
use App\Models\Empresas;
use App\Services\GlobaisService;

class AppServiceProvider extends ServiceProvider
{
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
            $servico = new GlobaisService;

            View::composer('*', function ($view) use($servico) {
                if (Auth::user() !== null) {
                    $emp = Empresas::find($servico->srv_obter_empresa()); // App\Services\GlobaisService.php
                    $view->with([
                        'admin' => $emp === null,
                        'empresa_descr' => $emp !== null ? $emp->nome_fantasia : "Todas"
                    ]);
                }
            });

            View::composer(['produtos', 'setores', 'empresas'], function ($view) use ($servico) {
                $view->with('ultima_atualizacao', $servico->srv_log_consultar($view->getName())); // App\Services\GlobaisService.php
            });
        }
    }
}
