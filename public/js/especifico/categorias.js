function listar(coluna) {
    $.get(URL + "/categorias/listar", {
        filtro : $("#busca").val()
    }, function(data) {
        let resultado = "";
        if (typeof data == "string") data = $.parseJSON(data);
        if (data.length) {
            esconderImagemErro();
            data.forEach((linha) => {
                resultado += "<tr>" +
                    "<td class = 'text-right' width = '10%'>" + linha.id.toString().padStart(4, "0") + "</td>" +
                    "<td width = '75%'>" + linha.descr + "</td>" +
                    "<td class = 'text-center btn-table-action' width = '15%'>";
                if (!EMPRESA) {
                    resultado += "<i class = 'my-icon far fa-edit' title = 'Editar' onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excluir(" + linha.id + ", " + '"/categorias"' + ")'></i>";
                }
                resultado += "</td></tr>";
            });
            $("#table-dados").html(resultado);
            ordenar(coluna);
        } else mostrarImagemErro();
    });
}

function validar() {
    limpar_invalido();
    let erro = "";
    if (!$("#descr").val()) erro = "Preencha o campo";
    if (!erro && $("#descr").val().toUpperCase().trim() == anteriores.descr.toUpperCase().trim()) erro = "Não há alterações para salvar";
    $.get(URL + "/categorias/consultar/", {
        descr : $("#descr").val().toUpperCase().trim()
    }, function(data) {
        if (!erro && parseInt(data) && !parseInt($("#id").val())) erro = "Já existe um registro com essa descrição";
        if (erro) {
            $("#descr").addClass("invalido");
            s_alert(erro);
        } else $("#categoriasModal form").submit();
    });
}

function chamar_modal(id) {
    $("#categoriasModalLabel").html((id ? "Editando" : "Cadastrando") + " categoria");
    if (id) {
        $.get(URL + "/categorias/mostrar/" + id, function(descr) {
            $("#descr").val(descr);
            modal("categoriasModal", id); 
        });
    } else modal("categoriasModal", id); 
}