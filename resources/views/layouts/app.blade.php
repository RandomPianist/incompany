<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <meta name = "csrf-token" content = "{{ csrf_token() }}" />
        <title>Loja Incompany</title>
        <link rel = "icon shortcut" href = "{{ asset('storage/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "icon"          href = "{{ asset('storage/favicon.ico') }}" type = "image/x-icon" />
        <link rel = "stylesheet"    href = "{{ asset('css/lib/bootstrap.min.css') }}" />
        <link rel = "stylesheet"    href = "{{ asset('css/geral/app.css')         }}" />
        <link rel = "stylesheet"    href = "{{ asset('css/lib/jquery-ui.min.css') }}" />
        <link rel = "stylesheet"    href = "{{ asset('css/lib/fa.css')            }}" />
        <link rel = "stylesheet"    href = "{{ asset('css/lib/select2.min.css')   }}" />
        <style type = "text/css">
            .form-search::after, .form-search-2::after {
                background: url("{{ asset('img/keyboard.png') }}") no-repeat;
                background-size: contain;
                bottom: 4.5px;
                content: " ";
                position: absolute;
                right: 20px;
                height: 30px;
                width: 30px
            }

            .form-search.new::before,
            .form-search.old::before {
                bottom: 10px;
                content: " ";
                position: absolute;
                left: 24px;
                height: 22px;
                width: 22px
            }

            .form-search.new::before {
                background: url("{{ asset('img/new.png') }}") no-repeat;
                background-size: contain
            }

            .form-search.old::before {
                background: url("{{ asset('img/old.png') }}") no-repeat;
                background-size: contain
            }

            .form-search-2::after {
                right: 7px
            }

            .form-search-3::after {
                right: -14px;
                top: 31px
            }

            .linha-atb {
                position: relative;
                display: inline-block;
                padding-left: 25px
            }

            .linha-atb.new::before,
            .linha-atb.old::before {
                content: " ";
                position: absolute;
                left: -1px;
                top: 1px;
                height: 22px;
                width: 22px
            }

            .linha-atb.new::before {
                background: url("{{ asset('img/new.png') }}") no-repeat;
                background-size: contain
            }

            .linha-atb.old::before {
                background: url("{{ asset('img/old.png') }}") no-repeat;
                background-size: contain
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
                    <a id = "link-home" href = "{{ config('app.address') }}{{ $root_url }}">
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
                                @if ($admin)
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
                        @if ($admin)
                            <a href = "#">
                                <img src = "{{ asset('img/itens.png') }}" class = "img-menu" />
                                <span>Itens</span>
                                <img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}">
                                <ul class = "dropdown-toolbar">
                                    <li onclick = "redirect('{{ $root_url }}/categorias')">
                                        <span>Categorias</span>
                                    </li>
                                    <li onclick = "redirect('{{ $root_url }}/produtos')">
                                        <span>Produtos</span>
                                    </li>
                                </ul>
                            </a>
                        @endif
                        <a href = "{{ $root_url }}/maquinas">
                            <img src = "{{ asset('img/maquinas.png') }}"  class = "img-menu" />
                            <span>Máquinas</span>
                        </a>
                        <a href = "#">
                            <img src = "{{ asset('img/relatorios.png') }}" class = "img-menu" />
                            <span>Relatórios</span>
                            <img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}">
                            <ul class = "dropdown-toolbar">
                                <li>
                                    <span>Pessoas & consumo<img class = "dropdown-icon" src = "{{ asset ('img/sort-down.png') }}"></span>
                                    <ul class = "subdropdown-toolbar">
                                        <li onclick = "relatorio = new RelatorioPessoas()">
                                            <span>Pessoas</span>
                                        </li>
                                        <li onclick = "relatorio = new RelatorioControle()">
                                            <span>Termo de retirada</span>
                                        </li>
                                        <li onclick = "relatorio = new RelatorioRetiradas('pessoa')">
                                            <span>Consumo por pessoa</span>
                                        </li>
                                        <li onclick = "relatorio = new RelatorioRetiradas('setor')">
                                            <span>Consumo por centro de custo</span>
                                        </li>
                                        <li onclick = "relatorio = new RelatorioRanking()">
                                            <span>Ranking de retiradas</span>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <span>Estoque<img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}"></span>
                                    <ul class = "subdropdown-toolbar">
                                        <li onclick = "relatorio = new RelatorioItens('E')">
                                            <span>Extrato de itens</span>
                                        </li>
                                        <li onclick = "relatorio = new RelatorioItens('P')">
                                            <span>Posição de estoque</span>
                                        </li>
                                        <li onclick = "relatorio = new RelatorioItens('S')">
                                            <span>Sugestão de compra</span>
                                        </li>
                                    </ul>
                                </li>
                                <li>
                                    <span>Outros<img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}"></span>
                                    <ul class = "subdropdown-toolbar">
                                        @if ($admin)
                                            <li onclick = "window.open('{{ $root_url }}/relatorios/comodatos', '_blank')">
                                                <span>Contratos</span>
                                            </li>
                                        @endif
                                        <li onclick = "relatorio = new RelatorioProdutos()">
                                            <span>Produtos</span>
                                        </li>
                                        <li onclick = "relatorio = new RelatorioBilateral('empresas-por-maquina')">
                                            <span>Empresas por máquina</span>
                                        </li>
                                        <li onclick = "relatorio = new RelatorioBilateral('maquinas-por-empresa')">
                                            <span>Máquinas por empresa</span>
                                        </li>
                                    </ul>
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
                                <li onclick = "$('#logout-form').submit()">
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
                @include("modals.reports.pessoas_modal")
                @include("modals.reports.bilateral_modal")
                @include("modals.reports.itens_modal")
                @include("modals.reports.retiradas_modal")
                @include("modals.reports.controle_modal")
                @include("modals.reports.ranking_modal")
                @include("modals.reports.produtos_modal")
            </main>
        </div>
        <div id = "loader">
            <div></div>
        </div>
        <script type = "text/javascript" language = "JavaScript">
            const URL = "{{ $root_url }}";
            const USUARIO = {{ Auth::user()->id_pessoa }};
            const EMPRESA = {{ App\Models\Pessoas::find(Auth::user()->id_pessoa)->id_empresa }};

            function redirect(url, bNew_Tab) {
                if (bNew_Tab) window.open(url, '_blank');
                else document.location.href = url;
            }
        </script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/sweetalert2.js')   }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/jquery.min.js')    }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/jquery-ui.min.js') }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/bootstrap.min.js') }}"></script>
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/lib/select2.min.js')   }}"></script>
        @if ($admin || (
            url()->current() != route('produtos') && 
            url()->current() != route('categorias')
        ))
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/classes/Atribuicoes.js')    }}"></script>
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/classes/CPMP.js')           }}"></script>
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/classes/Excecoes.js')       }}"></script>
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/classes/JanelaDinamica.js') }}"></script>
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/classes/Relatorios.js')     }}"></script>
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/geral/app.js')              }}"></script>
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/geral/mascaras.js')         }}"></script>
        @else
            <script type = "text/javascript" language = "JavaScript">
                window.onload = function() {
                    location.href = URL;
                }
            </script>
        @endif
        <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/geral/alerta.js') }}"></script>
    </body>
</html>
