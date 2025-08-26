@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3">{{ $titulo }}</h3>
                    </td>
                    <td class = "ultima-atualizacao">
                        <span class = "custom-label-form">{{ $ultima_atualizacao }}</span>
                    </td>
                </tr>
            </table>
            <div id = "filtro-grid-by0" class = "input-group col-12 mb-3" data-table = "#table-dados">
                <input id = "busca" type = "text" class = "form-control form-control-lg" placeholder = "Procurar por..." aria-label = "Procurar por..." aria-describedby = "btn-filtro" />
                <div class = "input-group-append">
                    <button class = "btn btn-secondary btn-search-grid" type = "button" onclick = "listar()">
                        <i class = "my-icon fas fa-search"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class = "custom-table card">
            <div class = "table-header-scroll">
                <table>
                    <thead>
                        <tr class = "sortable-columns" for = "#table-dados">
                            <th width = "10%" class = "text-right">
                                <span>Código</span>
                            </th>
                            <th width = "@if ($comodato) 30% @else 75% @endif">
                                <span>Descrição</span>
                            </th>
                            @if ($comodato)
                                <th width = "45%">
                                    <span>Locação</span>
                                </th>
                            @endif
                            <th width = "15%" class = "text-center nao-ordena">
                                <span>Ações</span>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
            <div class = "table-body-scroll custom-scrollbar">
                <table id = "table-dados" class = "table">
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class = "d-none" id = "nao-encontrado">
        <div class = "d-flex flex-column align-items-center justify-content-center">
            <img class = "imagem-erro" src = "{{ asset('img/not-found-error.png')}}"></img>
            <h1>Dados não encontrados</h1>
        </div>
    </div>
    @if (!intval(App\Models\Pessoas::find(Auth::user()->id_pessoa)->id_empresa))
        <button class = "btn btn-primary custom-fab" type = "button" onclick = "chamar_modal(0)">
            <i class = "my-icon fas fa-plus"></i>
        </button>
    @endif
    <script type = "text/javascript" language = "JavaScript">
        function listar(coluna) {
            $.get(URL + "/valores/{{ $alias }}/listar", {
                filtro : document.getElementById("busca").value
            }, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                if (data.length) {
                    esconderImagemErro();
                    data.forEach((linha) => {
                        resultado += "<tr>" +
                            "<td class = 'text-right' width = '10%'>" + linha.seq.toString().padStart(4, "0") + "</td>";
                        if ({{ $comodato ? "true" : "false"}}) {
                            resultado += "<td width = '30%'>" + linha.descr + "</td>" +
                                "<td width = '45%'>" + linha.comodato + "</td>";
                        } else resultado += "<td width = '75%'>" + linha.descr + "</td>";

                        resultado += "<td class = 'text-center btn-table-action' width = '15%'>";
                        if (linha.alias != "maquinas" || !EMPRESA) {
                            if (linha.alias == "maquinas") {
                                if (linha.tem_mov == "S") resultado += "<i class = 'my-icon fa-light fa-file' title = 'Extrato' onclick = 'extrato_maquina(" + linha.id + ")'></i>";
                                resultado += "<i class = 'my-icon fa-light fa-cubes' title = 'Estoque' onclick = 'estoque(" + linha.id + ")'></i>";
                                resultado += linha.comodato != "---" ?
                                    "<i class = 'my-icon fa-duotone fa-handshake-slash' title = 'Encerrar locação' onclick = 'encerrar(" + linha.id + ")'></i>"
                                :
                                    "<i class = 'my-icon far fa-handshake' title = 'Locar máquina' onclick = 'comodatar(" + linha.id + ")'></i>"
                                ;
                            }
                            resultado += "<i class = 'my-icon far fa-edit' title = 'Editar' onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                                "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excluir(" + linha.id + ", " + '"/valores/{{ $alias }}"' + ")'></i>";
                        }
                        if (linha.alias == "maquinas" && EMPRESA && linha.tem_cod == "S") resultado += "<i class = 'my-icon far fa-cart-arrow-down' title = 'Solicitar compra' onclick = 'relatorio = new RelatorioItens(true, " + linha.id + ")'></i>";
                        resultado += "</td></tr>";
                    });
                    document.getElementById("table-dados").innerHTML = resultado;
                    ordenar(coluna);
                } else mostrarImagemErro();
            });
        }

        function extrato_maquina(id_maquina) {
            let req = {};
            ["inicio", "fim", "id_produto"].forEach((chave) => {
                req[chave] = "";
            });
            req.lm = "S";
            req.id_maquina = id_maquina;
            let link = document.createElement("a");
            link.href = URL + "/relatorios/extrato?" + $.param(req);
            link.target = "_blank";
            link.click();
        }

        function validar() {
            limpar_invalido();
            let erro = "";
            let el = document.getElementById("descr");
            if (!el.value) erro = "Preencha o campo";
            if (!erro && el.value.toUpperCase().trim() == anteriores.descr.toUpperCase().trim()) erro = "Não há alterações para salvar";
            $.get(URL + "/valores/{{ $alias }}/consultar/", {
                descr : el.value.toUpperCase().trim()
            }, function(data) {
                if (!erro && parseInt(data) && !parseInt(document.getElementById("id").value)) erro = "Já existe um registro com essa descrição";
                if (erro) {
                    el.classList.add("invalido");
                    s_alert(erro);
                } else document.querySelector("#valoresModal form").submit();
            });
        }

        function chamar_modal(id) {
            let titulo = id ? "Editando" : "Cadastrando";
            titulo += " {{ $titulo }}".toLowerCase().substring(0, "{{ $titulo }}".length);
            document.getElementById("valoresModalLabel").innerHTML = titulo;
            if (id) {
                $.get(URL + "/valores/{{ $alias }}/mostrar/" + id, function(descr) {
                    document.getElementById("descr").value = descr;
                    modal("valoresModal", id); 
                });
            } else modal("valoresModal", id); 
        }
    </script>
    @if ($alias == "maquinas")
        @include("modals.estoque_modal")
        @include("modals.comodatos_modal")
    @endif
    @include("modals.valores_modal")
@endsection