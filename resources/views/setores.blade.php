@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3">Centro de custos</h3>
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
                            <th width = "35%">
                                <span>Descrição</span>
                            </th>
                            <th width = "40%">
                                <span>Empresa</span>
                            </th>
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
    <button class = "btn btn-primary custom-fab" type = "button" onclick = "chamar_modal(0)">
        <i class = "my-icon fas fa-plus"></i>
    </button>
    <script type = "text/javascript" language = "JavaScript">
        let ant_usr = false;

        function listar(coluna) {
            $.get(URL + "/setores/listar", {
                filtro : document.getElementById("busca").value
            }, function(data) {
                let resultado = "";
                while (typeof data == "string") data = $.parseJSON(data);
                if (data.length) {
                    esconderImagemErro();
                    data.forEach((linha) => {
                        resultado += "<tr>" +
                            "<td class = 'text-right' width = '10%'>" + linha.id.toString().padStart(4, "0") + "</td>" +
                            "<td width = '35%'>" + linha.descr + "</td>" +
                            "<td width = '40%'>" + linha.empresa + "</td>" +
                            "<td class = 'text-center btn-table-action' width = '15%'>" +
                                "<i class = 'my-icon far fa-box'    title = 'Atribuir produto' onclick = 'atribuicao(false, " + linha.id + ")'></i>" +
                                "<i class = 'my-icon far fa-tshirt' title = 'Atribuir grade'   onclick = 'atribuicao(true, " + linha.id + ")'></i>" +
                                (
                                    !EMPRESA ?
                                        "<i class = 'my-icon far fa-edit'      title = 'Editar'  onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                                        "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excluir(" + linha.id + ", " + '"/setores"' + ")'></i>"
                                    : ""
                                ) +
                            "</td>" +
                        "</tr>";
                    });
                    document.getElementById("table-dados").innerHTML = resultado;
                    ordenar(coluna);
                } else mostrarImagemErro();
            });
        }

        function validar() {
            limpar_invalido();

            const _descr = document.getElementById("descr").value.toUpperCase().trim();
            const _empresa = document.getElementById("setor-empresa").value;

            let lista = ["descr"];
            Array.from(document.getElementsByClassName("validar")).forEach((el) => {
                lista.push(el.id);
            })
            erro = verifica_vazios(lista).erro;

            if (
                parseInt(document.getElementById("id").value) &&
                !erro &&
                document.getElementById("cria_usuario-chk").checked == ant_usr &&
                _descr == anteriores.descr.toUpperCase().trim() &&
                _empresa.toUpperCase().trim() == anteriores["setor-empresa"].toUpperCase().trim()
            ) erro = "Não há alterações para salvar";

            $.get(URL + "/setores/consultar", {
                descr : _descr,
                id_empresa : document.getElementById("setor-id_empresa").value,
                empresa : _empresa
            }, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                if (data.msg && !erro) {
                    erro = data.msg;
                    document.getElementById(data.el).classList.add("invalido");
                }
                if (!erro) document.querySelector("#setoresModal form").submit();
                else s_alert(erro);
            });
        }

        function chamar_modal(id) {
            let titulo = id ? "Editando" : "Cadastrando";
            titulo += " centro de custo";
            document.getElementById("setoresModalLabel").innerHTML = titulo;
            let el_cria_usuario = document.getElementById("cria_usuario");
            let el_cria_usuario_chk = document.getElementById("cria_usuario-chk");
            if (id) {
                $.get(URL + "/setores/mostrar/" + id, function(data) {
                    if (typeof data == "string") data = $.parseJSON(data);
                    document.getElementById("descr").value = data.descr;
                    document.getElementById("setor-id_empresa").value = data.id_empresa;
                    document.getElementById("setor-empresa").value = data.empresa;
                    el_cria_usuario.value = parseInt(data.cria_usuario) ? "S" : "N";
                    el_cria_usuario_chk.checked = el_cria_usuario.value == "S";
                    ant_usr = el_cria_usuario_chk.checked;
                    modal("setoresModal", id);
                });
            } else {
                modal("setoresModal", id, function() {
                    el_cria_usuario.value = "N";
                    el_cria_usuario_chk.checked = false;
                    el_setor_padrao_chk.checked = false;
                });
            }
        }

        function muda_cria_usuario(el) {
            const escrever = function(container, texto) {
                tudo.innerHTML = resultado;
                document.querySelector("#setoresModal .linha-usuario:last-child").classList.add("mb-4");
                $(".form-control").each(function() {
                    $(this).keydown(function() {
                        $(this).removeClass("invalido");
                    });
                });
            }

            $(el).prev().val(el.checked ? "S" : "N");
            const id = parseInt(document.getElementById("id").value);
            let tudo = document.querySelector("#setoresModal .container");
            tudo.innerHTML = "";
            if (id) {
                $.get(URL + "/setores/permissao", function(permissao) {
                    if (parseInt(permissao)) {
                        if (el.checked) {
                            $.get(URL + "/setores/pessoas/" + id, function(data) {
                                if (typeof data == "string") data = $.parseJSON(data);
                                let resultado = "";
                                for (let i = 1; i <= data.length; i++) {
                                    resultado += "<div class = 'row linha-usuario mb-2'>" +
                                        "<input type = 'hidden' name = 'id_pessoa[]' value = '" + data[i - 1].id + "' />" +
                                        "<div class = 'col-6 pr-1'>" +
                                            "<input type = 'text' name = 'email[]' class = 'validar form-control' id = 'email-" + i + "' placeholder = 'Email de " + data[i - 1].nome + "' />" +
                                        "</div>" +
                                        "<div class = 'col-6 pl-1'>" +
                                            "<input type = 'text' name = 'password[]' class = 'validar form-control' id = 'senha-" + i + "' placeholder = 'Senha de " + data[i - 1].nome + "' />" +
                                        "</div>" +
                                    "</div>";
                                }
                                escrever(tudo, resultado);
                            });
                        } else {
                            $.get(URL + "/setores/usuarios/" + id, function(data) {
                                if (typeof data == "string") data = $.parseJSON(data);
                                if (!parseInt(data.bloquear)) {
                                    let resultado = "";
                                    for (let i = 1; i <= data.consulta.length; i++) {
                                        resultado += "<div class = 'row linha-usuario mb-2'>" +
                                            "<input type = 'hidden' name = 'id_pessoa[]' value = '" + data[i - 1].id + "' />" +
                                            "<div class = 'col-12'>" +
                                                "<input type = 'text' name = 'password[]' class = 'validar form-control' id = 'senha-" + i + "' placeholder = 'Senha de " + data[i - 1].nome + "' onkeyup = 'numerico(this)' />" +
                                            "</div>" +
                                        "</div>";
                                    }
                                    escrever(tudo, resultado);
                                } else {
                                    s_alert("Alterar essa opção apagaria seu usuário");
                                    el.checked = true;
                                    $(el).prev().val("S");
                                }
                            });
                        }
                    } else {
                        setTimeout(function() {
                            el.checked = !el.checked;
                            s_alert("Você não tem permissão para executar essa ação");
                        }, 1);
                    }
                });
            }
        }
    </script>

    @include("modals.setores_modal")
    @include("modals.atribuicoes_modal")
@endsection