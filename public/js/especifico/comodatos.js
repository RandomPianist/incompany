function comodatoListeners() {
    $("#comodatosModal input[type=number]").each(function() {
        $(this).off("change").on("change", function() {
            limitar($(this), true);
        }).off("keyup").on("keyup", function() {
            $(this).trigger("change");
        }).trigger("change");
    });
}

function mostrarComAtb() {
    const ativo = $("#atb_todos-chk").prop("checked");
    $("#atb_todos").val(ativo ? "1" : "0");
    $(".com-atb-row").each(function() {
        if (ativo) $(this).removeClass("d-none");
        else $(this).addClass("d-none");
    });
}

function comodatar(id) {
    $.get(URL + "/maquinas/mostrar/" + id, function(descr) {
        $("#comodatosModalLabel").html("Locando " + descr);
        $(".id_maquina").each(function() {
            $(this).val(id);
        });
        $("#comodato-inicio").val(hoje()).attr("disabled", false);
        $("#comodato-fim").attr("disabled", false);
        $("#comodato-empresa").attr("disabled", false);

        $("#atb_todos").val(0);
        $("#atb_todos-chk").prop("checked", false);
        $("#travar_ret").val(1);
        $("#travar_ret-chk").prop("checked", true);
        $("#travar_estq").val(1);
        $("#travar_estq-chk").prop("checked", true);
        
        $("#com-quantidade").val(1);
        $("#com-validade").val(1);
        $("#com-obrigatorio").val("opt-1");
        $("#comodatosModal form").attr("action", URL + "/maquinas/comodato/criar");
        $("#comodatosModal form button").off("click").on("click", function() {
            limpar_invalido();
            let erro = verifica_vazios(["comodato-empresa", "comodato-inicio", "comodato-fim"]).erro;
            if (!erro) erro = validar_datas($("#comodato-inicio"), $("#comodato-fim"), true);
            $.get(URL + "/maquinas/comodato/consultar/", {
                inicio : $("#comodato-inicio").val(),
                fim : $("#comodato-fim").val(),
                empresa : $("#comodato-empresa").val(),
                id_empresa : $("#comodato-id_empresa").val(),
                id_maquina : $($(".id_maquina")[0]).val()
            }, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                if (!erro && data.texto) {
                    if (data.invalida_inicio !== undefined) {
                        if (data.invalida_inicio == "S") $("#comodato-inicio").addClass("invalido");
                        if (data.invalida_fim == "S") $("#comodato-fim").addClass("invalido");
                    } else $("#comodato-empresa").addClass("invalido");
                    erro = data.texto;
                }
                if (!erro) $("#comodatosModal form").submit();
                else s_alert(erro);
            });
        });
        mostrarComAtb();
        comodatoListeners();
        modal2("comodatosModal", ["comodato-fim", "comodato-empresa", "comodato-id_empresa"]);
    });
}

async function encerrar(_id_maquina) {
    const resp = await s_alert({
        title : "Aviso",
        html : "Tem certeza que deseja encerrar essa locação?",
        invert : true
    });
    if (resp) {
        await $.post(URL + "/maquinas/comodato/encerrar", {
            _token : $("meta[name='csrf-token']").attr("content"),
            id_maquina : _id_maquina
        });
        location.reload();
    }
}

function configurar_comodato(id_maquina) {
    $.get(URL + "/maquinas/comodato/mostrar/" + id_maquina, function(data) {
        if (typeof data == "string") data = $.parseJSON(data);
        $(".id_maquina").each(function() {
            $(this).val(id_maquina);
        });
        focar = false;
        $("#comodatosModalLabel").html("Configurando " + data.maquina);
        $("#comodato-inicio").val(data.inicio).attr("disabled", true);
        $("#comodato-fim").val(data.fim).attr("disabled", true);
        
        ["travar_ret", "travar_estq", "atb_todos"].forEach((id) => {
            $("#" + id).val(data[id]);
            $("#" + id + "-chk").prop("checked", parseInt(data[id]) ? true : false);
            anteriores[id] = parseInt(data[id]);
        });
        anteriores.quantidade = parseInt(data.qtd);
        anteriores.validade = parseInt(data.validade);
        anteriores.obrigatorio = "opt-" + data.obrigatorio;
        
        $("#com-quantidade").val(data.qtd).trigger("change");
        $("#com-validade").val(data.validade).trigger("change");
        $("#com-obrigatorio").val(anteriores.obrigatorio);
        $("#comodato-empresa").val(data.empresa).attr("disabled", true);
        $("#comodatosModal form").attr("action", URL + "/maquinas/comodato/editar");
        $("#comodatosModal form button").off("click").on("click", function() {
            let erro = true;
            if (anteriores.travar_ret != parseInt($("#travar_ret").val())) erro = false;
            if (anteriores.travar_estq != parseInt($("#travar_estq").val())) erro = false;
            if (anteriores.atb_todos != parseInt($("#atb_todos").val())) erro = false;
            if (anteriores.quantidade != parseInt($("#com-quantidade").val())) erro = false;
            if (anteriores.validade != parseInt($("#com-validade").val())) erro = false;
            if (anteriores.obrigatorio != $("#com-obrigatorio").val()) erro = false;
            if (!erro) $("#comodatosModal form").submit();
            else s_alert("Altere pelo menos um campo para salvar");
        });
        mostrarComAtb();
        comodatoListeners();
        $("#comodatosModal").modal();
    });
}