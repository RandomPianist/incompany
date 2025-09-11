function listar(coluna) {
    document.querySelector(".loader-container").classList.remove("d-none");
    $.get(URL + "/colaboradores/listar/", {
        filtro : $("#busca").val(),
        tipo : TIPO
    }, function(data) {
        let resultado = "";
        if (typeof data == "string") data = $.parseJSON(data);
        if (data.length) {
            esconderImagemErro();
            data.forEach((linha) => {
                let biometria = "";
                if (linha.possui_biometria.indexOf("possui") > -1) biometria = '<img src = "' + (linha.possui_biometria == "nao-possui" ? IMG_BIOMETRIA.replace("sim", "nao") : IMG_BIOMETRIA) + '" class = "imagem-biometria" />';
                resultado += "<tr>" +
                    "<td width = '5%'>" + biometria + "</td>" +
                    "<td width = '10%' class = 'text-right'>" + linha.id.toString().padStart(4, "0") + "</td>" +
                    "<td width = '25%'>" + linha.nome + "</td>" +
                    "<td width = '20%'>" + linha.empresa + "</td>" +
                    "<td width = '20%'>" + linha.setor + "</td>" +
                    "<td class = 'text-center btn-table-action' width = '20%'>";
                if (parseInt(linha.possui_retiradas)) {
                    resultado += "<i class = 'my-icon fa-light fa-file' title = 'Retiradas' onclick = 'retirada_pessoa(" + linha.id + ")'></i>";
                    if (!EMPRESA) resultado += "<i class = 'my-icon fa-regular fa-clock-rotate-left' title = 'Desfazer retiradas' onclick = 'desfazer_retiradas(" + linha.id + ")'></i>";
                }
                if (parseInt(linha.possui_atribuicoes)) resultado += "<i class = 'my-icon far fa-calendar-alt' title = 'Próximas retiradas' onclick = 'proximas_retiradas(" + linha.id + ")'></i>";
                resultado += "" +
                        "<i class = 'my-icon far fa-box'       title = 'Atribuir produto' onclick = 'atribuicao = new Atribuicoes(false, " + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-tshirt'    title = 'Atribuir grade'   onclick = 'atribuicao = new Atribuicoes(true, " + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-edit'      title = 'Editar'           onclick = 'pessoa = new Pessoa(" + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-trash-alt' title = 'Excluir'          onclick = 'excluir(" + linha.id + ", " + '"/colaboradores"' + ")'></i>" +
                    "</td>" +
                "</tr>";
            });
            $("#table-dados").html(resultado);
            ordenar(coluna);
        } else mostrarImagemErro();
        document.querySelector(".loader-container").classList.add("d-none");
    });
}

function proximas_retiradas(id_pessoa) {
    $("#table-ret-dados").html("");
    $("#table-ret").addClass("d-none");
    $.get(URL + "/colaboradores/mostrar/" + id_pessoa, function(resp) {
        if (typeof resp == "string") resp = $.parseJSON(resp);
        $("#proximasRetiradasModalLabel").html("Próximas retiradas (" + resp.nome + ")");
        modal("proximasRetiradasModal", 0, function() {
            $.get(URL + "/retiradas/proximas/" + id_pessoa, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                let referencia = false;
                let tamanho = false;
                let resultado = "";
                let maximo_verde = 0;
                let maximo_vermelho = 0;
                data.forEach((linha) => {
                    let dias = parseInt(linha.dias);
                    if (dias > 0) {
                        if (dias > maximo_verde) maximo_verde = dias;
                    } else {
                        if (Math.abs(dias) > maximo_vermelho) maximo_vermelho = Math.abs(dias);
                    }
                });
                const hex = ["11", "22", "33", "44", "55", "66", "77", "88", "99", "AA", "BB", "CC", "DD", "EE", "FF"];
                data.forEach((linha) => {
                    let dias = parseInt(linha.dias);
                    if (linha.tamanho) tamanho = true;
                    if (linha.referencia) referencia = true;
                    let op_verde = hex[parseInt((((dias / maximo_verde) * 100) * 14) / 100)];
                    let op_vermelho = hex[parseInt((((Math.abs(dias) / maximo_vermelho) * 100) * 14) / 100)];
                    resultado += "<tr>" +
                        "<td class = 'align-middle'>" + linha.id_produto.toString().padStart(6, "0") + "</td>" +
                        "<td class = 'align-middle'>" + linha.descr + "</td>" +
                        "<td class = 'align-middle'>" + linha.referencia + "</td>" +
                        "<td class = 'align-middle'>" + linha.tamanho + "</td>" +
                        "<td class = 'align-middle text-right'>" + linha.qtd + "</td>" +
                        "<td class = 'align-middle'>" + linha.proxima_retirada + "</td>" +
                        "<td class = 'align-middle text-right' style = 'background:" + (dias < 0 ? "#ff0000" + op_vermelho : "#00ff00" + op_verde) + "'>" + Math.abs(dias) + "</td>" +
                    "</tr>";
                });
                $("#table-ret-dados").html(resultado);
                $("#table-ret").removeClass("d-none");
                $(".tamanho").each(function() {
                    if (!tamanho) $(this).addClass("d-none");
                    else $(this).removeClass("d-none");
                });
                $(".referencia").each(function() {
                    if (!referencia) $(this).addClass("d-none");
                    else $(this).removeClass("d-none");
                });
            });
        });
    });
}

function retirada_pessoa(id_pessoa) {
    let req = {};
    ["inicio", "fim"].forEach((chave) => {
        req[chave] = "";
    });
    req.id_pessoa = id_pessoa;
    req.tipo = "A";
    req.rel_grupo = "pessoa";
    req.consumo = "todos";
    req.tipo_colab = "ativos";
    let link = document.createElement("a");
    link.href = URL + "/relatorios/retiradas?" + $.param(req);
    link.target = "_blank";
    link.click();
}

async function desfazer_retiradas(_id_pessoa) {
    const resp = await s_alert({
        icon : "warning",
        html : "Tem certeza que deseja desfazer as retiradas?<br>Essa alteração é irreversível.",
        invert : true
    });
    if (resp) {
        await $.post(URL + "/retiradas/desfazer", {
            _token : $("meta[name='csrf-token']").attr("content"),
            id_pessoa : _id_pessoa
        });
        location.reload();
    }
}

function chamar_modal(id) {
    pessoa = new Pessoa(id);
}