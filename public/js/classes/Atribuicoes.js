class Atribuicoes {
    #idatb = 0;
    #hab = true;
    #grade; 
    #psm_valor;

    constructor(grade, _psm_valor) {
        this.#grade = grade;
        this.#psm_valor = _psm_valor;

        $('a[data-toggle="tab"]').on('shown.bs.tab', (e) => {
            const abaAtivaId = $(e.target).attr("id");
            
            this.#grade = (abaAtivaId == 'grade-tab');
            
            this.#mostrar();
        });

        $.get(URL + "/atribuicoes/permissao", {
            id: this.#psm_valor,
            tipo: this.#grade ? "R" : "P",
            tipo2: this.obter_psm()
        }, (data) => {
            if (typeof data == "string") data = $.parseJSON(data);
            if (parseInt(data.code) == 200) {
                modal("atribuicoesModal", 0, () => {
                    const _psm_chave = this.obter_psm();
                    let url = URL + "/";
                    switch (_psm_chave) {
                        case "P":
                            url += "colaboradores";
                            break;
                        case "S":
                            url += "setores";
                            break;
                        case "M":
                            url += "maquinas";
                            break;
                    }
                    url += "/mostrar";
                    if (_psm_chave != "M") url += "2";
                    
                    $.get(url + "/" + this.#psm_valor, (resp) => {
                        if (typeof resp == "string") resp = $.parseJSON(resp);
                        
                        $("#atribuicoesModalLabel").html(resp[_psm_chave == "P" ? "nome" : "descr"].toUpperCase() + " - Atribuindo");
                        
                        $("#produto, #referencia").val("");
                        $("#id_produto_p, #id_produto_r").val("");

                        $("#quantidade_p, #quantidade_r").val(1);
                        $("#validade_p, #validade_r").val(1);

                        if (this.#grade) {
                            $('#atribuicoesTab a[href="#grade-pane"]').tab('show');
                            $("#referencia").attr("data-filter", _psm_chave + "|" + this.#psm_valor);
                        } else $('#atribuicoesTab a[href="#produto-pane"]').tab('show');
                        
                        this.#mostrar();
                    });
                });
            } else s_alert("Não é possível listar as atribuições de <b>" + data.nome + "</b> porque elas estão sendo editadas por <b>" + data.usuario + "</b>");
        });
    }

    obter_psm() {
        if (location.href.indexOf("colaboradores") > -1) return "P";
        if (location.href.indexOf("maquinas") > -1) return "M";
        return "S";
    }

    salvar() {
        if (this.#hab) {
            this.#hab = false;

            let pr_chave, pr_valor, quantidade, validade, obrigatorio;

            if ($('#produto-tab').hasClass('active')) {
                pr_chave = "P";
                pr_valor = $("#produto").val();
                quantidade = $("#quantidade_p").val();
                validade = $("#validade_p").val();
                obrigatorio = $("#obrigatorio_p").val();
            } else {
                pr_chave = "R";
                pr_valor = $("#referencia").val();
                quantidade = $("#quantidade_r").val();
                validade = $("#validade_r").val();
                obrigatorio = $("#obrigatorio_r").val();
            }

            $.post(URL + "/atribuicoes/salvar", {
                _token: $("meta[name='csrf-token']").attr("content"),
                id: this.#idatb,
                psm_chave: this.obter_psm(),
                psm_valor: this.#psm_valor,
                pr_chave: pr_chave,
                pr_valor: pr_valor,
                validade: validade,
                qtd: quantidade,
                obrigatorio: obrigatorio.replace("opt-", "")
            }, (ret) => {
                ret = parseInt(ret);
                if (ret != 201) this.#hab = true;
                switch (ret) {
                    case 201:
                        $("#id_produto_p, #id_produto_r, #produto, #referencia").val("");
                        this.#mostrar();
                        break;
                    case 403:
                        s_alert(pr_chave == 'R' ? "Referência inválida" : "Produto inválido");
                        break;
                    case 404:
                        s_alert(pr_chave == 'R' ? "Referência não encontrada" : "Produto não encontrado");
                        break;
                }
            });
        }
    }

    editar(id) {
        if (this.#idatb != id) {
            const campo = this.#grade ? "referencia" : "produto";
            $.get(URL + "/atribuicoes/mostrar/" + id, (data) => {
                $("#estiloAux").html(".autocomplete-result{display:none}");
                let sufixo = this.#grade ? "_r" : "_p";
                $("#" + campo + ", #validade" + sufixo + ", #quantidade" + sufixo + ", #obrigatorio" + sufixo).each(function() {
                    $(this).attr("disabled", true);
                });
                if (typeof data == "string") data = $.parseJSON(data);
                $("#" + campo).val(data.descr).trigger("keyup");
                setTimeout(() => {
                    $($(".autocomplete-line").first()).trigger("click");
                }, 500);
                setTimeout(() => {
                    $("#validade" + sufixo).val(data.validade);
                    $("#quantidade" + sufixo).val(parseInt(data.qtd));
                    $("#obrigatorio" + sufixo).val("opt-" + data.obrigatorio);
                    $("#estiloAux").html("");
                    $("#validade" + sufixo + ", #quantidade" + sufixo + ", #obrigatorio" + sufixo).each(function() {
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
            let aviso = "Tem certeza que deseja excluir ess";
            aviso += this.#grade ? "a referência?" : "e produto?";
            excluirMain(id, "/atribuicoes", aviso, () => {
                this.#mostrar();
            });
        }
    }

    tentar(e) {
        if (e.keyCode == 13) this.salvar();
    }

    detalhar(id) {
        $.get(URL + "/atribuicoes/grade/" + id, (data) => {
            let resultado = "<thead>" +
                    "<th>Produto</th>" +
                    "<th>Tamanho</th>" +
                "</thead>" +
                "<tbody>";
            if (typeof data == "string") data = $.parseJSON(data);
            $("#detalharAtbModalLabel").html(data[0].referencia);
            data.forEach((produto) => {
                resultado += "<tr>" +
                    "<td>" + produto.descr + "</td>" +
                    "<td>" + produto.tamanho + "</td>" +
                "</tr>";
            });
            resultado += "</tbody>";
            $("#table-detalhar-atb").html(resultado);
            $("#detalharAtbModal").modal();
        });
    }

    preencherValidade(id_produto, tipo) {
        if (id_produto) {
            $.get(URL + "/produtos/validade", {
                id: id_produto,
                tipo: tipo
            }, function(validade) {
                $("#validade").val(parseInt(validade)).trigger("change");
            })
        }
    }

    atualizarQtd() {
        $("#quantidade2_label").html($("#quantidade2").val());
    }

    retirar(id) {
        $("#quantidade2").val(1);
        this.atualizarQtd();
        $.get(URL + "/atribuicoes/produtos/" + id, (data) => {
            let pai = $($("#variacao").parent()).parent();
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
            $("#btn-retirada").off("click").on("click", () => {
                let erro = "";

                if (!$("#data-ret").val()) erro = "Preencha o campo";
                else if (eFuturo($("#data-ret").val())) erro = "A retirada não pode ser no futuro";

                if (!erro) {
                    $.get(URL + "/retiradas/consultar", {
                        atribuicao: id,
                        qtd: $("#quantidade2").val(),
                        pessoa: this.#psm_valor
                    }, (ok) => {
                        if (!parseInt(ok)) {
                            this.#idatb = id;
                            modal2("supervisorModal", ["cpf2", "senha2"]);
                        } else this.#retirarMain(id);
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
            this.atualizarQtd();
            $("#data-ret").val("");
            $("#retiradasModal").modal();
        });
    }

    validarSpv() {
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
                _token: $("meta[name='csrf-token']").attr("content"),
                cpf: apenasNumeros($("#cpf2").val()),
                senha: $("#senha2").val()
            }, (ok) => {
                if (parseInt(ok)) this.#retirarMain(this.#idatb, ok);
                else s_alert("Supervisor inválido");
            });
        } else s_alert(erro);
    }

    recalcular() {
        let lista = Array.from(document.getElementsByClassName("btn-primary"));
        let loader = document.getElementById("loader").style;
        let modal1 = document.getElementById("atribuicoesModal").style;
        let modal2 = document.getElementById("excecoesModal").style;

        lista.forEach((el) => {
            el.style.zIndex = "0";
        });
        loader.display = "flex";
        modal1.zIndex = "0";
        modal2.zIndex = "0";
        $.post(URL + "/atribuicoes/recalcular", {
            _token: $("meta[name='csrf-token']").attr("content")
        }, () => {
            lista.forEach((el) => {
                el.style.removeProperty("z-index");
            });
            modal1.removeProperty("z-index");
            modal2.removeProperty("z-index");
            loader.removeProperty("display");
            this.#mostrar();
        });
    }

    async pergunta_salvar() {
        const resp = await s_alert({
            html: "Deseja salvar as alterações?",
            ync: true
        });
        if (resp.isDenied) {
            await $.post(URL + "/atribuicoes/descartar", {
                _token: $("meta[name='csrf-token']").attr("content")
            });
        } else if (resp.isConfirmed) this.recalcular();
        else $("#atribuicoesModal").modal();
    }

    #mostrar = (_id = 0) => {
        this.#idatb = _id;
        const _tipo2 = this.obter_psm();
        $.get(URL + "/atribuicoes/listar", {
            id: this.#psm_valor,
            tipo: this.#grade ? "R" : "P",
            tipo2: _tipo2
        }, (data) => {
            let resultado = "";
            if (typeof data == "string") data = $.parseJSON(data);
            if (data.length) {
                resultado += "<thead>" +
                    "<tr>" +
                        "<th>" + (this.#grade ? "Referência" : "Produto") + "</th>" +
                        "<th>Obrigatório?</th>" +
                        "<th class = 'text-right'>Qtde.</th>" +
                        "<th class = 'text-right'>Validade</th>" +
                        "<th>&nbsp;</th>" +
                    "</tr>" +
                "</thead>" +
                "<tbody>";
                data.forEach((atribuicao) => {
                    let acoes = "";
                    if (this.#grade) acoes += "<i class = 'my-icon far fa-eye' title = 'Detalhar' onclick = 'atribuicao.detalhar(" + atribuicao.id + ")'></i>";
                    if (_tipo2 == "P" && permissoes.retiradas) acoes += "<i class = 'my-icon far fa-hand-holding-box' title = 'Retirar' onclick = 'atribuicao.retirar(" + atribuicao.id + ")'></i>";
                    if (["M", "S"].indexOf(_tipo2) > -1) {
                        acoes += "<i class = 'my-icon far fa-user" + (_tipo2 == "M" ? "s" : "") + "-slash' title = 'Exceções' onclick = 'excecao = new Excecoes(" + atribuicao.id + ")'></i>";
                    }
                    if (parseInt(atribuicao.pode_editar)) {
                        acoes += "<i class = 'my-icon far fa-edit' title = 'Editar' onclick = 'atribuicao.editar(" + atribuicao.id + ")'></i>" +
                            "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'atribuicao.excluir(" + atribuicao.id + ")'></i>";
                    }
                    if (!acoes) acoes = "---";
                    resultado += "<tr>" +
                        "<td>" +
                            "<span class = 'linha-atb " + (atribuicao.rascunho == "S" ? "old" : "new") + "'>" + atribuicao.pr_valor + "</span>" +
                        "</td>" +
                        "<td>" + atribuicao.obrigatorio + "</td>" +
                        "<td class = 'text-right'>" + parseInt(atribuicao.qtd) + "</td>" +
                        "<td class = 'text-right'>" + atribuicao.validade + "</td>" +
                        "<td class = 'text-center manter-junto'>" + acoes + "</td>" +
                    "</tr>";
                });
                resultado += "</tbody>";
                $($("#table-atribuicoes").parent()).addClass("pb-4");
                if (this.obter_psm() == "M") $($("#atribuicoesModal div.atribuicoes").parent()).removeClass("mb-5");
                else $($("#atribuicoesModal div.atribuicoes").parent()).removeClass("mb-5");
                this.#hab = true;
            } else {
                $($("#table-atribuicoes").parent()).removeClass("pb-4");
                $($("#atribuicoesModal div.atribuicoes").parent()).removeClass("mb-5");
            }
            $("#table-atribuicoes").html(resultado);
            $("#referencia").attr("disabled", false);
            $("#produto").attr("disabled", false);
            $.get(URL + "/atribuicoes/permissao", {
                id: this.#psm_valor,
                tipo: this.#grade ? "R" : "P",
                tipo2: this.obter_psm()
            }, (resp) => {
                if (typeof resp == "string") resp = $.parseJSON(resp);
                if (resp.sou_eu !== undefined) $("#col-btn-salvar").removeClass("d-none").addClass("d-flex");
                else $("#col-btn-salvar").addClass("d-none").removeClass("d-flex");
            });
        });
    }

    #retirarMain = async (id, _supervisor = 0) => {
        await $.post(URL + "/retiradas/salvar", {
            _token: $("meta[name='csrf-token']").attr("content"),
            supervisor: _supervisor,
            atribuicao: id,
            pessoa: this.#psm_valor,
            produto: $("#variacao").val().replace("prod-", ""),
            data: $("#data-ret").val(),
            quantidade: $("#quantidade2").val()
        });
        $("#supervisorModal").modal("hide");
        $("#retiradasModal").modal("hide");
        await s_alert({
            icon: "success"
        });
        listar();
    }
}