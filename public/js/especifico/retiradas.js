function atualizaQtd() {
    $("#quantidade2_label").html($("#quantidade2").val());
}

function retirar(id) {
    $("#quantidade2").val(1);
    atualizaQtd();
    $.get(URL + "/atribuicoes/produtos/" + id, function(data) {
        let pai = $($($("#variacao").parent()).parent());
        let resultado = "";
        if (typeof data == "string") data = $.parseJSON(data);
        data.forEach((variacao) => {
            resultado += "<option value = 'prod-" + variacao.id + "'>" + variacao.descr + "</option>";
        });
        $("#variacao").html(resultado);
        $(pai).removeClass("d-none");
        if (data.length < 2) $(pai).addClass("d-none");
        pai = $($($($("#quantidade2").parent()).parent()).parent());
        $(pai).addClass("d-none")
        if (parseInt($("#quantidade2").attr("max")) > 1) $(pai).removeClass("d-none");
        $("#btn-retirada").click(function() {
            let erro = "";
            
            if (!$("#data-ret").val()) erro = "Preencha o campo";
            else if (eFuturo($("#data-ret").val())) erro = "A retirada não pode ser no futuro";
            
            if (!erro) {
                $.get(URL + "/retiradas/consultar", {
                    atribuicao : id,
                    qtd : $("#quantidade2").val(),
                    pessoa : pessoa_atribuindo
                }, function(ok) {
                    if (!parseInt(ok)) modal2("supervisorModal", ["cpf2", "senha2"]);
                    else retirarMain(id);
                });
            } else {
                $("#data-ret").addClass("invalido");
                s_alert(erro);
            }
        });
        let titulo = "Retirada retroativa - " + data[0].titulo;
        if (titulo.length > 46) titulo = titulo.substring(0, 46).trim() + "...";
        $("#retiradasModalLabel").html(titulo);
        $("#quantidade2").val(1);
        atualizaQtd();
        $("#data-ret").val("");
        $("#retiradasModal").modal();
    });
}

function validar() {
    limpar_invalido();
    let erro = "";

    if (!$("#cpf2").val()) {
        erro = "Preencha o campo";
        $("#cpf2").addClass("invalido");
    }

    if (!$("#senha2").val()) {
        if (!erro) erro = "Preencha o campo";
        else erro = "Preencha os campos";
        $("#senha2").addClass("invalido");
    }

    if (!erro && !validar_cpf($("#cpf2").val())) {
        erro = "CPF inválido";
        $("#cpf2").addClass("invalido");
    }

    if (!erro) {
        $.post(URL + "/colaboradores/supervisor", {
            _token : $("meta[name='csrf-token']").attr("content"),
            cpf : $("#cpf2").val().replace(/\D/g, ""),
            senha : $("#senha2").val()
        }, function(ok) {
            if (parseInt(ok)) retirarMain(id, ok);
            else s_alert("Supervisor inválido");
        });
    } else s_alert(erro);
}

async function retirarMain(id, _supervisor) {
    if (_supervisor === undefined) _supervisor = 0;
    await $.post(URL + "/retiradas/salvar", {
        _token : $("meta[name='csrf-token']").attr("content"),
        supervisor : _supervisor,
        atribuicao : id,
        pessoa : pessoa_atribuindo,
        produto : $("#variacao").val().replace("prod-", ""),
        data : $("#data-ret").val(),
        quantidade : $("#quantidade2").val()
    });
    $("#supervisorModal").modal("hide");
    $("#retiradasModal").modal("hide");
    await s_alert({icon : "success"});
    listar();
}