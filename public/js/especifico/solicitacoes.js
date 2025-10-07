function recalcular() {
    $("tbody .report-row").each(function() {
        let qtd = $(this).find(".sugerido").html();
        $($(this).find(".solicitado")[0]).html(qtd);
        $($(this).find(".qtd")[0]).val(qtd);
        let estilo = linha.querySelector(".fa-minus").style;
        if (!parseInt(qtd)) estilo.visibility = "hidden";
        else estilo.removeProperty("visibility");
    });
    
    $.post(URL + "/previas/excluir", {
        _token : $("meta[name='csrf-token']").attr("content"),
        id_comodato : $("#id_comodato").val()
    });
}

function calcular(el, val) {
    val += parseInt($($($(el).parent()).find(".qtd")[0]).val());
    let estilo = $($(el).parent()).find(".fa-minus").style;
    if (!val) estilo.visibility = "hidden";
    else estilo.removeProperty("visibility");
    $($($(el).parent()).find(".qtd")[0]).val(val);
    $($($(el).parent()).find(".solicitado")[0]).html(val);
    $.post(URL + "/previas/salvar", {
        _token : $("meta[name='csrf-token']").attr("content"),
        id_comodato : $("#id_comodato").val(),
        id_produto : $($($(el).parent()).find(".produto")[0]).val(),
        qtd : val
    });
}

function detalhar(_tipo, _id_produto) {
    $.get(URL + "/solicitacoes/mostrar", {
        id_produto : _id_produto,
        tipo : _tipo,
        id_maquina : $("#id_maquina").val(),
        inicio : INICIO,
        fim : FIM
    }, function(data) {
        if (typeof data == "string") data = $.parseJSON(data);
        let supervisor = false;
        let autor = false;
        let origem = false;
        let resultado = "<table class = 'report-body table table-sm table-bordered table-striped px-5'>" +
            "<thead>" +
                "<tr class = 'report-row'>" +
                    (_tipo == "R" ?
                        "<td width = '28%' class = 'text-left'>Funcionário</td>" +
                        "<td width = '27%' class = 'text-left supervisor'>Supervisor</td>" +
                        "<td width = '27%' class = 'text-left autor'>Autor</td>" +
                        "<td width = '10%'>Data</td>" +
                        "<td width = '8%' class = 'text-right'>Qtde.</td>"
                    :
                        "<td width = '82%' class = 'text-left origem'>Origem</td>" +
                        "<td width = '10%'>Data</td>" +
                        "<td width = '8%' class = 'text-right'>Qtde.</td>"
                    ) +
                "</tr>" +
            "</thead>" +
        "</table>" +
        "<div class = 'mb-3'>" +
            "<table class = 'report-body table table-sm table-bordered table-striped'>" +
                "<tbody>";
        data.forEach((linha) => {
            if (linha.supervisor) supervisor = true;
            if (linha.autor) autor = true;
            if (linha.origem) origem = true;
            resultado += "<tr class = 'report-row'>" +
                (_tipo == "R" ?
                    "<td width = '28%' class = 'text-left'>" + linha.funcionario + "</td>" +
                    "<td width = '27%' class = 'text-left supervisor'>" + linha.supervisor + "</td>" +
                    "<td width = '27%' class = 'text-left autor'>" + linha.autor + "</td>" +
                    "<td width = '10%'>" + linha.data + "</td>" +
                    "<td width = '8%' class = 'text-right'>" + linha.qtd + "</td>"
                :
                    "<td width = '82%' class = 'text-left origem'>" + linha.origem + "</td>" +
                    "<td width = '10%'>" + linha.data + "</td>" +
                    "<td width = '8%' class = 'text-right'>" + linha.qtd + "</td>"
                ) +
            "</tr>";
        });
        resultado += "</tbody></table></div>";
        switch (_tipo) {
            case "E":
                var titulo = "Entradas";
                break;
            case "S":
                var titulo = "Saídas avulsas";
                break;
            case "R":
                var titulo = "Retiradas";
                break;
        }
        s_alert({
            title : titulo,
            html : resultado
        });
        const lista = {
            c_autor : autor,
            c_supervisor : supervisor,
            c_origem : origem
        };
        for (let x in lista) {
            if (!lista[x]) {
                $("." + x.substring(2)).each(function() {
                    $(this).css("display", "none");
                });
            }
        }
    })
}

async function solicitar() {
    let total = 0;
    $(".qtd").each(function() {
        total += parseInt($(this).val());
    });
    if (!total) {
        s_alert({
            icon : "warning",
            html : "Nenhum item foi solicitado"
        });
        return;
    }
    let data = $.get(URL + "/solicitacoes/consultar/" + $("#id_comodato").val());
    if (typeof data == "string") data = $.parseJSON(data);
    if (!parseInt(data.continuar)) {
        if (parseInt(data.sou_autor) && data.status == "A") {
            const resp = await s_alert({
                icon : "warning",
                html : "Já há uma solicitação em aberto, feita no dia " + data.data + ", para a mesma máquina.<br>Gostaria de cancelar a última solicitação feita e sobrescrever por essa?",
                invert : true
            });
            if (resp) {
                await $.post(URL + "/solicitacoes/cancelar", {
                    _token : $("meta[name='csrf-token']").attr("content"),
                    id : data.id
                });
                $($("form")[0]).submit();
            }
        } else {
            if (!parseInt(data.sou_autor)) {
                var texto = "Há uma solicitação em " + (data.status == "A" ? "aberto" : "andamento") + ", feita por " + data.autor + " no dia " + data.data + ", para a mesma máquina";
                if (data.status == "A") texto += ".<br />Entre em contato com " + data.autor + " para cancelá-la.";
            } else var texto = "A solicitação que você fez no dia " + data.data + " já está em andamento e não é possível cancelá-la";
            s_alert({
                icon : "warning",
                html : texto
            });
        }
    } else $($("form")[0]).submit();
}

async function carregar() {
    let data = await $.get(URL + "/solicitacoes/meus-comodatos?id_maquina=" + $("#id_maquina").val());
    if (typeof data == "string") data = $.parseJSON(data);
    $("#id_comodato").val(data[0]);
    let _produtos = new Array();
    $("tbody .report-row").each(function() {
        _produtos.push($($(this).find(".produto")[0]).val());
    });
    let resp = await $.get(URL + "/previas/preencher", {
        id_comodato : data[0],
        produtos : _produtos.join(",")
    });
    resp = $.parseJSON(resp);
    for (let i = 0; i < resp.length; i++) {
        let qtd = parseInt(resp[i].qtd);                
        $($("#produto-" + resp[i].produto).find(".qtd")[0]).val(qtd);
        $($("#produto-" + resp[i].produto).find(".solicitado")[0]).html(qtd);
        let estilo = pai.querySelector(".fa-minus").style;
        if (!qtd) estilo.visibility = "hidden";
        else estilo.removeProperty("visibility");
    }
    $($("form")[0]).removeClass("d-none");
}