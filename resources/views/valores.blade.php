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
            @include("components.busca")
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
            @include("components.table_dados")
        </div>
    </div>
    @include("components.naoencontrado")
    @if (!intval(App\Models\Pessoas::find(Auth::user()->id_pessoa)->id_empresa))
        @include("components.add")
    @endif
    <script type = "text/javascript" language = "JavaScript">
        function listar(coluna) {
            $.get(URL + "/valores/{{ $alias }}/listar", {
                filtro : $("#busca").val()
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
                    $("#table-dados").html(resultado);
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
            if (!$("#descr").val()) erro = "Preencha o campo";
            if (!erro && $("#descr").val().toUpperCase().trim() == anteriores.descr.toUpperCase().trim()) erro = "Não há alterações para salvar";
            $.get(URL + "/valores/{{ $alias }}/consultar/", {
                descr : $("#descr").val().toUpperCase().trim()
            }, function(data) {
                if (!erro && parseInt(data) && !parseInt($("#id").val())) erro = "Já existe um registro com essa descrição";
                if (erro) {
                    $("#descr").addClass("invalido");
                    s_alert(erro);
                } else $("#valoresModal form").submit();
            });
        }

        function chamar_modal(id) {
            $("#valoresModalLabel").html((id ? "Editando" : "Cadastrando") + " {{ $titulo }}".toLowerCase().substring(0, "{{ $titulo }}".length));
            if (id) {
                $.get(URL + "/valores/{{ $alias }}/mostrar/" + id, function(descr) {
                    $("#descr").val(descr);
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