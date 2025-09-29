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
                    "<td class = 'text-center btn-table-action' width = '15%'>";
                if (permissoes.atribuicoes) {
                    resultado += "<i class = 'my-icon far fa-box' title = 'Atribuir produto' onclick = 'atribuicao = new Atribuicoes(false, " + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-tshirt'       title = 'Atribuir grade'   onclick = 'atribuicao = new Atribuicoes(true, " + linha.id + ")'></i>";
                }
                resultado += "<i class = 'my-icon far fa-edit' title = 'Editar'  onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excluir(" + linha.id + ", " + '"/setores"' + ")'></i>" +
                    "</td>" +
                "</tr>";
            });
            $("#table-dados").html(resultado);
            ordenar(coluna);
        } else mostrarImagemErro();
    });
}

function chamar_modal(id) {
    $("#setoresModalLabel").html((id ? "Editando" : "Cadastrando") + " centro de custo");
    if (id) {
        $.get(URL + "/setores/mostrar/" + id, function(data) {
            const explicar = function(_data, _x) {
                let legenda = "";
                switch(_data[_x + "_motivo"]) {
                    case "SYS":
                        legenda = "Não é possível editar essa configuração em um setor do sistema";
                        break;
                    case "PER":
                        legenda = "Não é possível " + (parseInt(_data[_x] ? "retirar de" : "atribuir a")) + " um setor uma permissão que seu usuário não tem";
                        break;
                    case "USU":
                        legenda = "Alterar essa opção apagaria seu usuário";
                        break;
                }
                if (legenda) $("#" + _x + "-lbl").attr("title", legenda);
                else $("#" + _x + "-lbl").removeAttr("title");
            }

            if (typeof data == "string") data = $.parseJSON(data);
            $("#descr").val(data.descr);
            $("#setor-id_empresa").val(data.id_empresa);
            $("#setor-empresa").val(data.empresa);
            $("#cria_usuario").val(parseInt(data.cria_usuario) ? "S" : "N");
            $("#cria_usuario-chk").prop("checked", $("#cria_usuario").val() == "S");
            explicar(data, "cria_usuario");
            for (x in permissoes) {
                $("#" + x).val(parseInt(data[x]) ? "S" : "N");
                $("#" + x + "-chk").prop("checked", $("#" + x).val() == "S").attr("disabled", data[x + "_motivo"] ? true : false);
                explicar(data, x);
            }
            modal("setoresModal", id);
        });
    } else {
        modal("setoresModal", 0, function() {
            let lista = [["usuarios", "cria_usuario"]];
            for (x in permissoes) {
                if (x != "usuarios") lista.push([x]);
            }
            lista.forEach((el) => {
                el.forEach((_id) => {
                    $("#" + _id).val("N");
                    $("#" + _id + "-chk").prop("checked", false);
                    if (!permissoes[el[0]]) $("#" + _id + "-lbl").attr("title", "Não é possível atribuir a um setor uma permissão que seu usuário não tem");
                    else $("#" + _id + "-lbl").removeAttr("title");
                })
            });
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
    });
    if (!id) return;
    if ($(el).prop("checked")) {
        $.get(URL + "/setores/pessoas/" + id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            let resultado = "";
            for (let i = 1; i <= data.length; i++) {
                resultado += "<div class = 'row linha-usuario mb-2'>" +
                    "<input type = 'hidden' name = 'id_pessoa[]' value = '" + data[i - 1].id + "' />" +
                    "<div class = 'col-4 pr-1'>" +
                        "<input type = 'text' name = 'email[]' class = 'validar form-control' id = 'email-" + i + "' placeholder = 'Email de " + data[i - 1].nome + "' />" +
                    "</div>" +
                    "<div class = 'col-4 px-1'>" +
                        "<input type = 'text' name = 'phone[]' class = 'validar form-control' id = 'phone-" + i + "' placeholder = 'Telefone de " + data[i - 1].nome + "' />" +
                    "</div>" +
                    "<div class = 'col-4 pl-1'>" +
                        "<input type = 'text' name = 'password[]' class = 'validar form-control' id = 'senha-" + i + "' placeholder = 'Senha de " + data[i - 1].nome + "' />" +
                    "</div>" +
                "</div>";
            }
            escrever($("#setoresModal .container"), resultado);
        });
    } else {
        $.get(URL + "/setores/usuarios/" + id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            let resultado = "";
            for (let i = 1; i <= data.length; i++) {
                resultado += "<div class = 'row linha-usuario mb-2'>" +
                    "<input type = 'hidden' name = 'id_pessoa[]' value = '" + data[i - 1].id + "' />" +
                    "<div class = 'col-12'>" +
                        "<input type = 'text' name = 'password[]' class = 'validar form-control' id = 'senha-" + i + "' placeholder = 'Senha de " + data[i - 1].nome + "' onkeyup = 'numerico(this)' />" +
                    "</div>" +
                "</div>";
            }
            escrever($("#setoresModal .container"), resultado);
        });
    }
}