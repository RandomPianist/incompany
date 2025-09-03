let ant_usr = false;

function listar(coluna) {
    $.get(URL + "/setores/listar", {
        filtro : $("#busca").val()
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
            $("#table-dados").html(resultado);
            ordenar(coluna);
        } else mostrarImagemErro();
    });
}

function validar() {
    limpar_invalido();

    const _descr = $("#descr").toUpperCase().trim();
    const _empresa = $("#setor-empresa").val();

    let lista = ["descr"];
    $(".validar").each(function() {
        lista.push($(this).attr("id"));
    });
    erro = verifica_vazios(lista).erro;

    if (
        parseInt($("#id").val()) &&
        !erro &&
        $("#cria_usuario-chk").prop("checked") == ant_usr &&
        _descr == anteriores.descr.toUpperCase().trim() &&
        _empresa.toUpperCase().trim() == anteriores["setor-empresa"].toUpperCase().trim()
    ) erro = "Não há alterações para salvar";

    $.get(URL + "/setores/consultar", {
        descr : _descr,
        id_empresa : $("#setor-id_empresa").val(),
        empresa : _empresa
    }, function(data) {
        if (typeof data == "string") data = $.parseJSON(data);
        if (data.msg && !erro) {
            erro = data.msg;
            $("#" + data.el).addClass("invalido");
        }
        if (!erro) $("#setoresModal form").submit();
        else s_alert(erro);
    });
}

function chamar_modal(id) {
    $("#setoresModalLabel").html((id ? "Editando" : "Cadastrando") + " centro de custo");
    if (id) {
        $.get(URL + "/setores/mostrar/" + id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            $("#descr").val(data.descr);
            $("#setor-id_empresa").val(data.id_empresa);
            $("#setor-empresa").val(data.empresa);
            $("#cria_usuario").val(parseInt(data.cria_usuario) ? "S" : "N");
            $("#cria_usuario-chk").prop("checked", $("#cria_usuario").val() == "S");
            ant_usr = $("#cria_usuario-chk").prop("checked");
            modal("setoresModal", id);
        });
    } else {
        modal("setoresModal", id, function() {
            $("#cria_usuario").val("N");
            $("#cria_usuario-chk").prop("checked", false);
        });
    }
}

function muda_cria_usuario(el) {
    const escrever = function(container, texto) {
        $(container).append(texto);
        $("#setoresModal .linha-usuario:last-child").addClass("mb-4");
        $(".form-control").each(function() {
            $(this).keydown(function() {
                $(this).removeClass("invalido");
            });
        });
    }

    $(el).prev().val($(el).prop("checked") ? "S" : "N");
    const id = parseInt($("#id").val());
    $(".linha-usuario").each(function() {
        $(this).remove();
    })
    if (id) {
        $.get(URL + "/setores/permissao", function(permissao) {
            if (parseInt(permissao)) {
                if ($(el).prop("checked")) {
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
                        escrever($("#setoresModal .container"), resultado);
                    });
                } else {
                    $.get(URL + "/setores/usuarios/" + id, function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        let erro = "";
                        switch (parseInt(data.cod)) {
                            case 200:
                                let resultado = "";
                                for (let i = 1; i <= data.consulta.length; i++) {
                                    resultado += "<div class = 'row linha-usuario mb-2'>" +
                                        "<input type = 'hidden' name = 'id_pessoa[]' value = '" + data[i - 1].id + "' />" +
                                        "<div class = 'col-12'>" +
                                            "<input type = 'text' name = 'password[]' class = 'validar form-control' id = 'senha-" + i + "' placeholder = 'Senha de " + data[i - 1].nome + "' onkeyup = 'numerico(this)' />" +
                                        "</div>" +
                                    "</div>";
                                }
                                escrever($("#setoresModal .container"), resultado);
                                break;
                            case 400:
                                erro = "Alterar essa opção apagaria seu usuário";
                                break;
                            case 401:
                                erro = "Não é permitido alterar essa opção em um setor do sistema";
                                break;
                        }
                        if (erro) {
                            s_alert(erro);
                            $(el).prop("checked", true);
                            $(el).prev().val("S");
                        }
                    });
                }
            } else {
                setTimeout(function() {
                    $(el).prop("checked", !$(el).prop("checked"));
                    s_alert("Você não tem permissão para executar essa ação");
                }, 1);
            }
        });
    }
}