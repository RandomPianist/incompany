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
                        legenda = "Essa opção está desativada porque não é possível " + (parseInt(_data[_x] ? "retirar de" : "atribuir a")) + " um setor uma permissão que seu usuário não tem";
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
        modal("setoresModal", id, function() {
            $("#cria_usuario").val("N");
            $("#cria_usuario-chk").prop("checked", false);
        });
    }
}