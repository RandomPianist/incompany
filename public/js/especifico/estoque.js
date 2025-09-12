function estoque(id) {
    $.get(URL + "/maquinas/mostrar/" + id, function(maq) {
        if (typeof maq == "string") maq = $.parseJSON(maq);
        $("#estoqueModalLabel").html(maq.descr + " - movimentar estoque");
        $(".id_maquina").each(function() {
            $(this).val(id);
        });
        modal2("estoqueModal", ["obs-1", "qtd-1"]);
        $("#estoqueModal #obs-1").trigger("keyup");
        $("#estoqueModal #qtd-1").trigger("keyup");
        $("#estoqueModal #es-1").trigger("change");
    });
}

function validar_estoque() {
    limpar_invalido();
    let lista = new Array();
    for (let i = 1; i <= document.querySelectorAll("#estoqueModal input[type=number]").length; i++) lista.push("produto-" + i, "qtd-" + i);
    let erro = verifica_vazios(lista, "", "estoqueModal").erro;
    $.get(URL + "/maquinas/estoque/consultar", {
        produtos_descr : obter_vetor("produto", "estoque"),
        produtos_id : obter_vetor("id-produto", "estoque"),
        quantidades : obter_vetor("qtd", "estoque"),
        es : obter_vetor("es", "estoque"),
        precos : obter_vetor("preco", "estoque"),
        id_maquina : $($(".id_maquina")[0]).val()
    }, function(data) {
        if (typeof data == "string") data = $.parseJSON(data);
        if (!erro && data.texto) {
            for (let i = 0; i < data.campos.length; i++) {
                let el = $("#estoqueModal #" + data.campos[i]);
                $(el).val(data.valores[i]);
                $(el).trigger("keyup");
                $(el).addClass("invalido");
            }
            erro = data.texto;
        }
        if (!erro) {
            $("#estoqueModal .preco").each(function() {
                $(this).val(parseInt(apenasNumeros($(this).val())) / 100);
            });
            $("#estoqueModal form").submit();
        } else s_alert(erro);
    });
}

function carrega_obs(seq, focar) {
    switch($("#estoqueModal #es-" + seq).val()) {
        case "E":
            var obs = "ENTRADA";
            break;
        case "S":
            var obs = "SAÍDA";
            break;
        default:
            var obs = "AJUSTE";
    }
    $("#estoqueModal #obs-" + seq).val(obs);
    if (focar) $("#estoqueModal #qtd-" + seq).focus();
}

function adicionar_campo_estoque() {
    const cont = $("#estoqueModal input[type=number]").length + 1;

    let linha = $($("#estoqueModal #template-linha").html());

    $($(linha).find(".produto")[0]).attr("id", "produto-" + cont).attr("data-input", "#estoqueModal #id_produto-" + cont);
    $($(linha).find(".id-produto")[0]).attr("id", "id_produto-" + cont);
    $($(linha).find(".es")[0]).attr("id", "es-" + cont).html($("#es-1").html());
    $($(linha).find(".qtd")[0]).attr("id", "qtd-" + cont);
    $($(linha).find(".preco")[0]).attr("id", "preco-" + cont);
    $($(linha).find(".obs")[0]).attr("id", "obs-" + cont);

    $($(linha).find(".id-produto")[0]).off("change").on("change", () => atualizaPreco(cont, "estoque"));
    $($(linha).find(".es")[0]).off("change").on("change", () => carrega_obs(cont, true));
    $($(linha).find(".atalho")[0]).attr("data-campo_id", "#estoqueModal #id_produto-" + cont);
    $($(linha).find(".atalho")[0]).attr("data-campo_descr", "#estoqueModal #produto-" + cont);
    carrega_atalhos();

    $($(linha).find(".remove-linha")[0]).on("click", function() {
        $(linha).remove();
        ["produto", "id_produto", "es", "qtd", "preco", "obs", "atalho"].forEach((classe) => {
            $("#estoqueModal ." + classe).each(function(i) {
                $(this).attr("id", classe + "-" + (i + 1));
                if ($(this).hasClass("atalho")) {
                    $(this).attr("data-campo_id", "#estoqueModal #id_produto-" + (i + 1));
                    $(this).attr("data-campo_descr", "#estoqueModal #produto-" + (i + 1));
                } else if ($(this).hasClass("produto")) $(this).attr("data-input", "#estoqueModal #id_produto-" + (i + 1));
                else if ($(this).hasClass("id-produto")) $(this).off("change").on("change", () => atualizaPreco(i + 1, "estoque"));
                else if ($(this).hasClass("es")) $(this).off("change").on("change", () => carrega_obs(i + 1, true));
            });
        });
        carrega_atalhos();
    });

    $("#estoqueModal .modal-tudo").append($(linha));

    carrega_autocomplete();
    carrega_dinheiro();
    $($(linha).find(".obs")[0]).trigger("keyup");
    $($(linha).find(".qtd")[0]).trigger("keyup");
    carrega_obs(cont, false);

    $(".form-control").keydown(function() {
        $(this).removeClass("invalido");
    });
}