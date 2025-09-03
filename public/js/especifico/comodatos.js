function comodatar(id) {
    $.get(URL + "/valores/maquinas/mostrar/" + id, function(descr) {
        $("#comodatosModalLabel").html("Locando " + descr);
        $(".id_maquina").each(function() {
            $(this).val(id);
        });
        $("#comodato-inicio").val(hoje());
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

function validar_comodato() {
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
}