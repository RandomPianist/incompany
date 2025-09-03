function estoque(id) {
    $.get(URL + "/valores/maquinas/mostrar/" + id, function(descr) {
        $("#estoqueModalLabel").html(descr + " - movimentar estoque");
        $(".id_maquina").each(function() {
            $(this).val(id);
        });
        modal2("estoqueModal", ["obs-1", "qtd-1"]);
        $("#obs-1").trigger("keyup");
        $("#qtd-1").trigger("keyup");
        $("#es-1").trigger("change");
    });
}

function validar_estoque() {
    let obter_vetor = function(classe) {
        let resultado = new Array();
        $("." + classe).each(function() {
            resultado.push(classe == "preco" ? $(this).val().replace(/\D/g, "") : $(this).val());
        });
        return resultado.join(",");
    }

    limpar_invalido();
    let lista = new Array();
    for (let i = 1; i <= document.querySelectorAll("#estoqueModal input[type=number]").length; i++) lista.push("produto-" + i, "qtd-" + i);
    let erro = verifica_vazios(lista).erro;
    $.get(URL + "/maquinas/estoque/consultar/", {
        produtos_descr : obter_vetor("produto"),
        produtos_id : obter_vetor("id-produto"),
        quantidades : obter_vetor("qtd"),
        es : obter_vetor("es"),
        precos : obter_vetor("preco"),
        id_maquina : $($(".id_maquina")[0]).val()
    }, function(data) {
        if (typeof data == "string") data = $.parseJSON(data);
        if (!erro && data.texto) {
            for (let i = 0; i < data.campos.length; i++) {
                let el = $("#" + data.campos[i]);
                $(el).val(data.valores[i]);
                $(el).trigger("keyup");
                $(el).addClass("invalido");
            }
            erro = data.texto;
        }
        if (!erro) {
            $(".preco").each(function() {
                $(this).val(parseInt($(this).val().replace(/\D/g, "")) / 100);
            });
            $("#estoqueModal form").submit();
        } else s_alert(erro);
    });
}

function carrega_obs(seq, focar) {
    switch($("#es-" + seq).val()) {
        case "E":
            var obs = "ENTRADA";
            break;
        case "S":
            var obs = "SAÍDA";
            break;
        default:
            var obs = "AJUSTE";
    }
    $("#obs-" + seq).val(obs);
    if (focar) $("#qtd-" + seq).focus();
}

function atualizaPreco(seq) {
    $.get(URL + "/maquinas/preco", {
        id_maquina : $($(".id_maquina")[0]).val(),
        id_produto : $("#id_produto-" + seq).val()
    }, function(preco) {
        let el_preco = $($($($("#id_produto-" + seq).parent()).parent()).find(".preco"));
        $(el_preco).val(preco);
        $(el_preco).trigger("keyup");
    })
}

function adicionar_campo() {
    const cont = $("#estoqueModal input[type=number]").length + 1;

    let linha = $($("#template-linha").html());

    $($(linha).find(".produto")[0]).attr("id", "produto-" + cont).data("input", "#id_produto-" + cont);
    $($(linha).find(".id-produto")[0]).attr("id", "id_produto-" + cont);
    $($(linha).find(".es")[0]).attr("id", "es-" + cont).html($("#es-1").html());
    $($(linha).find(".qtd")[0]).attr("id", "qtd-" + cont);
    $($(linha).find(".preco")[0]).attr("id", "preco-" + cont);
    $($(linha).find(".obs")[0]).attr("id", "obs-" + cont);

    $($(linha).find(".id-produto")[0]).on("change", () => atualizaPreco(cont));
    $($(linha).find(".es")[0]).on("change", () => carrega_obs(cont, true));

    $($(linha).find(".remove-produto")[0]).on("click", function() {
        $(linha).remove();
        ["produto","id_produto","es","qtd","preco","obs"].forEach((classe) => {
            $("." + classe).each(function(i) {
                $(this).attr("id", classe + "-" + (i + 1));
            });
        });
    });

    $("#estoqueModal .container").append($(linha));

    carrega_autocomplete();
    carrega_dinheiro();
    $($(linha).find(".obs")[0]).trigger("keyup");
    $($(linha).find(".qtd")[0]).trigger("keyup");
    carrega_obs(cont, false);

    $(".form-control").keydown(function() {
        $(this).removeClass("invalido");
    });
}