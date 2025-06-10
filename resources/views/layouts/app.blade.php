<!DOCTYPE html>
<html lang = "{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset = "utf-8" />
        <meta name = "viewport" content = "width=device-width, initial-scale=1" />
        <meta name = "csrf-token" content = "{{ csrf_token() }}" />
        <title>Kx-safe</title>
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
                    <a id = "link-home" href = "{{ config('app.root_url') }}">
                        <img src = "{{ asset('img/logo.png') }}" style = "height:100px">
                    </a>
                    <div class = "btn-toolbar px-3 mr-auto">
                        <a href = "#">
                            <img src = "{{ asset('img/corporativo.png') }}" class = "img-menu" />
                            <span>Corporativo</span>
                            <img class = "dropdown-icon" src = "{{ asset('img/sort-down.png') }}">
                            <ul class = "dropdown-toolbar">
                                <li onclick = "redirect('/empresas')">
                                    <span>Empresas</span>
                                </li>
                                <li onclick = "redirect('/setores')">
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
                                    <li onclick = "redirect('/colaboradores/pagina/A')">
                                        <span>Administradores</span>
                                    </li>
                                @endif
                                <li onclick = "redirect('/colaboradores/pagina/F')">
                                    <span>Funcionários</span>
                                </li>
                                <li onclick = "redirect('/colaboradores/pagina/S')">
                                    <span>Supervisores</span>
                                </li>
                                <li onclick = "redirect('/colaboradores/pagina/U')">
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
                                    <li onclick = "redirect('/valores/categorias')">
                                        <span>Categorias</span>
                                    </li>
                                    <li onclick = "redirect('/produtos')">
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
                                    <li onclick = "window.open('/relatorios/comodatos', '_blank')">
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
            <script type = "text/javascript" language = "JavaScript" src = "{{ asset('js/my-functions.js')  }}"></script>
        @else
            <script type = "text/javascript" language = "JavaScript">
                window.onload = function() {
                    location.href = URL;
                }
            </script>
        @endif
        <script type = "text/javascript" language = "JavaScript">
            function Pessoa(id) {
                let that = this;
                let ant_id_setor = 0;
                let ant_id_empresa = 0;

                this.toggle_user = function(setor) {
                    $.get(URL + "/setores/mostrar/" + setor, function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        Array.from(document.getElementsByClassName("usr-info")).forEach((el) => {
                            let pes_info = document.getElementById("pes-info").classList;
                            let palavras = document.getElementById("pessoasModalLabel").innerHTML.split(" ");
                            if (parseInt(data.cria_usuario)) {
                                el.classList.remove("d-none");
                                pes_info.add("d-none");
                                palavras[1] = "administrador";
                            } else {
                                el.classList.add("d-none");
                                pes_info.remove("d-none");
                                palavras[1] = "colaborador";
                            }
                            document.getElementById("pessoasModalLabel").innerHTML = palavras.join(" ");
                        });
                    })
                }

                this.validar = function() {
                    let validar_email = function(__email) {
                        if ((__email == null) || (__email.length < 4)) return false;
                        let partes = __email.split("@");
                        if (partes.length != 2) return false;
                        let pre = partes[0];
                        if (!pre.length) return false;
                        if (!/^[a-zA-Z0-9_.-/+]+$/.test(pre)) return false;
                        let partesDoDominio = partes[1].split(".");
                        if (partesDoDominio.length < 2) return false;
                        let valido = true;
                        partesDoDominio.forEach((parteDoDominio) => {
                            if (!parteDoDominio.length) valido = false;
                            if (!/^[a-zA-Z0-9-]+$/.test(parteDoDominio)) valido = false;
                        })
                        return valido;
                    }

                    that.alterarEmpresa(function() {
                        that.alterarSetor(function() {
                            limpar_invalido();
                            let erro = "";

                            const id_setor = document.getElementById("pessoa-id_setor").value;

                            let _email = document.getElementById("email");
                            let _cpf = document.getElementById("cpf");
                            let nome = document.getElementById("nome");

                            $.get(URL + "/setores/mostrar/" + id_setor, function(data) {
                                if (typeof data == "string") data = $.parseJSON(data);
                                if (parseInt(data.cria_usuario)) {
                                    if (!_email.value) {
                                        erro = "Preencha o campo";
                                        _email.classList.add("invalido");
                                    }
                                }
                                if (!_cpf.value) {
                                    if (!erro) erro = "Preencha o campo";
                                    else erro = "Preencha os campos";
                                    _cpf.classList.add("invalido");
                                }
                                let lista = ["nome", /*"pessoa-setor", "pessoa-empresa", */"funcao", "admissao", "supervisor"];
                                if (!parseInt(document.getElementById("pessoa-id").value)) lista.push(parseInt(data.cria_usuario) ? "password" : "senha");
                                let aux = verifica_vazios(lista, erro);
                                erro = aux.erro;
                                let alterou = aux.alterou;
                                
                                if (parseInt(data.cria_usuario)) {
                                    if (!erro && !validar_email(_email.value)) {
                                        erro = "E-mail inválido";
                                        _email.classList.add("invalido");
                                    }
                                    if (
                                        document.getElementById("password").value ||
                                        _email.value.toLowerCase() != anteriores.email.toLowerCase()
                                    ) alterou = true;
                                } else if (document.getElementById("senha").value) alterou = true;
                                
                                // if (!erro && !validar_cpf(_cpf.value)/* && _cpf.value.trim()*/) {
                                //     erro = "CPF inválido";
                                //     _cpf.classList.add("invalido");
                                // }
                                // if (_cpf.value != anteriores.cpf) alterou = true;
                                
                                if (!erro && document.getElementById("pessoa-id_setor").value != ant_id_setor) alterou = true;
                                if (!erro && document.getElementById("pessoa-id_empresa").value != ant_id_empresa) alterou = true;

                                aux = document.getElementById("admissao").value;
                                if (aux) {
                                    if (!erro && eFuturo(aux)) erro = "A admissão não pode ser no futuro";
                                }

                                $.get(URL + "/colaboradores/consultar/", {
                                    cpf : _cpf.value.replace(/\D/g, ""),
                                    email : _email.value,
                                    empresa : document.getElementById("pessoa-empresa").value,
                                    id_empresa : document.getElementById("pessoa-id_empresa").value,
                                    setor : document.getElementById("pessoa-setor").value,
                                    id_setor : document.getElementById("pessoa-id_setor").value
                                }, function(data) {
                                    if (typeof data == "string") data = $.parseJSON(data);
                                    if (!erro && data.tipo == "invalido") {
                                        erro = data.dado + " não encontrad" + (data.dado == "Empresa" ? "a" : "o");
                                        document.getElementById("pessoa-" + data.dado.toLowerCase() + "-select").classList.add("invalido");
                                    }
                                    if (!erro && data.tipo == "duplicado" && !parseInt(document.getElementById("pessoa-id").value)) {
                                        erro = "Já existe um registro com esse " + data.dado;
                                        document.getElementById(data.dado == "CPF" ? "cpf" : "email").classList.add("invalido");
                                    }
                                    if (!erro && !alterou && !document.querySelector("#pessoasModal input[type=file]").value) erro = "Altere pelo menos um campo para salvar";
                                    if (!erro) {
                                        _cpf.value = _cpf.value.replace(/\D/g, "");
                                        document.querySelector("#pessoasModal form").submit();
                                    } else s_alert(erro.replace("Setor", "Centro de custo"));
                                });
                            });
                        });
                    });
                }

                this.alterarEmpresa = function(callback) {
                    setTimeout(function() {
                        try {
                            document.getElementById("pessoa-empresa").value = document.querySelector("#pessoa-empresa-select option[value='" + document.getElementById("pessoa-id_empresa").value + "']").innerHTML;
                            document.getElementById("pessoa-id_empresa").value = document.getElementById("pessoa-empresa-select").value;
                        } catch(err) {
                            document.getElementById("pessoa-empresa").value = "--";
                            document.getElementById("pessoa-empresa-select").value = "0";
                            document.getElementById("pessoa-id_empresa").value = 0;
                        }
                        if (callback !== undefined) callback();
                    }, 0);
                }

                this.alterarSetor = function(callback) {
                    setTimeout(function() {
                        try {
                            document.getElementById("pessoa-setor").value = document.querySelector("#pessoa-setor-select option[value='" + document.getElementById("pessoa-id_setor").value + "']").innerHTML;
                            document.getElementById("pessoa-id_setor").value = document.getElementById("pessoa-setor-select").value; 
                        } catch(err) {
                            document.getElementById("pessoa-setor").value = "--";
                            document.getElementById("pessoa-setor-select").value = "0";
                            document.getElementById("pessoa-id_setor").value = 0;
                        }
                        if (callback !== undefined) callback();
                    }, 0);                    
                }

                let titulo = id ? "Editando" : "Cadastrando";
                titulo += " colaborador";
                document.getElementById("pessoasModalLabel").innerHTML = titulo;
                let estilo_bloco_senha = document.getElementById("password").parentElement.parentElement.style;
                if (id) {
                    $.get(URL + "/colaboradores/mostrar/" + id, function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        ["nome", "cpf", "pessoa-setor", "pessoa-empresa", "pessoa-id_setor", "pessoa-id_empresa", "email", "funcao", "admissao", "supervisor"].forEach((_id) => {
                            document.getElementById(_id).value = data[_id.replace("pessoa-", "")];
                        });
                        ant_id_setor = parseInt(document.getElementById("pessoa-id_setor").value);
                        ant_id_empresa = parseInt(document.getElementById("pessoa-id_empresa").value);
                        setTimeout(function() {
                            modal("pessoasModal", id, function() {
                                that.toggle_user(parseInt(data.id_setor));
                                estilo_bloco_senha.display = id != USUARIO && document.getElementById("pessoasModalLabel").innerHTML.indexOf("administrador") > -1 ? "none" : "";
                                document.getElementById("pessoa-setor-select").disabled = id == USUARIO;
                                document.getElementById("supervisor-chk").checked = parseInt(data.supervisor) == 1;
                                Array.from(document.getElementsByClassName("pessoa-senha")).forEach((el) => {
                                    el.innerHTML = "Senha:";
                                });
                                document.getElementById("pessoa-setor-select").value = data.id_setor;
                                document.getElementById("pessoa-empresa-select").value = data.id_empresa;
                                that.alterarEmpresa();
                                that.alterarSetor();
                                document.querySelector("#pessoasModal .user-pic").parentElement.classList.remove("d-none");
                                if (!data.foto) {
                                    let nome = data.nome.toUpperCase().replace("DE ", "").split(" ");
                                    iniciais = "";
                                    iniciais += nome[0][0];
                                    if (nome.length > 1) iniciais += nome[nome.length - 1][0];
                                    document.querySelector("#pessoasModal .user-pic span").innerHTML = iniciais;
                                }
                                foto_pessoa("#pessoasModal .user-pic", data.foto ? data.foto : "");
                            });
                        }, 0);
                    });
                } else {
                    setTimeout(function() {
                        modal("pessoasModal", id, function() {
                            that.toggle_user(id);
                            estilo_bloco_senha.removeProperty("display");
                            document.getElementById("pessoa-setor-select").disabled = false;
                            Array.from(document.getElementsByClassName("pessoa-senha")).forEach((el) => {
                                el.innerHTML = "Senha: *";
                            });
                            document.querySelector("#pessoasModal .user-pic").parentElement.classList.add("d-none");
                            document.getElementById("supervisor").value = "0";
                            const tipo = document.getElementById("titulo-tela").innerHTML.charAt(0);
                            if (tipo == "A" || tipo == "U") {
                                $.get(URL + "/setores/primeiro-admin", function(data) {
                                    if (typeof data == "string") data = $.parseJSON(data);
                                    document.getElementById("pessoa-setor-select").value = data.id;
                                    document.getElementById("pessoa-id_setor").value = data.id;
                                    that.toggle_user(data.id);
                                    that.alterarEmpresa();
                                    that.alterarSetor();
                                });
                            } else if (tipo == "S") {
                                document.getElementById("supervisor-chk").checked = true;
                                document.getElementById("supervisor").value = 1;
                                that.alterarEmpresa();
                                that.alterarSetor();
                            } else {
                                that.alterarEmpresa();
                                that.alterarSetor();
                            }
                        });
                    }, 0);
                }
            }
        </script>
    </body>
</html>
