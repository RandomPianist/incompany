<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <meta name = "csrf-token" content = "{{ csrf_token() }}" />
        <title>Loja Incompany</title>
        <link rel = "icon shortcut" href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "icon"          href = "{{ asset('img/favicon.ico') }}" type = "image/x-icon" />
        <link href = "{{ asset('css/bootstrap.min.css') }}" rel = "stylesheet" />
        <link href = "{{ asset('css/jquery-ui.min.css') }}" rel = "stylesheet" />
        <link href = "{{ asset('css/my-style.css')      }}" rel = "stylesheet" />
        <link href = "{{ asset('css/fa.css')            }}" rel = "stylesheet" />
        <style type = "text/css">
            .form-search::after, .form-search-2::after {
                background: url("{{ config('app.root_url') }}/img/keyboard.png") no-repeat;
                background-size: contain;
                bottom: 4.5px;
                content: " ";
                position: absolute;
                right: 20px;
                height: 30px;
                width: 30px;
            }

            .form-search-2::after {
                right: 7px
            }

            .form-search-3::after {
                right: -14px;
                top: 31px
            }
        </style>
    </head>
    <body>
        <footer>
            <div class = "container-fluid">
                <p class = "footer-text">{{ $empresa_descr }}</p>
            </div>
        </footer>
        <div id = "app">
            <main class = "py-4">
                <div class = "main-toolbar shadow-sm">
                    <a id = "link-home" href = "{{ config('app.address') }}{{ config('app.root_url') }}">
                        <img src = "{{ asset('img/logo.png') }}" style = "height:100px">
                    </a>
                    <div class = "btn-toolbar px-3 mr-auto">
                        <a href = "#">
                            <img src = "{{ asset('img/corporativo.png') }}" class = "img-menu" />
                            <span>Corporativo</span>
                            <img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}">
                            <ul class = "dropdown-toolbar">
                                <li onclick = "redirect('{{ $root_url }}/empresas')">
                                    <span>Empresas</span>
                                </li>
                                <li onclick = "redirect('{{ $root_url }}/setores')">
                                    <span>Centro de custos</span>
                                </li>
                            </ul>
                        </a>
                        <a href = "#">
                            <img src = "{{ asset('img/pessoas.png') }}" class = "img-menu" />
                            <span>Pessoas</span>
                            <img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}">
                            <ul class = "dropdown-toolbar">
                                @if (!intval(App\Models\Pessoas::find(Auth::user()->id_pessoa)->id_empresa))
                                    <li onclick = "redirect('{{ $root_url }}/colaboradores/pagina/A')">
                                        <span>Administradores</span>
                                    </li>
                                @endif
                                <li onclick = "redirect('{{ $root_url }}/colaboradores/pagina/F')">
                                    <span>Funcionários</span>
                                </li>
                                <li onclick = "redirect('{{ $root_url }}/colaboradores/pagina/S')">
                                    <span>Supervisores</span>
                                </li>
                                <li onclick = "redirect('{{ $root_url }}/colaboradores/pagina/U')">
                                    <span>Usuários</span>
                                </li>
                            </ul>
                        </a>
                        @if (!intval(App\Models\Pessoas::find(Auth::user()->id_pessoa)->id_empresa))
                            <a href = "#">
                                <img src = "{{ asset('img/itens.png') }}" class = "img-menu" />
                                <span>Itens</span>
                                <img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}">
                                <ul class = "dropdown-toolbar">
                                    <li onclick = "redirect('{{ $root_url }}/valores/categorias')">
                                        <span>Categorias</span>
                                    </li>
                                    <li onclick = "redirect('{{ $root_url }}/produtos')">
                                        <span>Produtos</span>
                                    </li>
                                </ul>
                            </a>
                        @endif
                        <a href = "{{ config('app.root_url') }}/valores/maquinas">
                            <img src = "{{ asset('img/maquinas.png') }}"  class = "img-menu" />
                            <span>Máquinas</span>
                        </a>
                        <a href = "#">
                            <img src = "{{ asset('img/relatorios.png') }}" class = "img-menu" />
                            <span>Relatórios</span>
                            <img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}">
                            <ul class = "dropdown-toolbar">
                                <li>
                                    <span>Consumo<img class = "dropdown-icon" src = "/img/sort-down.png"></span>
                                    <ul class = "subdropdown-toolbar">
                                        <li onclick = "relatorio = new RelatorioRetiradas('pessoa')">por colaborador</li>
                                        <li onclick = "relatorio = new RelatorioRetiradas('setor')">por centro de custo</li>
                                    </ul>
                                </li>
                                <li onclick = "relatorio = new RelatorioControle()">
                                    <span>Controle de Entrega</span>
                                </li>

                                    <li onclick = "relatorio = new RelatorioBilateral('empresas-por-maquina')">
                                        <span>Empresas por máquina</span>
                                    </li>
                                    <li onclick = "relatorio = new RelatorioItens(false)">
                                        <span>Extrato de itens</span>
                                    </li>
                                @if (!intval(App\Models\Pessoas::find(Auth::user()->id_pessoa)->id_empresa))
                                    <li onclick = "window.open('{{ $root_url }}/relatorios/comodatos', '_blank')">
                                        <span>Locação</span>
                                    </li>
                                @endif
                                    <li onclick = "relatorio = new RelatorioBilateral('maquinas-por-empresa')">
                                        <span>Máquinas por empresa</span>
                                    </li>
                                
                                <li onclick = "relatorio = new RelatorioRanking()">
                                    <span>Ranking de retiradas</span>
                                </li>

                                    <li onclick = "relatorio = new RelatorioItens(true)">
                                        <span>Sugestão de compra</span>
                                    </li>
                            </ul>
                        </a>
                    </div>
                    <div class = "d-flex mx-3">
                        <div class = "user-card d-flex my-auto">
                            <div class = "user-pic mr-3">
                                <span class = "m-auto">
                                    @foreach(explode(" ", Auth::user()->name, 2) as $nome)
                                        {{ substr($nome, 0, 1) }}
                                    @endforeach
                                </span>
                            </div>
                            <div class = "user-name d-grid ml-1">
                                <div class = "m-auto">
                                    @php
                                        $full_name = explode(" ", trim(Auth::user()->name));
                                    @endphp
                                    <span class = "mt-2">{{ $full_name[0] }}</span>
                                    <span></span>
                                </div>
                            </div>
                            <img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}">
                            <ul class = "dropdown-toolbar-user">
                                @if (intval(Auth::user()->admin))
                                    <li onclick = "trocarEmpresaModal()">
                                        <span>Trocar empresa</span>
                                    </li>
                                @endif
                                <li onclick = "pessoa = new Pessoa(USUARIO)">
                                    <span>Editar</span>
                                </li>
                                <li onclick = "document.getElementById('logout-form').submit()">
                                    <span>Sair</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <form id = "logout-form" class = "d-none" action = "{{ route('logout') }}" method = "POST">
                        @csrf
                    </form>
                </div>

                @yield("content")
                
                @include("modals.pessoas_modal")
                @include("modals.trocar_empresa_modal")
                @include("modals.reports.bilateral_modal")
                @include("modals.reports.itens_modal")
                @include("modals.reports.retiradas_modal")
                @include("modals.reports.controle_modal")
                @include("modals.reports.ranking_modal")
            </main>
        </div>
        <div id = "loader">
            <div></div>
        </div>
        <script type = "text/javascript" language = "JavaScript">
            const URL = "{{ config('app.root_url') }}";
            const USUARIO = {{ Auth::user()->id_pessoa }};
            const EMPRESA = {{ App\Models\Pessoas::find(Auth::user()->id_pessoa)->id_empresa }};

            function redirect(url, bNew_Tab) {
                if (bNew_Tab) window.open(url, '_blank');
                else document.location.href = url;
            }
        </script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/sweetalert2.js')   }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/jquery.min.js')    }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/jquery-ui.min.js') }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/bootstrap.min.js') }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/highcharts.js')    }}"></script>
        @if (!intval(App\Models\Pessoas::find(Auth::user()->id_pessoa)->id_empresa) || ((isset($alias) ? $alias : "maquinas") == "maquinas"))
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/my-functions.js') }}"></script>
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/dinheiro.js')     }}"></script>
        @else
            <script type = "text/javascript" language = "JavaScript">
                window.onload = function() {
                    location.href = URL;
                }
            </script>
        @endif
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/pessoa.js') }}"></script>
    </body>
</html>
