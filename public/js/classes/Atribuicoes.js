class Atribuicoes {
    #idatb = 0;
    #hab = true;
    #grade; 
    #psm_valor;
    #pessoa_selecionada;
    #ajaxRequest = null;

    constructor(grade, _psm_valor) {
        this.#grade = grade;
        this.#psm_valor = _psm_valor;

        $('a[data-toggle="tab"]').on('shown.bs.tab', (e) => {
            const abaAtivaId = $(e.target).attr("id");
            
            this.#grade = (abaAtivaId == 'grade-tab');
            
            this.#mostrar();
        });

        $('#produto, #referencia').each((index, element) => {
            const $element = $(element);
            const tipo = $element.attr('id');
            const campoIdOculto = $element.data('input');

            $element.select2({
                width: '100%',
                placeholder: `Digite para buscar...`,
                allowClear: true,
                language: {
                    noResults: () => "Nenhum resultado encontrado",
                    searching: () => "Buscando..."
                },
                ajax: {
                    url: `${URL}/autocomplete`,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => {
                        let filtroDinamico = '';
                        if (tipo === 'referencia') {
                            const _psm_chave = this.obter_psm();
                            const _psm_valor = this.#psm_valor;
                            filtroDinamico = `${_psm_chave}|${_psm_valor}`;
                        }
                        return {
                        table: $element.data('table'),
                        column: $element.data('column'),
                        filter_col: $element.data('filter_col') || '',
                        filter: filtroDinamico, // Usa o filtro dinâmico
                        search: params.term
                    };
                        // table: $element.data('table'),
                        // column: $element.data('column'),
                        // filter_col: $element.data('filter_col') || '',
                        // filter: $element.data('filter') || '',
                        // search: params.term
                    },
                    processResults: (data) => {
                        const column = $element.data('column');
                        return {
                            results: data.map(item => ({
                                id: item.id,
                                text: item[column]
                            }))
                        };
                    },
                    cache: true
                },
                templateSelection: function (data) {
                    return $('<span>').html(data.text).text();
                },

                escapeMarkup: function (markup) {
                    return markup;
                }
            }).on('select2:select', (e) => {
                const data = e.params.data;
                if (data.id && typeof this.preencherValidade === 'function') {
                    $(campoIdOculto).val(data.id);
                    this.preencherValidade(data.id, tipo === 'produto' ? 'P' : 'R');
                }
            }).on('select2:unselect', () => {
                $(campoIdOculto).val('');
            });
        });

        $.get(`${URL}/atribuicoes/permissao`, {
            id: this.#psm_valor,
            tipo: this.#grade ? "R" : "P",
            tipo2: this.obter_psm()
        }, (data) => {
            if (typeof data === "string") data = $.parseJSON(data);
            if (parseInt(data.code) === 200) {
                modal("atribuicoesModal", 0, () => {
                    const _psm_chave = this.obter_psm();
                    const url = `${URL}/${_psm_chave === 'P' ? 'colaboradores' : (_psm_chave === 'S' ? 'setores' : 'maquinas')}/mostrar${_psm_chave !== 'M' ? '2' : ''}/${this.#psm_valor}`;
                    
                    $.get(url, (resp) => {
                        if (typeof resp === "string") resp = $.parseJSON(resp);
                        $("#atribuicoesModalLabel").html(`${resp[_psm_chave === "P" ? "nome" : "descr"].toUpperCase()} - Atribuindo`);
                        
                        $('#produto, #referencia').val(null).trigger('change');
                        $("#quantidade_p, #quantidade_r, #validade_p, #validade_r").val(1);

                        if (this.#grade) {
                            $('#atribuicoesTab a[href="#grade-pane"]').tab('show');
                        } else {
                            $('#atribuicoesTab a[href="#produto-pane"]').tab('show');
                        }
                        
                        this.#mostrar();
                    });
                });
            } else {
                s_alert(`Não é possível listar as atribuições de <b>${data.nome}</b> porque elas estão sendo editadas por <b>${data.usuario}</b>`);
            }
        });
    }

    obter_psm() {
        if (location.href.indexOf("colaboradores") > -1) return "P";
        if (location.href.indexOf("maquinas") > -1) return "M";
        return "S";
    }

    salvar() {
        if (!this.#hab) return;
        this.#hab = false;

        const isProduto = $('#produto-tab').hasClass('active');
        const pr_chave = isProduto ? "P" : "R";
        
        const textoComHtml = $(isProduto ? "#produto option:selected" : "#referencia option:selected").text();
        const pr_valor = $('<span>').html(textoComHtml).text();
        
        if (!pr_valor) {
            s_alert(`Por favor, selecione ${isProduto ? 'um produto' : 'uma referência'} da lista.`);
            this.#hab = true;
            return;
        }
        
        const quantidade = $(isProduto ? "#quantidade_p" : "#quantidade_r").val();
        const validade = $(isProduto ? "#validade_p" : "#validade_r").val();
        const obrigatorio = $(isProduto ? "#obrigatorio_p" : "#obrigatorio_r").val();
        console.log(pr_valor)

        $.post(`${URL}/atribuicoes/salvar`, {
            _token: $("meta[name='csrf-token']").attr("content"),
            id: this.#idatb,
            psm_chave: this.obter_psm(),
            psm_valor: this.#psm_valor,
            pr_chave,
            pr_valor: pr_valor,
            validade,
            qtd: quantidade,
            obrigatorio: obrigatorio.replace("opt-", "")
        }, (ret) => {
            this.#hab = true;
            
            switch (parseInt(ret)) {
                case 201:
                    // Limpa o Select2 da forma correta após o sucesso
                    $('#produto, #referencia').val(null).trigger('change');
                    this.#mostrar();
                    break;
                case 403:
                    s_alert(pr_chave === 'R' ? "Referência inválida" : "Produto inválido");
                    break;
                case 404:
                    s_alert(pr_chave === 'R' ? "Referência não encontrada" : "Produto não encontrado");
                    break;
                default:
                    s_alert("Ocorreu um erro ao salvar a atribuição.");
                    break;
            }
        });
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

    setPessoaRetirando() {
        if (!$("#pessoa-retirando").val()) {
            $("#pessoa-retirando").addClass("invalido");
            s_alert("Preencha o campo");
            return;
        }
        $.get(URL + "/colaboradores/consultar2", {
            pessoa : $("#pessoa-retirando").val(),
            id_pessoa : $("#pessoa-retirando-id").val()
        }, function(resp) {
            if (resp == "ok") {
                this.#pessoa_selecionada = $("#pessoa-retirando-id").val();
                $("#pessoaRetiradaModal").modal("hide");
                atribuicao.retirar($("#id_atribuicao").val());
            } else s_alert("Colaborador não encontrado");
        }.bind(this))
    }

    retirar(id) {
        const psm = this.obter_psm();
        let _id_pessoa;
        if (psm == "P") _id_pessoa = this.#psm_valor;
        else _id_pessoa = this.#pessoa_selecionada;
        if (_id_pessoa === undefined) {
            modal("pessoaRetiradaModal", 0, function() {
                $("#id_atribuicao").val(id);
                $("#pessoa-retirando").attr("data-filter_col", psm == "S" ? "id_setor" : "v_maquina");
                $("#pessoa-retirando").attr("data-filter", this.#psm_valor);
                $("#pessoa-retirando").attr("data-atribuicao", id);
            }.bind(this));
            return;
        }
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
                        pessoa: _id_pessoa
                    }, (ok) => {
                        this.#pessoa_selecionada = _id_pessoa;
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

        if (this.#ajaxRequest) {
            this.#ajaxRequest.abort();
        }

        this.#ajaxRequest = $.get(URL + "/atribuicoes/listar", {
            id: this.#psm_valor,
            tipo: this.#grade ? "R" : "P",
            tipo2: _tipo2
        }, (data) => {
            let resultado = "";
            if (typeof data == "string") data = $.parseJSON(data);
            if (data && data.length > 0) {
                $('#table-container').removeClass('d-none');
                $('#no-results-container').addClass('d-none');
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
                    if (permissoes.retiradas) acoes += "<i class = 'my-icon far fa-hand-holding-box' title = 'Retirar' onclick = 'atribuicao.retirar(" + atribuicao.id + ")'></i>";
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
                $('#table-container').removeClass('d-none');
                $('#no-results-container').addClass('d-none');
            } else {
                $($("#table-atribuicoes").parent()).removeClass("pb-4");
                $($("#atribuicoesModal div.atribuicoes").parent()).removeClass("mb-5");
                $('#table-container').addClass('d-none');
                $('#no-results-container').removeClass('d-none');
            }
            this.#hab = true;
            $("#table-atribuicoes").animate({ opacity: 0.3 }, 400, function() {
                $(this).html(resultado).animate({ opacity: 1 }, 400);
            });
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
            pessoa: this.#pessoa_selecionada,
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