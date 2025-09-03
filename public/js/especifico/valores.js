function listar(coluna) {
    $.get(URL + "/valores/" + ALIAS + "/listar", {
        filtro : $("#busca").val()
    }, function(data) {
        let resultado = "";
        if (typeof data == "string") data = $.parseJSON(data);
        if (data.length) {
            esconderImagemErro();
            data.forEach((linha) => {
                resultado += "<tr>" +
                    "<td class = 'text-right' width = '10%'>" + linha.seq.toString().padStart(4, "0") + "</td>";
                if (comodato) {
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
                        "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excluir(" + linha.id + ", " + '"/valores/" + ALIAS + ""' + ")'></i>";
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
    $.get(URL + "/valores/" + ALIAS + "/consultar/", {
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
    $("#valoresModalLabel").html((id ? "Editando" : "Cadastrando") + " " + TITULO.toLowerCase().substring(0, TITULO.length));
    if (id) {
        $.get(URL + "/valores/" + ALIAS + "/mostrar/" + id, function(descr) {
            $("#descr").val(descr);
            modal("valoresModal", id); 
        });
    } else modal("valoresModal", id); 
}