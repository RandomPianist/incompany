function mp(id) {
    $.get(URL + "/produtos/mostrar/" + id, function(prod) {
        if (typeof prod == "string") prod = $.parseJSON(prod);
        limpar_invalido();
        $("#mpModalLabel").html(prod.descr + " - contratos do produto");
        $("#id_produto").val(id);
        cp_mp_listeners("mp");
        listar_mp(function() {
            $("#mpModal").modal();
            $("#mpModal #id-maquina").each(function() {
                $(this).trigger("change");
            });
            $("#mpModal .minimo, #mpModal .maximo").each(function() {
                limitar($(this), true);
            });
        });
   });
}

function listar_mp(callback) {
    cp_mp_limpar("mp");
    $.get(URL + "/produtos/maquina/listar", {
        id_produto : $("#id_produto").val(),
        filtro : $("#busca-maq").val()
    }, function(data) {
        cp_mp_limpar("mp");
        if (typeof data == "string") data = $.parseJSON(data);
        const total = data.total;
        let titulo = $("#mpModalLabel").html();
        if (titulo.indexOf("|") > -1) titulo = titulo.split("|")[0].trim();
        titulo += " | Listando " + data.lista.length + " de " + total;
        $("#mpModalLabel").html(titulo);
        data = data.lista;
        for (let i = 0; i < data.length; i++) {
            if (i > 0) adicionar_campo_mp();
            $("#mpModal #maquina-" + (i + 1)).val(data[i].maquina);
            $("#mpModal #id_maquina-" + (i + 1)).val(data[i].id_maquina).trigger("change");
            $("#mpModal #lixeira-" + (i + 1)).val("opt-" + data[i].lixeira);
            $("#mpModal #preco-" + (i + 1)).val(data[i].preco).trigger("keyup");
            $("#mpModal #minimo-" + (i + 1)).val(parseInt(data[i].minimo)).trigger("keyup");
            $("#mpModal #maximo-" + (i + 1)).val(parseInt(data[i].maximo)).trigger("keyup");
        }
        if (callback !== undefined) callback();
    });
}

// parei aqui

async function mp_validar_main() {
    limpar_invalido();
    let erro = "";
    let data = await $.get(URL + "/produtos/maquina/consultar", {
        maquinas_descr : obter_vetor("maquina", "mp"),
        maquinas_id : obter_vetor("id-maquina", "mp"),
        maximos : obter_vetor("maximo", "mp"),
        precos : obter_vetor("preco", "mp"),
        id_produto : $("#id_produto").val()
    });
    if (typeof data == "string") data = $.parseJSON(data);
    if (!erro && data.texto) {
        for (let i = 0; i < data.campos.length; i++) {
            let el = $("#mpModal #" + data.campos[i]);
            $(el).val(data.valores[i]);
            $(el).trigger("keyup");
            $(el).addClass("invalido");
        }
        erro = data.texto;
    }
    if (erro) return erro;
    $("#mpModal .preco").each(function() {
        $(this).val(parseInt(apenasNumeros($(this).val())) / 100);
    });
    $("#mpModal form").submit();
    return "";
}

async function validar_cp() {
    let erro = await validar_cp_main();
    if (erro) s_alert(erro);
}

function adicionar_campo_cp() {
    const cont = ($("#cpModal input[type=number]").length / 2) + 1;

    let linha = $($("#cpModal #template-linha").html());

    $($(linha).find(".produto")[0]).attr("id", "produto-" + cont).attr("data-input", "#id_produto-" + cont);
    $($(linha).find(".id-produto")[0]).attr("id", "id_produto-" + cont);
    $($(linha).find(".lixeira")[0]).attr("id", "lixeira-" + cont).html($("#lixeira-1").html());
    $($(linha).find(".preco")[0]).attr("id", "preco-" + cont);
    $($(linha).find(".minimo")[0]).attr("id", "minimo-" + cont);
    $($(linha).find(".maximo")[0]).attr("id", "maximo-" + cont);

    $($(linha).find(".remove-linha")[0]).on("click", function() {
        $(linha).remove();
        ["produto", "id_produto", "lixeira", "minimo", "maximo", "preco"].forEach((classe) => {
            $("#cpModal ." + classe).each(function(i) {
                $(this).attr("id", classe + "-" + (i + 1));
            });
        });
    });

    $("#cpModal .modal-tudo").append($(linha));

    cp_mp_listeners("mp");
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
            cp_mp_limpar("mp");
            $("#cpModal #busca-prod").val("");
            s_alert({
                icon : "error",
                title : "Não foi possível salvar"
            });
        }
    } else if (resp.isDenied) {
        cp_mp_limpar("mp");
        $("#cpModal #busca-prod").val("");
    } else $("#cpModal").modal();
}