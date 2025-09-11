function zebrar() {
    setTimeout(function() {
        let obterVisiveis = function(details, resultado) {
            let summary = $(details).children("summary")[0];
            if (summary) resultado.push(summary.id);
            $(details).children("div, details > summary").each(function() {
                resultado.push(this.id);
            });
            $(details).children("details[open]").each(function() {
                obterVisiveis(this, resultado);
            });
            return resultado;
        }

        let ativar = false;
        let aux = new Array();
        $("#principal > div, #principal > details > summary").each(function() {
            aux.push($(this).attr("id"));
        });
        $("#principal > details").each(function() {
            if ($(this).attr("open")) {
                aux.concat(obterVisiveis($(this), aux));
                ativar = true;
            }
        });
        let lista = new Array();
        $("#principal .texto-tabela").each(function() {
            $(this).removeClass("impar");
            $(this).removeClass("par");
            if (aux.indexOf($(this).attr("id")) > -1) lista.push($(this).attr("id").replace("empresa-", ""));
        });
        for (let i = 0; i < lista.length; i++) $("#principal #empresa-" + lista[i]).addClass(((i % 2 > 0) ? "im" : "") + "par");
    }, 0);
}

function listar() {
    let linha = function(id, nome) {
        return "<summary class = 'texto-tabela' id = 'empresa-" + id + "' onclick = 'zebrar()'>" +
            nome +
            "<div class = 'btn-table-action'>" +
                "<i title = 'Nova filial' class = 'espacamento my-icon far fa-plus' onclick = 'criar_filial(" + id + ", event)'></i>" +
                "<i title = 'Editar'      class = 'my-icon far fa-edit'             onclick = 'chamar_modal(" + id + ", event)'></i>" +
                "<i title = 'Excluir'     class = 'my-icon far fa-trash-alt'        onclick = 'excluir(" + id + ", " + '"/empresas"' + ", event)'></i>" +
            "</div>" +
        "</summary>";
    }

    $.get(URL + "/empresas/listar", function(data) {
        if (typeof data == "string") data = $.parseJSON(data);
        let resultado = "";
        data.inicial.forEach((empresa) => {
            resultado += "<details>" + linha(empresa.id, empresa.nome_fantasia) + "</details>";
        });
        $("#principal").html(resultado);
        data.final.forEach((empresa) => {
            if (empresa.id_matriz != 0) {
                $($("#empresa-" + empresa.id_matriz).parent()).html(
                    $($("#empresa-" + empresa.id_matriz).parent()).html() +
                    "<details class = 'filho'>" + 
                        linha(empresa.id, empresa.nome_fantasia) +
                    "</details>"
                );
                if (!parseInt(data.matriz_editavel)) $("#empresa-" + empresa.id_matriz + " .btn-table-action").css("visibility", "hidden");
            }
        });
        $("summary.texto-tabela").each(function() {
            if (!$($(this).parent()).find("details").length) $($(this).parent()).replaceWith("<div class = 'sem-filhos texto-tabela' id = '" + $(this).attr("id") + "'>" + $(this).html() + "</div>");
        });
        zebrar();
        const elGrupo = document.getElementById("empresa-" + GRUPO);
        if (elGrupo !== null) {
            elGrupo.parentElement.open = true;
            setTimeout(function() {
                zebrar();
            }, 100);
        }
    });
}

function validar_cnpj(cnpj) {
    cnpj = cnpj.replace(/[^\d]+/g,'');
    if (cnpj == '' || cnpj.length != 14 || /^(\d)\1{13}$/.test(cnpj)) return false;
    let tamanho = cnpj.length - 2
    let numeros = cnpj.substring(0, tamanho);
    let digitos = cnpj.substring(tamanho);
    let soma = 0;
    let pos = tamanho - 7;
    for (let i = tamanho; i >= 1; i--) {
        soma += numeros.charAt(tamanho - i) * pos--;
        if (pos < 2) pos = 9;
    }
    let resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado != digitos.charAt(0)) return false;
    tamanho = tamanho + 1;
    numeros = cnpj.substring(0, tamanho);
    soma = 0;
    pos = tamanho - 7;
    for (let i = tamanho; i >= 1; i--) {
        soma += numeros.charAt(tamanho - i) * pos--;
        if (pos < 2) pos = 9;
    }
    resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
    if (resultado != digitos.charAt(1)) return false;
    return true;
}

function formatar_cnpj(el) {
    el.classList.remove("invalido");
    let rawValue = $(el).val().replace(/\D/g, "").replace(",", "");
    if (rawValue.length === 15 && rawValue.startsWith("0")) {
        let potentialCNPJ = rawValue.substring(1);
        if (validar_cnpj(potentialCNPJ)) rawValue = potentialCNPJ;
    }
    $(el).val(
        rawValue.replace(/^(\d{2})(\d)/, '$1.$2') // Adiciona ponto após o segundo dígito
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3') // Adiciona ponto após o quinto dígito
            .replace(/\.(\d{3})(\d)/, '.$1/$2') // Adiciona barra após o oitavo dígito
            .replace(/(\d{4})(\d)/, '$1-$2') // Adiciona traço após o décimo segundo dígito
            .replace(/(-\d{2})\d+?$/, '$1') // Impede a entrada de mais de 14 dígitos
    );
}

async function validar() {
    limpar_invalido();
    let erro = "";

    if (!$("#cnpj").val()) {
        erro = "Preencha o campo";
        $("#cnpj").addClass("invalido");
    }
    const aux = verifica_vazios(["nome_fantasia", "razao_social"], erro);
    erro = aux.erro;
    let alterou = aux.alterou;
    if (!erro && !validar_cnpj($("#cnpj").val())) {
        erro = "CNPJ inválido";
        $("#cnpj").addClass("invalido");
    }
    if ($("#cnpj").val() != anteriores.cnpj) alterou = true;

    const data = await $.get(URL + "/empresas/consultar/", {
        id : $("#id").val(),
        cnpj : $("#cnpj").val().replace(/\D/g, "").replace(",", "")
    });
    if (!erro && data == "R" && !parseInt($("#id").val())) {
        erro = "Já existe um registro com esse CNPJ";
        $("#cnpj").addClass("invalido");
    }
    if (!erro && !alterou) erro = "Altere pelo menos um campo para salvar";
    if (!erro) {
        $("#cnpj").val($("#cnpj").val().replace(/\D/g, "").replace(",", ""));
        $("#empresasModal form").submit();
    } else s_alert(erro);
}

function chamar_modal(id, e) {
    if (e !== undefined) e.preventDefault();
    $("#empresasModalLabel").html((id ? "Editando" : "Cadastrando") + " empresa");
    if (id) {
        $.get(URL + "/empresas/mostrar/" + id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            $("#id_matriz, #cnpj, #razao_social, #nome_fantasia").each(function() {
                $(this).val(data[$(this).attr("id")]);
            });
            if (parseInt(data.id_matriz)) $("#empresasModalLabel").html("Editando filial");
            modal("empresasModal", id);
        });
    } else modal("empresasModal", id);
}

function criar_filial(matriz, e) {
    e.preventDefault();
    $("#empresasModalLabel").html("Criando filial");
    modal("empresasModal", 0, function() {
        $("#id_matriz").val(matriz);
    });
}