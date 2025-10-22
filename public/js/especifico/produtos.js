let ant_consumo = false;

function listar(coluna) {
    $.get(URL + "/produtos/listar", {
        filtro : $("#busca").val()
    }, function(data) {
        let resultado = "";
        if (typeof data == "string") data = $.parseJSON(data);
        if (data.length) {
            esconderImagemErro();
            data.forEach((linha) => {
                resultado += "<tr>" +
                    "<td width = '5%' class = 'text-center'>" +
                        "<img class = 'user-photo-sm' src = '" + linha.foto + "'" + ' onerror = "this.onerror=null;' + "this.classList.add('d-none');$(this).next().removeClass('d-none')" + '" />' +
                        "<i class = 'fa-light fa-image d-none' style = 'font-size:20px'></i>" +
                    "</td>" +
                    "<td width = '20%' class = 'text-center'>" + linha.cod_externo + "</td>" +
                    "<td width = '32.5%'>" + linha.descr + "</td>" +
                    "<td width = '32.5%'>" + linha.categoria + "</td>" +
                    "<td class = 'text-center btn-table-action' width = '10%'>" +
                        "<i class = 'my-icon far fa-edit'      title = 'Editar'               onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-handshake' title = 'Adicionar a contrato' onclick = 'mp(" + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-trash-alt' title = 'Excluir'              onclick = 'excluir(" + linha.id + ", " + '"/produtos"' + ")'></i>"
                    "</td>" +
                "</tr>";
            });
            $("#table-dados").html(resultado);
            ordenar(coluna);
        } else mostrarImagemErro();
    });
}

async function validar() {
    limpar_invalido();
    const aux = verifica_vazios(["cod_externo", "descr", "validade", "categoria"]);
    let erro = aux.erro;
    let alterou = aux.alterou;
    if (!erro && parseInt(apenasNumeros($("#preco").val())) <= 0) {
        erro = "Valor inválido";
        $("#preco").addClass("invalido");
    }
    if ($("#preco").val().trim() != dinheiro(anteriores.preco.toString()) || $("#consumo-chk").prop("checked") != ant_consumo) alterou = true;
    ["ca", "validade_ca", "tamanho", "referencia", "detalhes"].forEach((id) => {
        if ($("#" + id).val().toString().toUpperCase().trim() != anteriores[id].toString().toUpperCase().trim()) alterou = true;
    });
    let data = await $.get(URL + "/produtos/consultar/", {
        id : $("#id").val(),
        cod_externo : $("#cod_externo").val(),
        categoria : $("#categoria").val(),
        id_categoria : $("#id_categoria").val(),
        referencia : $("#referencia").val(),
        preco : parseInt(apenasNumeros($("#preco").val()) / 100)
    });
    if (!erro && data == "invalido") {
        erro = "Categoria não encontrada";
        $("#categoria").addClass("invalido");
    }
    if (!erro && data == "duplicado") {
        erro = "Já existe um registro com esse código";
        $("#cod_externo").addClass("invalido");
    }
    if (!erro && data.indexOf("preco") > -1) {
        erro = "O preço está inferior ao preço mínimo de " + dinheiro(data.replace("preco", ""));
        $("#preco").addClass("invalido");
    }
    if (!erro && !alterou && !document.querySelector("#produtosModal input[type=file]").value) erro = "Altere pelo menos um campo para salvar";
    if (!erro) {
        let resp = true;
        if (data == "aviso") {
            resp = await s_alert({
                invert : true,
                html : "Prosseguir apagará atribuições.<br>Deseja continuar?",
                icon : "warning"
            });
        }
        if (resp) {
            $("#preco").val(parseInt(apenasNumeros($("#preco").val())) / 100);
            $("#produtosModal form").submit();
        }
    } else s_alert(erro);
}

function chamar_modal(id) {
    $("#produtosModalLabel").html((id ? "Editando" : "Cadastrando") + " produto");
    if (id) {
        $.get(URL + "/produtos/mostrar/" + id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            ["cod_externo", "descr", "preco", "ca", "validade", "categoria", "id_categoria", "referencia", "tamanho", "detalhes", "validade_ca_fmt"].forEach((_id) => {
                $("#" + _id.replace("_fmt", "")).val(data[_id]);
            });
            $("#cod_externo").attr("disabled", true);
            $("#consumo-chk").prop("checked", parseInt(data.e_consumo) == 1);
            ant_consumo = parseInt(data.e_consumo) == 1;
            modal("produtosModal", id, function() {
                $("#produtosModal img").attr("src", data.foto);
                $($("#produtosModal img").parent()).removeClass("d-none");
                if (!data.foto) $($("#produtosModal img").parent()).addClass("d-none");
            });
        });
    } else {
        modal("produtosModal", id, function() {
            $($("#produtosModal img").parent()).addClass("d-none");
            $("#cod_externo").attr("disabled", false);
            $("#validade_ca").val(hoje());
            $("#consumo-chk").prop("checked", false);
            ant_consumo = false;
        });
    }
}

function atualiza_cod_externo(el) {
    contar_char(el, 8);
    $("#cod_externo_real").val($("#cod_externo").val());
}

function atualizaPreco(seq) {
    const _id_maquina = $("#mpModal #id_maquina-" + seq).val().trim();
    if (_id_maquina) {
        $.get(URL + "/maquinas/preco", {
            id_maquina : _id_maquina,
            id_produto : $("#id_produto").val()
        }, function(preco) {
            $($($($("#mpModal #id_maquina-" + seq).parent()).parent()).find(".preco")).val(preco).trigger("keyup");
        })
    }
}

function mp(id) {
    $.get(URL + "/produtos/mostrar2/" + id, function(prod) {
        if (typeof prod == "string") prod = $.parseJSON(prod);
        limpar_invalido();
        $("#mpModalLabel").html(prod.descr + " - contratos do produto");
        $("#id_produto").val(id);
        cpmp = new CPMP("mp");
   });
}