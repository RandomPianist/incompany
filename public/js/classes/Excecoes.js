class Excecoes {
    #hab = true;
    #idexc = 0;
    #id_atribuicao;

    constructor(_id_atribuicao) {
        this.#id_atribuicao = _id_atribuicao;
        modal("excecoesModal", 0, () => {
            setTimeout(() => {
                $("#exc-ps-chave").val("P").trigger("change");
                if (atribuicao.obter_psm() == "S") {
                    $($("#exp-ps-chave").parent()).addClass("d-none");
                    $($("#exp-ps-valor").parent()).removeClass("col-7").addClass("col-11");
                } else {
                    $($("#exp-ps-chave").parent()).removeClass("d-none");
                    $($("#exp-ps-valor").parent()).removeClass("col-11").addClass("col-7");
                }
                this.#mostrar();
            }, 0);
        });
    }

    salvar() {
        if (this.#hab) {
            this.#hab = false;
            $.post(URL + "/atribuicoes/excecoes/salvar", {
                _token: $("meta[name='csrf-token']").attr("content"),
                ps_chave: $("#exc-ps-chave").val(),
                ps_valor: $("#exc-ps-valor").val(),
                ps_id: $("#exc-ps-id").val(),
                id_atribuicao: this.#id_atribuicao
            }, (ret) => {
                ret = parseInt(ret);
                if (ret != 201) this.#hab = true;
                switch (ret) {
                    case 201:
                        $("#exc-ps-chave").val("P");
                        $("#exc-ps-valor").val("");
                        $("#exc-ps-id").val("");
                        this.#mostrar();
                        break;
                    case 403:
                        s_alert(($("#exc-ps-chave").val() == "S" ? "Centro de custo" : "Funcionário") + " inválido");
                        break;
                    case 404:
                        s_alert(($("#exc-ps-chave").val() == "S" ? "Centro de custo" : "Funcionário") + " não encontrado");
                        break;
                }
            });
        }
    }

    editar(id) {
        if (this.#idexc !== id) {
            $.get(URL + "/atribuicoes/excecoes/mostrar/" + id, (data) => {
                $("#estiloAux").html(".autocomplete-result{display:none}");
                $("#exc-ps-chave, #exc-ps-valor").each(function() {
                    $(this).attr("disabled", true);
                });
                if (typeof data == "string") data = $.parseJSON(data);
                setTimeout(() => {
                    $($(".autocomplete-line").first()).trigger("click");
                }, 500);
                setTimeout(() => {
                    $("#exc-ps-chave").val(data.ps_chave);
                    $("#exc-ps-id").val(data.ps_id);
                    $("#exc-ps-valor").val(data.ps_valor);
                    $("#exc-ps-chave, #exc-ps-valor").each(function() {
                        $(this).attr("disabled", false);
                    });
                    this.#mostrar(id);
                }, 1000);
            });
        }
    }

    excluir(id) {
        if (this.#hab) {
            this.#hab = false;
            excluirMain(id, "/atribuicoes/excecoes", "Tem certeza que deseja excluir essa exceção?", () => {
                this.#mostrar();
            });
        }
    }

    mudarTipo(chave) {
        $("#lbl-exc-ps-valor").html((chave == "P" ? "Nome" : "Descrição") + ": *");
        $("#exc-ps-valor").attr("data-table", chave == "P" ? "pessoas" : "setores");
        $("#exc-ps-valor").attr("data-column", chave == "P" ? "nome" : "descr");
        $("#exc-ps-valor").attr("data-filter", atribuicao.psm_val);
        $("#exc-ps-valor").attr("data-filter_col", atribuicao.obter_psm() == "M" ? "v_maquina" : "id_setor");
        $("#exc-atalho").attr("data-atalho", chave == "P" ? "pessoas" : "setores");
        carrega_atalhos();
    }

    #mostrar = (_id = 0) => {
        this.#idexc = _id;
        $.get(URL + "/atribuicoes/excecoes/listar/" + this.#id_atribuicao, (data) => {
            let resultado = "";
            if (typeof data == "string") data = $.parseJSON(data);
            let ha_pessoa = false;
            let ha_setor = false;
            if (data.length) {
                resultado += "<thead>" +
                    "<tr>" +
                        "<th class = 'exc-tipo'>Tipo</th>" +
                        "<th id = 'exc-titulo'></th>" +
                        "<th>&nbsp;</th>" +
                    "</tr>" +
                "</thead>" +
                "<tbody>";
                data.forEach((excecao) => {
                    if (excecao.ps_chave == "P") ha_pessoa = true;
                    if (excecao.ps_chave == "S") ha_setor = true;
                    resultado += "<tr>" +
                        "<td class = 'exc-tipo'>" + (excecao.ps_chave == "P" ? "FUNCIONÁRIO" : "CENTRO DE CUSTO") + "</td>" +
                        "<td>" +
                            "<span class = 'linha-atb " + (excecao.rascunho == "S" ? "old" : "new") + "'>" + excecao.pr_valor + "</span>" +
                        "</td>" +
                        "<td class = 'text-center manter-junto'>" + (
                            parseInt(excecao.pode_editar) ?
                                "<i class = 'my-icon far fa-edit' title = 'Editar' onclick = 'excecao.editar(" + excecao.id + ")'></i>" +
                                "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excecao.excluir(" + excecao.id + ")'></i>"
                            :
                                "---"
                        ) + "</td>" +
                    "</tr>";
                });
                resultado += "</tbody>";
                $($("#table-excecoes").parent()).addClass("pb-4");
                this.#hab = true;
            } else $($("#table-excecoes").parent()).removeClass("pb-4");
            $("#table-excecoes").html(resultado);
            if (data.length) {
                let titulo = "";
                if (ha_pessoa) titulo += "Nome";
                if (ha_setor) {
                    if (titulo) titulo += "/";
                    titulo += "Descrição";
                }
                $("#exc-titulo").html(titulo);
                $(".exc-tipo").each(function() {
                    if (titulo.indexOf("/") > -1) $(this).removeClass("d-none");
                    else $(this).addClass("d-none");
                });
            }
        });
    }
}