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
                if (permissoes.atribuicoes) resultado += "<i class = 'my-icon far fa-box' title = 'Atribuir produto' onclick = 'atribuicao = new Atribuicoes(false, " + linha.id + ")'></i>";
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
    setor = new Setor(id);
}