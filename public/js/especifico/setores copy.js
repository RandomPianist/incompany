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
                        legenda = "Não é possível editar essa configuração em um centro de custo do sistema";
                        break;
                    case "PER":
                        legenda = "Não é possível " + (parseInt(_data[_x] ? "retirar de" : "atribuir a")) + " um centro de custo uma permissão que seu usuário não tem";
                        break;
                    case "USU":
                        legenda = "Alterar essa opção apagaria seu usuário";
                        break;
                }
                if (legenda) $("#setor-" + _x + "-lbl").attr("title", legenda);
                else $("#setor-" + _x + "-lbl").removeAttr("title");
            }

            if (typeof data == "string") data = $.parseJSON(data);
            $("#descr").val(data.descr);
            $("#setor-id_empresa").val(data.id_empresa);
            $("#setor-empresa").val(data.empresa).attr("disabled", data.empresa_motivo ? true : false);
            switch(data.empresa_motivo) {
                case "SYS":
                    $("#setor-empresa").attr("title", "Não é possível editar a empresa de um centro de custo do sistema");
                    break;
                case "PES":
                    $("#setor-empresa").attr("title", "Não é possível editar a empresa desse centro de custo porque há pessoas vinculadas a ele");
                    break;
                default:
                    $("#setor-empresa").removeAttr("title");
            }
            $("#cria_usuario").val(parseInt(data.cria_usuario) ? "S" : "N");
            $("#cria_usuario-chk").prop("checked", $("#cria_usuario").val() == "S");
            explicar(data, "cria_usuario");
            modal("setoresModal", id, function() {
                muda_cria_usuario($("#cria_usuario-chk"), function() {
                    for (x in permissoes) {
                        $("#setor-" + x).val(parseInt(data[x]) ? "S" : "N");
                        $("#setor-" + x + "-chk").prop("checked", $("#setor-" + x).val() == "S").attr("disabled", data[x + "_motivo"] ? true : false);
                        explicar(data, x);
                    }
                });
            });
        });
    } else {
        modal("setoresModal", 0, function() {
            $("#setor-empresa").removeAttr("title");
            muda_cria_usuario($("#cria_usuario-chk"), function() {
                let lista = [["usuarios", "cria_usuario"]];
                for (x in permissoes) {
                    if (x != "usuarios") lista.push([x]);
                }
                lista.forEach((el) => {
                    el.forEach((_id) => {
                        $("#" + _id).val("N");
                        $("#" + _id + "-chk").prop("checked", false).attr("disabled", !permissoes[el[0]] ? true : false);
                        if (!permissoes[el[0]]) $("#setor-" + _id + "-lbl").attr("title", "Não é possível atribuir a um centro de custo uma permissão que seu usuário não tem");
                        else $("#setor-" + _id + "-lbl").removeAttr("title");
                    });
                });
            });
        });
    }
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
    let alterouPermissoes = false;
    for (x in permissoes) {
        if ($("#" + permissoes[x]).val() != anteriores[permissoes[x]]) alterouPermissoes = true;
    }

    if (
        parseInt($("#id").val()) &&
        !erro &&
        !alterouPermissoes &&
        $("#cria_usuario").val() == anteriores.cria_usuario &&
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
        if (!erro) {
            $(".telefone").each(function() {
                $(this).val(apenasNumeros($(this).val()));
            });
            $("#setoresModal form").submit();
        } else s_alert(erro);
    });
}

function muda_cria_usuario(el, callback) {
    const escrever = function(container, texto) {
        if (texto) $("#setoresModal > .modal-dialog").addClass("modal-xl", "modal-xl-kx").removeClass("modal-lg");
        else $("#setoresModal > .modal-dialog").removeClass("modal-xl", "modal-xl-kx").addClass("modal-lg");
        $(container).append(texto + obterHtmlPermissoes(true, $(el).prop("checked")));
        permissoesListeners(false);
        $("#setoresModal .linha-usuario:last-child").addClass("mb-4");
        $(".form-control").each(function() {
            $(this).keydown(function() {
                $(this).removeClass("invalido");
            });
        });
        if (callback !== undefined) callback();
    }

    let obter_campo_senha = function(_i, _nome) {
        return "<input type = 'password' name = 'password[]' class = 'validar form-control' id = 'senha-" + _i + "' placeholder = 'Senha de " + _nome + "' />"
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
            const mostrar_email = parseInt(data.mostrar_email);
            const mostrar_fone = parseInt(data.mostrar_fone);
            let resultado = "";
            for (let i = 1; i <= data.consulta.length; i++) {
                let nome = data.consulta[i - 1].nome;
                resultado += "<div class = 'row linha-usuario mb-2'>" +
                    "<input type = 'hidden' name = 'id_pessoa[]' value = '" + data.consulta[i - 1].id + "' />";
                let campo_email = "<input type = 'text' name = 'email[]' class = 'validar form-control' id = 'email-" + i + "' placeholder = 'Email de " + nome + "' value = '" + data.consulta[i - 1].email + "' />";
                let campo_fone = "<input type = 'text' name = 'phone[]' class = 'validar telefone form-control' id = 'phone-" + i + "' placeholder = 'Telefone de " + nome + "' value = '" + data.consulta[i - 1].telefone + "' onkeyup = 'this.value=phoneMask(this.value)' />";
                if (mostrar_email && mostrar_fone) {
                    resultado += "<div class = 'col-4 pr-1'>" +
                        campo_email +
                    "</div>" +
                    "<div class = 'col-4 px-1'>" +
                        campo_fone +
                    "</div>" +
                    "<div class = 'col-4 pl-1'>" +
                        obter_campo_senha(i, nome) +
                    "</div>";
                } else if (mostrar_email) {
                    resultado += "<div class = 'col-6 pr-1'>" +
                        campo_email +
                    "</div>" +
                    "<div class = 'col-6 pl-1'>" +
                        obter_campo_senha(i, nome) +
                    "</div>";
                } else if (mostrar_fone) {
                    resultado += "<div class = 'col-4 pr-1'>" +
                        campo_fone +
                    "</div>" +
                    "<div class = 'col-4 pl-1'>" +
                        obter_campo_senha(i, nome) +
                    "</div>";
                } else {
                    resultado += "<div class = 'col-12'>" +
                        obter_campo_senha(i, nome) +
                    "</div>";
                }
                resultado += "</div>";
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
                        obter_campo_senha(i, data.nome[i - 1]) +
                    "</div>" +
                "</div>";
            }
            escrever($("#setoresModal .container"), resultado);
        });
    }
}