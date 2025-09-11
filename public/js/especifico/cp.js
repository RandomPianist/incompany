function cp(id) {
    $.get(URL + "/maquinas/mostrar/" + id, function(descr) {
        limpar_invalido();
        $("#cpModalLabel").html(descr + " - produtos do contrato");
        $(".id_maquina").each(function() {
            $(this).val(id);
        });
        $("#cpModal .form-control-lg").each(function() {
            $(this).off("change").on("change", function() {
                const prod = $("#busca-prod").val();
                const refer = $("#busca-refer").val();
                if (prod.trim()) $("#busca-refer").val("");
                if (prod.trim() || refer.trim()) $("#busca-cat").val("");
            });
        });
        cp_listeners();
        listar_cp(function() {
            $("#cpModal").modal();
            $("#cpModal .id-produto").each(function() {
                $(this).trigger("change");
            });
            $("#cpModal .minimo, #cpModal .maximo").each(function() {
                limitar($(this), true);
            });
        });
   });
}

function cp_listeners() {
    $("#cpModal .id-produto, #cpModal .minimo, #cpModal .maximo, #cpModal .preco").each(function() {
        $(this).off("change").on("change", function() {
            const linha = $($($(this).parent()).parent())[0];
            if ($(this).val().trim()) {
                $.get(URL + "/maquinas/produto/verificar-novo", {
                    preco : parseInt($($(linha).find(".preco")[0]).val().replace(/\D/g, "")) / 100,
                    minimo : $($(linha).find(".minimo")[0]).val(),
                    maximo : $($(linha).find(".maximo")[0]).val(),
                    id_produto : $($(linha).find(".id-produto")[0]).val(),
                    id_maquina : $($(".id_maquina")[0]).val()
                }, function(novo) {
                    const el = $($(linha).find(".form-search")[0]);
                    if (parseInt(novo)) $(el).addClass("new").removeClass("old");
                    else $(el).addClass("old").removeClass("new");
                });
            } else $($(linha).find(".form-search")[0]).addClass("new").removeClass("old");
            if ($(this).hasClass("id-produto")) atualizaPreco($(this).attr("id").replace(/\D/g, ""), "cp");
            if ($(this).hasClass("maximo") || $(this).hasClass("minimo")) limitar($(this), true);
        });
    }).off("keyup").on("keyup", function() {
        if ($(this).hasClass("maximo") || $(this).hasClass("minimo")) limitar($(this), true);
    });
}

function limpar_cp() {
    $("#cpModal .remove-produto").each(function() {
        $(this).trigger("click");
    });
    $("#cpModal #produto-1").val("");
    $("#cpModal #id_produto-1").val("");
    $("#cpModal #lixeira-1").val("opt-0");
    $("#cpModal #preco-1").val(0).trigger("keyup");
    $("#cpModal #minimo-1").val(0).trigger("keyup");
    $("#cpModal #maximo-1").val(0).trigger("keyup");
}

function listar_cp(callback) {
    limpar_cp();
    $.get(URL + "/maquinas/listar", {
        id_maquina : $($(".id_maquina")[0]).val(),
        filtro : $("#busca-prod").val(),
        filtro_ref : $("#busca-ref").val(),
        filtro_cat : $("#busca-cat").val()
    }, function(data) {
        limpar_cp();
        if (typeof data == "string") data = $.parseJSON(data);
        const total = data.total;
        let titulo = $("#cpModalLabel").html();
        if (titulo.indexOf("|") > -1) titulo = titulo.split("|")[0].trim();
        titulo += " | Listando " + data.lista.length + " de " + total;
        $("#cpModalLabel").html(titulo);
        data = data.lista;
        for (let i = 0; i < data.length; i++) {
            if (i > 0) adicionar_campo_cp();
            $("#cpModal #produto-" + (i + 1)).val(data[i].produto);
            $("#cpModal #id_produto-" + (i + 1)).val(data[i].id_produto).trigger("change");
            $("#cpModal #lixeira-" + (i + 1)).val("opt-" + data[i].lixeira);
            $("#cpModal #preco-" + (i + 1)).val(data[i].preco).trigger("keyup");
            $("#cpModal #minimo-" + (i + 1)).val(parseInt(data[i].minimo)).trigger("keyup");
            $("#cpModal #maximo-" + (i + 1)).val(parseInt(data[i].maximo)).trigger("keyup");
        }
        if (callback !== undefined) callback();
    });
}

async function validar_cp_main() {
    limpar_invalido();
    let erro = "";
    let data = await $.get(URL + "/maquinas/produto/consultar", {
        produtos_descr : obter_vetor("produto", "cp"),
        produtos_id : obter_vetor("id-produto", "cp"),
        maximos : obter_vetor("maximo", "cp"),
        precos : obter_vetor("preco", "cp"),
        id_maquina : $($(".id_maquina")[0]).val()
    });
    if (typeof data == "string") data = $.parseJSON(data);
    if (!erro && data.texto) {
        for (let i = 0; i < data.campos.length; i++) {
            let el = $("#cpModal #" + data.campos[i]);
            $(el).val(data.valores[i]);
            $(el).trigger("keyup");
            $(el).addClass("invalido");
        }
        erro = data.texto;
    }
    if (erro) return erro;
    $("#cpModal .preco").each(function() {
        $(this).val(parseInt($(this).val().replace(/\D/g, "")) / 100);
    });
    $("#cpModal form").submit();
    return "";
}

async function validar_cp() {
    let erro = await validar_cp_main();
    if (erro) s_alert(erro);
}

function adicionar_campo_cp() {
    const cont = ($("#cpModal input[type=number]").length / 2) + 1;

    let linha = $($("#cpModal #template-linha").html());

    $($(linha).find(".produto")[0]).attr("id", "produto-" + cont).data("input", "#id_produto-" + cont);
    $($(linha).find(".id-produto")[0]).attr("id", "id_produto-" + cont);
    $($(linha).find(".lixeira")[0]).attr("id", "lixeira-" + cont).html($("#lixeira-1").html());
    $($(linha).find(".preco")[0]).attr("id", "preco-" + cont);
    $($(linha).find(".minimo")[0]).attr("id", "minimo-" + cont);
    $($(linha).find(".maximo")[0]).attr("id", "maximo-" + cont);

    $($(linha).find(".remove-produto")[0]).on("click", function() {
        $(linha).remove();
        ["produto", "id_produto", "lixeira", "minimo", "maximo", "preco"].forEach((classe) => {
            $("#cpModal ." + classe).each(function(i) {
                $(this).attr("id", classe + "-" + (i + 1));
            });
        });
    });

    $("#cpModal .modal-tudo").append($(linha));

    cp_listeners();
    carrega_autocomplete();
    carrega_dinheiro();

    $(".form-control").keydown(function() {
        $(this).removeClass("invalido");
    });

    $($(linha).find(".id-produto")[0]).trigger("change");
    $($(linha).find(".minimo")[0]).trigger("change");
    $($(linha).find(".maximo")[0]).trigger("change");
}

async function cp_pergunta_salvar() {
    const resp = await s_alert({
        html : "Deseja salvar os produtos novos?",
        ync : true
    });
    if (resp.isConfirmed) {
        let erro = await validar_cp();
        if (erro) {
            limpar_cp();
            $("#cpModal #busca-prod").val("");
            s_alert({
                icon : "error",
                title : "Não foi possível salvar"
            });
        }
    } else if (resp.isDenied) {
        limpar_cp();
        $("#cpModal #busca-prod").val("");""
    } else $("#cpModal").modal();
}