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
                data.consulta.forEach((linha) => {
                    resultado += "<tr>" +
                        "<td class = 'text-right' width = '10%'>" + linha.id.toString().padStart(4, "0") + "</td>" +
                        "<td width = '35%'>" + linha.descr + "</td>" +
                        "<td width = '40%'>" + linha.empresa + "</td>" +
                        "<td class = 'text-center btn-table-action' width = '15%'>" +
                            "<i class = 'my-icon far fa-box'    title = 'Atribuir produto' onclick = 'atribuicao(false, " + linha.id + ")'></i>" +
                            "<i class = 'my-icon far fa-tshirt' title = 'Atribuir grade'   onclick = 'atribuicao(true, " + linha.id + ")'></i>" +
                            (
                                !parseInt(data.empresa) ?
                                    "<i class = 'my-icon far fa-edit'      title = 'Editar'  onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                                    "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excluir(" + linha.id + ", " + '"/setores"' + ")'></i>"
                                : ""
                            ) +
                        "</td>" +
                    "</tr>";
                });
                document.getElementById("table-dados").innerHTML = resultado;
                ordenar(coluna);
            });
        }

        function validar() {
            limpar_invalido();

            const id = parseInt(document.getElementById("id").value);
            const _descr = document.getElementById("descr").value.toUpperCase().trim();

            let lista = ["descr"];
            Array.from(document.getElementsByClassName("validar")).forEach((el) => {
                lista.push(el.id);
            })
            erro = verifica_vazios(lista).erro;

            if (
                id &&
                !erro &&
                document.getElementById("cria_usuario-chk").checked == ant_usr &&
                _descr == anteriores.descr.toUpperCase().trim() &&
                document.getElementById("setor-empresa").value.toUpperCase().trim() == anteriores["setor-empresa"].toUpperCase().trim()
            ) erro = "Não há alterações para salvar";

            $.get(URL + "/setores/consultar", {
                descr : _descr,
                id_empresa : document.getElementById("setor-id_empresa").value,
                empresa : document.getElementById("setor-empresa").value
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
            $(el).prev().val(el.checked ? "S" : "N");
            const id = parseInt(document.getElementById("id").value);
            let tudo = document.querySelector("#setoresModal .container");
            if (id) {
                $(".linha-usuario").each(function() {
                    $(this).remove();
                });
                if (el.checked) {
                    $.get(URL + "/setores/pessoas/" + id, function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        for (let i = 1; i <= data.length; i++) {
                            let linha = document.createElement("div");
                            linha.classList.add("row", "linha-usuario", "mb-2");

                            let col_email = document.createElement("div");
                            col_email.classList.add("col-6", "pr-1");

                            let col_senha = document.createElement("div");
                            col_senha.classList.add("col-6", "pl-1");

                            let el_id_pessoa = document.createElement("input");
                            el_id_pessoa.type = "hidden";
                            el_id_pessoa.name = "id_pessoa[]";
                            el_id_pessoa.value = data[i - 1].id;

                            let el_nome_pessoa = document.createElement("input");
                            el_nome_pessoa.type = "hidden";
                            el_nome_pessoa.name = "nome[]";
                            el_nome_pessoa.value = data[i - 1].nome;

                            let el_email = document.createElement("input");
                            el_email.classList.add("form-control", "validar");
                            el_email.type = "text";
                            el_email.name = "email[]";
                            el_email.placeholder = "Email de " + data[i - 1].nome;
                            el_email.id = "email-" + i;

                            let el_senha = document.createElement("input");
                            el_senha.classList.add("form-control", "validar");
                            el_senha.type = "password";
                            el_senha.name = "password[]";
                            el_senha.placeholder = "Senha de " + data[i - 1].nome;
                            el_senha.id = "senha-" + i;

                            col_email.appendChild(el_email);
                            col_senha.appendChild(el_senha);
                            linha.appendChild(el_id_pessoa);
                            linha.appendChild(el_nome_pessoa);
                            linha.appendChild(col_email);
                            linha.appendChild(col_senha);
                            tudo.appendChild(linha);
                        }
                        let lista = document.getElementsByClassName("linha-usuario");
                        lista[lista.length - 1].classList.add("mb-4");
                        $(".form-control").each(function() {
                            $(this).keydown(function() {
                                $(this).removeClass("invalido");
                            });
                        });
                    });
                } else {
                    $.get(URL + "/setores/usuarios/" + id, function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        if (!parseInt(data.bloquear)) {
                            for (let i = 1; i <= data.consulta.length; i++) {
                                let linha = document.createElement("div");
                                linha.classList.add("row", "linha-usuario", "mb-2");

                                let col_senha = document.createElement("div");
                                col_senha.classList.add("col-12");

                                let el_id_pessoa = document.createElement("input");
                                el_id_pessoa.type = "hidden";
                                el_id_pessoa.name = "id_pessoa[]";
                                el_id_pessoa.value = data.consulta[i - 1].id;

                                let el_senha = document.createElement("input");
                                el_senha.classList.add("form-control", "validar");
                                el_senha.type = "password";
                                el_senha.name = "password[]";
                                el_senha.placeholder = "Senha de " + data.consulta[i - 1].nome;
                                el_senha.id = "senha-" + i;
                                el_senha.onkeyup = function() {
                                    numerico(el_senha);
                                }

                                col_senha.appendChild(el_senha);
                                linha.appendChild(el_id_pessoa);
                                linha.appendChild(col_senha);
                                tudo.appendChild(linha);
                            }
                            let lista = document.getElementsByClassName("linha-usuario");
                            lista[lista.length - 1].classList.add("mb-4");
                            $(".form-control").each(function() {
                                $(this).keydown(function() {
                                    $(this).removeClass("invalido");
                                });
                            });
                        } else {
                            s_alert("Alterar essa opção apagaria seu usuário");
                            el.checked = true;
                        }
                    });
                }
            }
        }
    </script>

    @include("modals.setores_modal")
    @include("modals.atribuicoes_modal")
@endsection