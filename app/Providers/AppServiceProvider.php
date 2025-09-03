<?php

namespace App\Providers;

use Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use App\Models\Pessoas;
use App\Models\Empresas;

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
        if (strpos(Route::getCurrentRoute(), "api") === false) {
            View::composer('*', function ($view) {
                if (Auth::user() !== null) {
                    $emp = Empresas::find(Pessoas::find(Auth::user()->id_pessoa)->id_empresa);
                    $view->with([
                        'admin' => $emp === null,
                        'empresa_descr' => $emp !== null ? $emp->nome_fantasia : "Todas",
                        'root_url' => config("app.root_url")
                    ]);
                }
            });
        }
    }
}
