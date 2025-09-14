function listar(coluna) {
    $.get(URL + "/maquinas/listar", {
        filtro : $("#busca").val()
    }, function(data) {
        let resultado = "";
        if (typeof data == "string") data = $.parseJSON(data);
        if (data.length) {
            esconderImagemErro();
            data.forEach((linha) => {
                resultado += "<tr>" +
                    "<td class = 'text-right' width = '12%'>" + linha.id.toString().padStart(4, "0") + "</td>";
                if (COMODATO) {
                    resultado += "<td width = '28%'>" + linha.descr + "</td>" +
                        "<td width = '35%'>" + linha.comodato + "</td>";
                } else resultado += "<td width = '69%'>" + linha.descr + "</td>";

                resultado += "<td class = 'text-center btn-table-action' width = '25%'>";
                if (!EMPRESA) {
                    if (linha.comodato != "---") {
                        resultado += "<i class = 'my-icon far fa-box'           title = 'Atribuir produto'    onclick = 'atribuicao = new Atribuicoes(false, " + linha.id + ")'></i>" +                            
                            "<i class = 'my-icon far fa-tshirt'                 title = 'Atribuir grade'      onclick = 'atribuicao = new Atribuicoes(true, " + linha.id + ")'></i>" +
                            "<i class = 'my-icon far fa-edit'                   title = 'Editar'              onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                            "<br />" +
                            "<i class = 'my-icon far fa-bars'                   title = 'Opções do contrato'  onclick = 'contrato(" + linha.id + ", " + (linha.tem_mov == "S" ? "true" : "false") + ", " + (linha.tem_cp == "S" ? "true" : "false") + ")'></i>" +
                            "<i class = 'my-icon far fa-tools'                  title = 'Configurar contrato' onclick = 'configurar_comodato(" + linha.id + ")'></i>" +
                            "<i class = 'my-icon fa-duotone fa-handshake-slash' title = 'Encerrar contrato'   onclick = 'encerrar(" + linha.id + ")'></i>";
                    } else {
                        resultado += "<i class = 'my-icon far fa-handshake' title = 'Locar máquina' onclick = 'comodatar(" + linha.id + ")'></i>" +
                            "<i class = 'my-icon far fa-edit'               title = 'Editar'        onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                            "<i class = 'my-icon far fa-trash-alt'          title = 'Excluir'       onclick = 'excluir(" + linha.id + ", " + '"/maquinas"' + ")'></i>";
                    }
                } else if (linha.tem_cod == "S") resultado += "<i class = 'my-icon far fa-cart-arrow-down' title = 'Solicitar compra' onclick = 'chamarRelatorioItens(" + linha.id + ")'></i>";
                resultado += "</td></tr>";
            });
            $("#table-dados").html(resultado);
            ordenar(coluna);
        } else mostrarImagemErro();
    });
}

function chamarRelatorioItens(id) {
    relatorio = new RelatorioItens("S", id);
}

function extrato_maquina(id_maquina) {
    let req = {};
    ["inicio", "fim", "id_produto"].forEach((chave) => {
        req[chave] = "";
    });
    req.lm = "S";
    req.id_maquina = id_maquina;
    let link = document.createElement("a");
    link.href = URL + "/relatorios/extrato?" + $.param(req);
    link.target = "_blank";
    link.click();
}

function validar() {
    limpar_invalido();
    let erro = "";
    if (!$("#descr").val()) erro = "Preencha o campo";

    if (!erro) {
        let alterou = false;
        if ($("#descr").val().toUpperCase().trim() != anteriores.descr.toUpperCase().trim()) alterou = true;
        if ($("#patrimonio").val().toUpperCase().trim() != anteriores.patrimonio.toUpperCase().trim()) alterou = true;
        if (!alterou) erro = "Altere pelo menos um campo para salvar";
    }
    
    $.get(URL + "/maquinas/consultar", {
        id : $("#id").val(),
        descr : $("#descr").val().toUpperCase().trim()
    }, function(data) {
        if (!erro && parseInt(data) && !parseInt($("#id").val())) erro = "Já existe um registro com essa descrição";
        if (erro) {
            if (!$("#descr").val()) $("#descr").addClass("invalido");
            s_alert(erro);
        } else $("#maquinasModal form").submit();
    });
}

function chamar_modal(id) {
    $("#maquinasModalLabel").html((id ? "Editando" : "Cadastrando") + " máquina");
    if (id) {
        $.get(URL + "/maquinas/mostrar/" + id, function(maq) {
            if (typeof maq == "string") maq = $.parseJSON(maq);
            $("#descr").val(maq.descr);
            $("#patrimonio").val(maq.patrimonio);
            modal("maquinasModal", id); 
        });
    } else modal("maquinasModal", id); 
}

function atualizaPreco(seq, nome) {
    const _id_produto = $("#" + nome + "Modal #id_produto-" + seq).val().trim();
    if (_id_produto) {
        $.get(URL + "/maquinas/preco", {
            id_maquina : $($(".id_maquina")[0]).val(),
            id_produto : _id_produto
        }, function(preco) {
            $($($($("#" + nome + "Modal #id_produto-" + seq).parent()).parent()).find(".preco")).val(preco).trigger("keyup");
        })
    }
}

function contrato(id_maquina, mostrarExtrato, mostrarProdutos) {
    $.get(URL + "/maquinas/mostrar/" + id_maquina, function(maq) {
        if (typeof maq == "string") maq = $.parseJSON(maq);
        $("#contratoModalLabel").html(maq.descr + " - opções do contrato");
        $("#btn-extrato").off("click").on("click", function() {
            extrato_maquina(id_maquina);
            $("#contratoModal").modal("hide");
        });
        $("#btn-cp").off("click").on("click", function() {
            cp(id_maquina);
            $("#contratoModal").modal("hide");
        });
        $("#btn-estoque").off("click").on("click", function() {
            estoque(id_maquina);
            $("#contratoModal").modal("hide");
        });
        if (mostrarExtrato) $($("#btn-extrato").parent()).removeClass("d-none");
        else $($("#btn-extrato").parent()).addClass("d-none");
        if (mostrarProdutos) $($("#btn-cp").parent()).removeClass("d-none");
        else $($("#btn-cp").parent()).addClass("d-none");
        $("#contratoModal").modal();
    });
}

function cp(id) {
    $.get(URL + "/maquinas/mostrar/" + id, function(maq) {
        if (typeof maq == "string") maq = $.parseJSON(maq);
        limpar_invalido();
        $("#cpModalLabel").html(maq.descr + " - produtos do contrato");
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
        cp_mp_listeners("cp");
        cp_mp_listar("cp", true);
   });
}