let relatorio, pessoa, atribuicao, excecao, colGlobal;
let anteriores = new Array();
let validacao_bloqueada = false;
let focar = true;
let grupo_emp2 = 0;

jQuery.fn.sortElements = (function() {
    var sort = [].sort;

    return function(comparator, getSortable) {    
        getSortable = getSortable || function() {
            return this;
        };

        var placements = this.map(function() {    
            var sortElement = getSortable.call(this),
                parentNode = sortElement.parentNode,
                nextSibling = parentNode.insertBefore(
                    document.createTextNode(""),
                    sortElement.nextSibling
                );
            
            return function() {
                if (parentNode === this) {
                    throw new Error(
                        "You can't sort elements if any one is a descendant of another."
                    );
                }
                parentNode.insertBefore(this, nextSibling);
                parentNode.removeChild(nextSibling);                
            }
        });
       
        return sort.call(this, comparator).each(function(i) {
            placements[i].call(getSortable.call(this));
        });
    };
})();

$(document).ready(function() {
    $(".modal-body .row:not(.sem-margem)").each(function() {
        if ($(this).prev().hasClass("row")) $(this).css("margin-top", $(this).prev().find(".tam-max").length ? "-14px" : "11px");
    });

    $(".modal-body button").each(function() {
        $($(this).parent()).css("padding-top", "1px");
    });

    $("#busca").keyup(function(e) {
        if (e.keyCode == 13) listar();
    });

    $(document).on("keydown", "form", function(event) { 
        const enter = event.key == "Enter";
        if (enter && !validacao_bloqueada) {
            try {
                pessoa.validar();
            } catch(err) {
                try {
                    relatorio.validar();
                } catch(err) {
                    try {
                        validar_estoque();    
                    } catch(err) {
                        try {
                            validar_comodato();
                        } catch(err) {
                            validar();
                        }
                    }
                }
            }
        }
        return !enter;
    });

    $(".sortable-columns > th:not(.nao-ordena)").each(function() {
        var th = $(this),
            thIndex = th.index(),
            table = $($(this).parent().attr("for"));
        
        th.click(function() {
            var inverse = $(this).hasClass("text-dark") && $(this).html().indexOf("fa-sort-down") > -1;
            if ($(this).hasClass("nao-inverte")) {
                inverse = !inverse;
                $(this).removeClass("nao-inverte");
            }
            $(this).parent().find(".text-dark").removeClass("text-dark");
            $(this).parent().find(".my-icon").remove();
            $(this).addClass("text-dark");
            $(this).append(inverse ? "<i class = 'my-icon ml-2 fad fa-sort-up'></i>" : "<i class = 'my-icon ml-2 fad fa-sort-down'></i>");
            $(".sortable-columns > th:not(.nao-ordena)").each(function() {
                if (!$(this).hasClass("text-dark")) $(this).append("<i class = 'my-icon ml-2 fa-light fa-sort'></i>");
            });
            table.find("td").filter(function() {
                return $(this).index() === thIndex;
            }).sortElements(function(a, b) {
                return $.text([a]) > $.text([b]) ? inverse ? -1 : 1 : inverse ? 1 : -1;
            }, function() {
                return this.parentNode;
            });
            colGlobal = thIndex;
        });
    });

    carrega_autocomplete();

    carrega_dinheiro();

    $("input.data").each(function() {
        let that = $(this);
        $(that).datepicker({
            dateFormat: "dd/mm/yy",
            closeText: "Fechar",
            prevText: "Anterior",
            nextText: "Próximo",
            currentText: "Hoje",
            monthNames: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"],
            monthNamesShort: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"],
            dayNames: ["Domingo", "Segunda-feira", "Terça-feira", "Quarta-feira", "Quinta-feira", "Sexta-feira", "Sábado"],
            dayNamesShort: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"],
            dayNamesMin: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb"],
            weekHeader: "Sm",
            firstDay: 1,
            beforeShow: function(elem, dp) {
                setTimeout(function() {
                    $(dp.dpDiv[0]).css("width", (elem.offsetWidth > 272 ? elem.offsetWidth : 272) + "px");
                }, 0);
            },
            onSelect: function() {
                const el = document.getElementById(that.data().prox);
                if (el !== null) {
                    setTimeout(function() {
                        $(el).focus();
                    }, $(el).hasClass("data") ? 100 : 0);
                }
            }
        });
        $(that).keyup(function() {
            let resultado = apenasNumeros($(that).val());
            if (resultado.length >= 8) {
                resultado = resultado.substring(0, 8);
                resultado = resultado.substring(0, 2) + "/" + resultado.substring(2, 4) + "/" + resultado.substring(4, 8);
                $(that).val(resultado);    
            }
        });
        $(that).blur(function() {
            let aux = $(that).val().split("/");
            data = new Date(parseInt(aux[2]), parseInt(aux[1]) - 1, parseInt(aux[0]));
            if (data.getFullYear() != aux[2] || data.getMonth() + 1 != aux[1] || data.getDate() != aux[0]) $(that).val("");
        });
    });

    $("#relatorioBilateralModal").on("hide.bs.modal", function() {
        if ($("#rel-grupo1").val() == "maquinas-por-empresa") relatorio.inverter();
    });

    $("#estoqueModal").on("hide.bs.modal", function() {
        $("#estoqueModal .remove-linha").each(function() {
            $(this).trigger("click");
        });
        $("#estoqueModal #produto-1").val("");
        $("#estoqueModal #id_produto-1").val("");
        $("#estoqueModal #es-1").val("E");
        $("#estoqueModal #preco-1").val(0);
        $("#estoqueModal #qtd-1").val(1);
        $("#estoqueModal #obs-1").val("ENTRADA");
    });

    ["cp", "mp"].forEach((tipo) => {
        $("#" + tipo + "Modal").on("hide.bs.modal", function() {
            if (document.querySelector("#" + tipo + "Modal .form-search.new") === null) cp_mp_limpar_tudo(tipo);
            else cp_mp_pergunta_salvar(tipo);
        });
        $("#" + tipo + "Modal .form-control-lg").each(function() {
            $(this).on("keyup", function(e) {
                if (e.keyCode == 13) cp_mp_listar(tipo, true);
            }).on("focus", function() {
                validacao_bloqueada = true;
            }).on("blur", function() {
                validacao_bloqueada = false;
            });
        });
    });

    $("#atribuicoesModal").on("hide.bs.modal", function() {
        if (document.querySelector("#atribuicoesModal .linha-atb.new") !== null) atribuicao.pergunta_salvar();
    });

    $("#setoresModal").on("hide.bs.modal", function() {
        $(".linha-usuario").each(function() {
            $(this).remove();
        });
    });

    $(".modal").each(function() {
        let that = this;
        $(this).on("shown.bs.modal", function () {
            let cont = 0;
            if (focar) {
                do {
                    var el = $($("#" + that.id + " input[type=text]")[cont]);
                    el.focus();
                    cont++;
                } while ($($(el).parent()).hasClass("d-none") || $(el).attr("disabled"))
            } else focar = true;
            carrega_autocomplete();
        })
    });

    $(".form-control").each(function() {
        $(this).keydown(function() {
            $(this).removeClass("invalido");
        });
    });

    $(".user-pic .m-auto").each(function() {
        $(this).html($(this).html().replaceAll("\n", "").replaceAll(" ", ""));
    });

    $.get(URL + "/colaboradores/mostrar/" + USUARIO, function(data) {
        if (typeof data == "string") data = $.parseJSON(data);
        foto_pessoa(".main-toolbar .user-pic", data.foto ? data.foto : "");
    });

    document.querySelector(".user-card").onmouseover = function() {
        document.querySelector(".dropdown-toolbar-user").style.display = "block";
    }

    document.querySelector(".user-card").onmouseleave = function(e) {
        let el = document.querySelector(".dropdown-toolbar-user");
        if (!el.contains(e.target) && !document.querySelector(".main-toolbar.shadow-sm").contains(e.target)) el.style.removeProperty("display");
    }

    setTimeout(function() {
        [".main-toolbar.shadow-sm", ".dropdown-toolbar-user"].forEach((seletor) => {
            document.querySelector(seletor).onmouseleave = function() {
                document.querySelector(".dropdown-toolbar-user").style.removeProperty("display");
            }
        })
    }, 200);
    
    let url = "";
    ["categorias", "empresas", "maquinas", "pessoas", "produtos", "setores"].forEach((view) => {
        if (location.href.indexOf(view) > -1) url = view;
    });

    try {
        if (ID) {
            $.get(URL + "/obter-descr", {
                id : ID,
                tabela : url
            }, function (val) {
                if (url == "empresas") grupo_emp2 = val;
                // else $("#busca").val(val);
                listar(url == "produtos" ? 1 : 0);
                avisarSolicitacao();
            });
        } else if (FILTRO) $("#busca").val(FILTRO);
        
        if (!ID) {
            listar(url == "produtos" ? 1 : 0);
            avisarSolicitacao();
        }
    } catch(err) {
        listar(url == "produtos" ? 1 : 0);
        avisarSolicitacao();
    }
});

async function avisarSolicitacao() {
    let comodatos = await $.get(URL + "/solicitacoes/meus-comodatos");
    if (typeof comodatos == "string") comodatos = $.parseJSON(comodatos);

    for (let i = 0; i < comodatos.length; i++) {
        let retorno;
        retorno = await $.get(URL + "/solicitacoes/aviso/" + comodatos[i]);
        retorno = $.parseJSON(retorno);
        if (retorno !== 200) {
            let texto = "";
            if (retorno.status != "A") {
                texto = "Sua solicitação feita no dia " + retorno.criacao + " foi ";
                switch (retorno.status) {
                    case "E":
                        texto += "aceita";
                        break;
                    case "F":
                        texto += "finalizada";
                        break;
                    case "R":
                        texto += "recusada";
                        break;
                }
                if (retorno.status != "E") texto += " no dia " + retorno.data;
                texto += " por " + retorno.usuario_erp;
                if (retorno.status == "E") texto += " e tem prazo para o dia " + retorno.data;
            } else if (retorno.possui_inconsistencias) texto = "Sua solicitação feita no dia " + retorno.criacao + " teve alguns produtos marcados como inexistentes";
            if (texto != "") {
                if (retorno.possui_inconsistencias) {
                    texto += ".<br>Deseja verificar";
                    if (retorno.status != "A") texto += " as diferenças";
                    texto += "?";
                    let viz = await s_alert({
                        icon : "success",
                        html : texto,
                        yn : true
                    });
                    if (viz) {
                        let link = document.createElement("a");
                        link.href = URL + "/relatorios/solicitacao/" + retorno.id;
                        link.target = "_blank";
                        link.click();
                    }
                } else {
                    await s_alert({
                        icon : "EF".indexOf(retorno.status) > -1 ? "success" : "warning",
                        html : texto
                    });
                }
            }
        }
    }
}

async function excluirMain(_id, prefixo, aviso, callback) {
    const resp = await s_alert({
        icon : "warning",
        html : aviso,
        invert : true
    });
    if (resp) {
        await $.post(URL + prefixo + "/excluir", {
            _token : $("meta[name='csrf-token']").attr("content"),
            id : _id
        });
        callback();
    }
}

async function controleTodos(ids) {
    let lista = Array.from(document.getElementsByClassName("btn-primary"));
    let loader = document.getElementById("loader").style;
    let modal = document.getElementById("relatorioControleModal").style;
    let algum_existe = false;
    let elementos = relObterElementos(["pessoa1", "consumo1", "inicio2", "fim2"]);
    lista.forEach((el) => {
        el.style.zIndex = "0";
    });
    loader.display = "flex";
    modal.zIndex = "0";
    for (let i = 0; i < ids.length; i++) {
        $(elementos.id_pessoa).val(ids[i]);
        let existe = await $.get(URL + "/relatorios/controle/existe", {
            id_pessoa : ids[i],
            consumo : $(elementos.consumo).val(),
            inicio : $(elementos.inicio).val(),
            fim : $(elementos.fim).val()
        });
        if (parseInt(existe)) {
            algum_existe = true;
            $("#relatorioControleModal form").submit();
        }
    }
    lista.forEach((el) => {
        el.style.removeProperty("z-index");
    });
    modal.removeProperty("z-index");
    loader.removeProperty("display");
    $(elementos.id_pessoa).val("");
    if (!algum_existe) {
        $(elementos.pessoa).addClass("invalido");
        s_alert("Colaborador não encontrado");
    }
}

async function cp_mp_validar_main(tipo) {
    limpar_invalido();
    let erro = "";
    let req = tipo == "cp" ? {
        produtos_descr : obter_vetor("produto", "cp"),
        produtos_id : obter_vetor("id-produto", "cp"),
        id_maquina : $($(".id_maquina")[0]).val()
    } : {
        maquinas_descr : obter_vetor("maquina", "mp"),
        maquinas_id : obter_vetor("id-maquina", "mp"),
        id_produto : $("#id_produto").val()
    };
    req.precos = obter_vetor("preco", tipo);
    req.maximos = obter_vetor("maximo", tipo);
    let data = await $.get(URL + "/" + (tipo == "cp" ? "maquinas/produto" : "produtos/maquina") + "/consultar", req);
    if (typeof data == "string") data = $.parseJSON(data);
    if (!erro && data.texto) {
        for (let i = 0; i < data.campos.length; i++) {
            let el = $("#cpModal #" + data.campos[i]);
            $(el).val(data.valores[i]);
            $(el).trigger("keyup");
            $(el).addClass("invalido");
        }
        erro = data.texto;
    }
    if (erro) return erro;
    $("#" + tipo + "Modal .preco").each(function() {
        $(this).val(parseInt(apenasNumeros($(this).val())) / 100);
    });
    $("#" + tipo + "Modal form").submit();
    return "";
}

async function cp_mp_validar(tipo) {
    const erro = await cp_mp_validar_main(tipo);
    if (erro) s_alert(erro);
}

async function cp_mp_pergunta_salvar(tipo) {
    const resp = await s_alert({
        html : "Deseja salvar as alterações?",
        ync : true
    });
    if (resp.isConfirmed) {
        let erro = await cp_mp_validar(tipo);
        if (erro) {
            cp_mp_limpar_tudo(tipo);
            s_alert({
                icon : "error",
                title : "Não foi possível salvar"
            });
        }
    } else if (resp.isDenied) cp_mp_limpar_tudo(tipo);    
    else $("#" + tipo + "Modal").modal();
}

function ordenar(coluna) {
    if (coluna === undefined) {
        coluna = colGlobal;
        $($(".sortable-columns").children()[coluna]).addClass("nao-inverte");
    }
    $($(".sortable-columns").children()[coluna]).trigger("click");
    if (ID && !EMPRESA) chamar_modal(ID);
}

function obter_vetor(classe, nome) {
    let resultado = new Array();
    $("#" + nome + "Modal ." + classe).each(function() {
        resultado.push(classe == "preco" ? apenasNumeros($(this).val()) : $(this).val());
    });
    return resultado.join("|!|");
}

function contar_char(el, max) {
    $(el).removeClass("invalido");
    $(el).val($(el).val().substring(0, max));
    $(el).next().html($(el).val().length + "/" + max);
}

function mostrarImagemErro() {
    $("#nao-encontrado").removeClass("d-none");
    $($("#nao-encontrado").prev()).find(".card").addClass("d-none");
    $($("#nao-encontrado").prev()).removeClass("h-100");
}

function esconderImagemErro() {
    $("#nao-encontrado").addClass("d-none");
    $($("#nao-encontrado").prev()).find(".card").removeClass("d-none");
    $($("#nao-encontrado").prev()).addClass("h-100");
}

function modal(nome, id, callback) {
    const concluir = function() {
        if (!id && ["pessoasModal", "setoresModal", "maquinasModal", "categoriasModal"].indexOf(nome) > -1) {
            let el = $("#" + (nome == "pessoasModal" ? "nome" : "descr"));
            $(el).val($("#busca").val());
            $(el).trigger("keyup");
        }
        $("#" + nome).modal();
        callback();
    }

    limpar_invalido();
    if (callback === undefined) callback = function() {}
    if (id) $("#" + (nome == "pessoasModal" ? "pessoa-id" : "id")).val(id);
    $("#" + nome + " input[type=text], #" + nome + " input[type=number], #" + nome + " input[type=hidden], #" + nome + " textarea").each(function() {
        if (!id && $(this).attr("name") != "_token" && (!(nome == "pessoasModal" && $(this).attr("name") == "tipo"))) $(this).val("");
        if (!$(this).hasClass("autocomplete")) $(this).trigger("keyup");
        anteriores[$(this).attr("id")] = $(this).val();
    });
    if (!id) {
        $("#" + nome + " input[type=checkbox]").each(function() {
            $(this).prop("checked", false);
        });
    }
    if (nome == "pessoasModal") {
        $.get(URL + "/colaboradores/modal", function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            let primeiro = 0;
            let resultado = !EMPRESA ? "<option value = '0'>--</option>" : "";
            data.empresas.forEach((empresa) => {
                resultado += "<option value = '" + empresa.id + "'" + (data.filial == "S" ? " disabled" : "") + ">" + empresa.nome_fantasia + "</option>";
                if (data.filial != "S" && !primeiro) primeiro = parseInt(empresa.id);
                empresa.filiais.forEach((filial) => {
                    resultado += "<option value = '" + filial.id + "'>- " + filial.nome_fantasia + "</option>";
                    if (!primeiro) primeiro = parseInt(filial.id);
                });
            });
            $("#pessoa-empresa-select").html(resultado);
            try {
                if (TIPO != "A") $("#pessoa-empresa-select").val(primeiro);
            } catch(err) {}
            pessoa.setorPorEmpresa(function() {
                concluir();
            });
        });
    } else concluir();
}

function modal2(nome, limpar) {
    limpar_invalido();
    limpar.forEach((id) => {
        $("#" + id).val("");
    });
    $("#" + nome).modal();
}

function excluir(_id, prefixo, e) {
    if (e !== undefined) e.preventDefault();
    $.get(URL + prefixo + "/aviso/" + _id, function(data) {
        if (typeof data == "string") data = $.parseJSON(data);
        if (parseInt(data.permitir)) {
            excluirMain(_id, prefixo, data.aviso, function() {
                location.reload();
            });
        } else s_alert(data.aviso);
    });
}

function carrega_atalhos() {
    Array.from(document.getElementsByClassName("atalho")).forEach((el) => {
        el.title = "Cadastro de " + (el.dataset.atalho == "setores" ? "centro de custos" : el.dataset.atalho);
        el.onclick = function() {
            const concluir = function(_link, _req, _achou) {
                let caminho = URL + "/" + _link;
                if (_achou) caminho += "?" + $.param(_req);
                let clique = document.createElement("a");
                clique.href = caminho;
                clique.target = "_blank";
                clique.click();
            }

            let req = {};
            let _id = "";
            let _filtro = "";
            let achou = false;
            let link = el.dataset.atalho;
            let campoId = el.dataset.campo_id;
            let campoDescr = el.dataset.campo_descr;
            if (link == "pessoas") link = "colaboradores/pagina/F";
            if (campoId && campoId.indexOf("#") == -1) campoId = "#" + campoId;
            if (campoDescr && campoDescr.indexOf("#") == -1) campoDescr = "#" + campoDescr;            
            
            if (!campoId) campoId = "#nao";
            if (!campoDescr) campoDescr = "#nao";

            let elCampoId = document.querySelector(campoId);
            if (elCampoId !== null) {
                if (elCampoId.value.trim()) {
                    _id = elCampoId.value.trim();
                    achou = true;
                }
            }
            let elCampoDescr = document.querySelector(campoDescr);
            if (elCampoDescr !== null) {
                if (elCampoDescr.value.trim()) {
                    _filtro = elCampoDescr.value.trim();
                    achou = true;
                }
            }

            achou = achou && !EMPRESA;
            if (achou) {
                if (elCampoDescr !== null && elCampoId !== null) {
                    $.get(URL + "/consultar-geral", {
                        id : _id,
                        filtro : _filtro,
                        tabela : el.dataset.atalho
                    }, function(ret) {
                        if (parseInt(ret)) req.id = _id;
                        else req.filtro = _filtro;
                        concluir(link, req, achou);
                    });
                } else {
                    if (_id) req.id = _id;
                    else if (_filtro) req.filtro = _filtro;
                    concluir(link, req, achou);
                }
            } else concluir(link, req, achou);
        }
    });
}

function autocomplete(_this) {
    var _table = _this.data().table,
        _column = _this.data().column,
        _filter = _this.data().filter,
        _filter_col = _this.data().filter_col,
        _search = _this.val(),
        input_id = _this.data().input,
        element = _this,
        div_result;

    $(document).click(function (e) {
        if (e.target.id != element.prop("id")) {
            div_result.remove();
        }
    });

    if (!element.parent().find(".autocomplete-result").length) {
        div_result = $("<div class = 'autocomplete-result' style = 'width:" + document.querySelector(
            $(element).data("input").indexOf(" ") == -1 ? ("#" + $(element).attr("id")) : $(element).data("input").replace("id_", "")
        ).offsetWidth + "px'>");
        element.after(div_result);
    } else {
        div_result = element.parent().find(".autocomplete-result");
        div_result.empty();
    }

    if (!_search) $(input_id).val($(this).data().id).trigger("change");
    $.get(URL + "/autocomplete", {
        table : _table,
        column : _column,
        filter_col : _filter_col,
        filter : _filter,
        search : _search
    }, function (data) {
        if (typeof data == "string") data = $.parseJSON(data);
        div_result.empty();
        data.forEach((item) => {
            div_result.append("<div class = 'autocomplete-line' data-id = '" + item.id + "'>" + item[_column] + "</div>");
        });
        element.parent().find(".autocomplete-line").each(function () {
            $(this).click(function () {
                $(input_id).val($(this).data().id).trigger("change");
                element.val($(this).text());
                div_result.remove();
                const el = document.getElementById(element.data().prox);
                if (el !== null) el.focus();
            });

            $(this).mouseover(function () {
                $(input_id).val($(this).data().id).trigger("change");
                element.val($(this).text());
                $(this).parent().find(".hovered").removeClass("hovered");
                $(this).addClass("hovered");
            });
        });
    });
}

function carrega_autocomplete() {
    $(".autocomplete").each(function() {
        $(this).keyup(function(e) {
            $(this).removeClass("invalido");
            if (e.keyCode == 13) validacao_bloqueada = true;
            if ([9, 13, 17, 38, 40].indexOf(e.keyCode) == -1 && $(this).val().trim()) autocomplete($(this));
            if (!$(this).val().trim()) $($(this).data().input).val("");
            setTimeout(function() {
                validacao_bloqueada = false;
            }, 50);
        });

        $(this).keydown(function(e) {
            if ([9, 13, 38, 40].indexOf(e.keyCode) > -1) {
                if (e.keyCode == 13) {
                    e.preventDefault();
                    validacao_bloqueada = true;
                }
                seta_autocomplete(e.keyCode, $(this));
            }
        });
    });
    carrega_atalhos();
}

function seta_autocomplete(direcao, _this) {
    _this = _this.parent();
    var el = _this.find(".autocomplete-result .autocomplete-line");
    var el_hovered = _this.find(".autocomplete-result .autocomplete-line.hovered");
    var target = el.first();
    if (el_hovered.length) {
        switch(direcao) {
            case 38:
                target = el_hovered.prev();
                break;
            case 40:
                target = el_hovered.next();
                break;
            default:
                target = el_hovered;
                break;
        }
    }
    target.trigger(([38, 40].indexOf(direcao) > -1) ? "mouseover" : "click");
}

function carrega_dinheiro() {
    $(".dinheiro-editavel").each(function() {
        $($(this)[0]).focus(function() {
            if ($(this).val() == "") $(this).val("R$ 0,00");
        });
        $($(this)[0]).keyup(function() {
            let texto_final = $(this).val();
            if (texto_final == "") $(this).val("R$ 0,00");
            $(this).val(dinheiro(texto_final));
        });
        $(this).addClass("text-right");
        $(this).trigger("keyup");
    });
}

function verifica_vazios(arr, _erro, pai) {
    if (_erro === undefined) _erro = "";
    if (pai === undefined) pai = "";
    if (pai) pai = "#" + pai + " ";
    let _alterou = false;
    arr.forEach((id) => {
        let el = $(pai + "#" + id);
        let erro_ou_vazio = !$(el).val();
        if (!erro_ou_vazio && id.indexOf("qtd-") > -1) erro_ou_vazio = !parseInt($(el).val());
        if (erro_ou_vazio) {
            if (!_erro) _erro = "Preencha o campo";
            else _erro = "Preencha os campos";
            $(el).addClass("invalido");
        }
        try {
            if ($(el).val().toString().toUpperCase().trim() != anteriores[id].toString().toUpperCase().trim()) _alterou = true;
        } catch(err) {}
    });
    return {
        alterou : _alterou,
        erro : _erro
    };
}

function limpar_invalido() {
    $("input, select").each(function() {
        $(this).removeClass("invalido");
    });
}

function hoje() {
    return new Date().toJSON().slice(0, 10).split('-').reverse().join('/');
}

function validar_datas(el_inicio, el_fim, comodato) {
    let erro = "";
    let aux = $(el_inicio).val().split("/");
    const inicio = new Date(aux[2], aux[1] - 1, aux[0]);
    aux = $(el_fim).val().split("/");
    const fim = new Date(aux[2], aux[1] - 1, aux[0]);
    if (inicio > fim) erro = "A data inicial não pode ser maior que a data final";
    else if (inicio.getTime() == fim.getTime() && comodato) erro = "A locação precisa durar mais de um dia";
    if (!comodato && erro) {
        $(el_inicio).addClass("invalido");
        $(el_fim).addClass("invalido");
    }
    return erro;
}

function eFuturo(data) {
    data = data.split("/");
    const hj = new Date();
    const comp = new Date(data[2], data[1] - 1, data[0]);
    return comp > hj;
}

function relObterElementos(lista) {
    let resultado = {};
    lista.forEach((item) => {
        let chave = item.replace(/[0-9]/g, '');
        resultado[chave] = $("#rel-" + item);
        let el = document.getElementById("rel-id_" + item);
        if (el !== null) resultado["id_" + chave] = $(el);
    });
    return resultado;
}

function relObterElementosValor(elementos, chaves) {
    let resultado = {};
    chaves.forEach((chave) => {
        resultado[chave] = $(elementos[chave]).val();
        resultado["id_" + chave] = $(elementos["id_" + chave]).val();
    });
    return resultado;
}

function limitar(el, zero) {
    let minimo = 1;
    if (zero !== undefined) minimo = 0;
    let texto = apenasNumeros($(el).val().toString());
    $(el).val(texto);
    if (!texto.length || parseInt(texto) < minimo) $(el).val(minimo);
    if (texto.length > 11) $(el).val("".padStart(11, "9"));
}

function numerico(el) {
    $(el).val(apenasNumeros($(el).val()).substring(0, 4));
}

function foto_pessoa(seletor, caminho) {
    if (caminho) caminho = URL + "/storage/" + caminho;
    $(seletor).css("background-image", caminho ? "url('" + caminho + "')" : "");
    $($($(seletor).children()[0])).removeClass("d-none");
    if (caminho) {
        $(seletor).css("background-size", "100% 100%");
        $($($(seletor).children()[0])).addClass("d-none");
   }
}

function formatar_cpf(el) {
    $(el).removeClass("invalido");
    let cpf = $(el).val();
    let num = cpf.replace(/[^\d]/g, '');
    let len = num.length;
    if (len <= 6) cpf = num.replace(/(\d{3})(\d{1,3})/g, '$1.$2');
    else if (len <= 9) cpf = num.replace(/(\d{3})(\d{3})(\d{1,3})/g, '$1.$2.$3');
    else {
        cpf = num.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/g, "$1.$2.$3-$4");
        cpf = cpf.substring(0, 14);
    }
    $(el).val(cpf);
}

function validar_cpf(__cpf) {
    cpf = apenasNumeros(cpf);
    if (cpf == "00000000000") return false;
    if (cpf.length != 11) return false;
    let soma = 0;
    for (let i = 1; i <= 9; i++) soma = soma + (parseInt(cpf.substring(i - 1, i)) * (11 - i));
    let resto = (soma * 10) % 11;
    if ((resto == 10) || (resto == 11)) resto = 0;
    if (resto != parseInt(cpf.substring(9, 10))) return false;
    soma = 0;
    for (i = 1; i <= 10; i++) soma = soma + (parseInt(cpf.substring(i - 1, i)) * (12 - i));
    resto = (soma * 10) % 11;
    if ((resto == 10) || (resto == 11)) resto = 0;
    if (resto != parseInt(cpf.substring(10, 11))) return false;
    return true;
}

function trocarEmpresa() {
    $.post(URL + "/colaboradores/alterar-empresa", {
        _token : $("meta[name='csrf-token']").attr("content"),
        idEmpresa : $("#empresa-select").val()
    }, function() {
        location.reload();
    });
}

function trocarEmpresaModal() {
    $.get(URL + "/empresas/todas", function(data) {
        data = $.parseJSON(data);
        let resultado = "<option value = '0'>Todas</option>";
        data.forEach((empresa) => {
            resultado += "<option value = '" + empresa.id + "'>" + empresa.nome_fantasia + "</option>";
            empresa.filiais.forEach((filial) => {
                resultado += "<option value = '" + filial.id + "'>- " + filial.nome_fantasia + "</option>";
            });
        })
        $("#empresa-select").html(resultado);
        $("#empresa-select option[value='" + EMPRESA + "']").attr("selected", true);
        $("#trocarEmpresaModal").modal();
    });
}

function cp_mp_listeners(tipo) {
    $((tipo == "mp" ? "#mpModal .id-maquina" : "#cpModal .id-produto") + ", #" + tipo + "Modal .minimo, #" + tipo + "Modal .maximo, #" + tipo + "Modal .preco, #" + tipo + "Modal .lixeira").each(function() {
        $(this).off("change").on("change", function() {
            const linha = $($($(this).parent()).parent())[0];
            if ($(this).val().trim()) {
                $.get(URL + "/maquinas/produto/verificar-novo", {
                    preco : parseInt(apenasNumeros($($(linha).find(".preco")[0]).val())) / 100,
                    minimo : $($(linha).find(".minimo")[0]).val(),
                    maximo : $($(linha).find(".maximo")[0]).val(),
                    lixeira : $($(linha).find(".lixeira")[0]).val().replace("opt-", ""),
                    id_produto : tipo == "mp" ? $("#id_produto").val() : $($(linha).find(".id-produto")[0]).val(),
                    id_maquina : tipo == "mp" ? $($(linha).find(".id-maquina")[0]).val() : $($(".id_maquina")[0]).val()
                }, function(novo) {
                    const el = $($(linha).find(".form-search")[0]);
                    if (parseInt(novo)) $(el).addClass("new").removeClass("old");
                    else $(el).addClass("old").removeClass("new");
                });
            } else $($(linha).find(".form-search")[0]).addClass("new").removeClass("old");
            if ($(this).hasClass(tipo == "cp" ? "id-produto" : "id-maquina")) atualizaPreco(apenasNumeros($(this).attr("id")), tipo);
            if ($(this).hasClass("maximo") || $(this).hasClass("minimo")) limitar($(this), true);
        });
    }).off("keyup").on("keyup", function() {
        if ($(this).hasClass("maximo") || $(this).hasClass("minimo")) limitar($(this), true);
    });
}

function cp_mp_limpar_tudo(tipo) {
    cp_mp_limpar(tipo);
    const lista = tipo == "cp" ? ["busca-prod", "busca-refer", "busca-cat"] : ["busca-maq"];
    lista.forEach((id) => {
        $("#" + id).val("");
    });
}

function cp_mp_limpar(tipo) {
    $("#" + tipo + "Modal .remove-linha").each(function() {
        $(this).trigger("click");
    });
    $(tipo == "cp" ? "#cpModal #produto-1" : "#mpModal #maquina-1").val("");
    $(tipo == "cp" ? "#cpModal #id_produto-1" : "#mpModal #id_maquina-1").val("");
    $("#" + tipo + "Modal #lixeira-1").val("opt-0");
    $("#" + tipo + "Modal #preco-1").val(0).trigger("keyup");
    $("#" + tipo + "Modal #minimo-1").val(0).trigger("keyup");
    $("#" + tipo + "Modal #maximo-1").val(0).trigger("keyup");
}

function cp_mp_adicionar_campo(tipo) {
    const cont = ($("#" + tipo + "Modal input[type=number]").length / 2) + 1;

    let linha = $($("#" + tipo + "Modal #template-linha").html());

    if (tipo == "cp") {
        $($(linha).find(".produto")[0]).attr("id", "produto-" + cont).attr("data-input", "#id_produto-" + cont);
        $($(linha).find(".id-produto")[0]).attr("id", "id_produto-" + cont);
    } else {
        $($(linha).find(".maquina")[0]).attr("id", "maquina-" + cont).attr("data-input", "#id_maquina-" + cont);
        $($(linha).find(".id-maquina")[0]).attr("id", "id_maquina-" + cont);
    }

    $($(linha).find(".lixeira")[0]).attr("id", "lixeira-" + cont).html($("#lixeira-1").html());
    $($(linha).find(".preco")[0]).attr("id", "preco-" + cont);
    $($(linha).find(".minimo")[0]).attr("id", "minimo-" + cont);
    $($(linha).find(".maximo")[0]).attr("id", "maximo-" + cont);

    $($(linha).find(".remove-linha")[0]).on("click", function() {
        $(linha).remove();
        let classes = ["lixeira", "minimo", "maximo", "preco"];
        if (tipo == "cp") classes.push("produto", "id_produto");
        else classes.push("maquina", "id_maquina");
        classes.forEach((classe) => {
            $("#" + tipo + "Modal ." + classe).each(function(i) {
                $(this).attr("id", classe + "-" + (i + 1));
            });
        });
    });

    $("#" + tipo + "Modal .modal-tudo").append($(linha));

    cp_mp_listeners(tipo);
    carrega_autocomplete();
    carrega_dinheiro();

    $(".form-control").keydown(function() {
        $(this).removeClass("invalido");
    });

    $($(linha).find(tipo == "cp" ? ".id-produto" : ".id-maquina")[0]).trigger("change");
    $($(linha).find(".minimo")[0]).trigger("change");
    $($(linha).find(".maximo")[0]).trigger("change");
}

function cp_mp_listar(tipo, abrir) {
    cp_mp_limpar(tipo);
    $.get(URL + "/" + (tipo == "mp" ? "produtos/maquina" : "maquinas") + "/listar", tipo == "cp" ? {
        id_maquina : $($(".id_maquina")[0]).val(),
        filtro : $("#busca-prod").val(),
        filtro_ref : $("#busca-ref").val(),
        filtro_cat : $("#busca-cat").val()
    } : {
        id_produto : $("#id_produto").val(),
        filtro : $("#busca-maq").val()
    }, function(data) {
        cp_mp_limpar(tipo);
        if (typeof data == "string") data = $.parseJSON(data);
        const total = data.total;
        let titulo = $("#" + tipo + "ModalLabel").html();
        if (titulo.indexOf("|") > -1) titulo = titulo.split("|")[0].trim();
        titulo += " | Listando " + data.lista.length + " de " + total;
        $("#" + tipo + "ModalLabel").html(titulo);
        data = data.lista;
        for (let i = 0; i < data.length; i++) {
            if (i > 0) cp_mp_adicionar_campo(tipo);
            $((tipo == "cp" ? "#cpModal #produto-" : "#mpModal #maquina-") + (i + 1)).val(data[i][tipo == "cp" ? "produto" : "maquina"]);
            $((tipo == "cp" ? "#cpModal #id_produto-" : "#mpModal #id_maquina-") + (i + 1)).val(data[i][tipo == "cp" ? "id_produto" : "id_maquina"]).trigger("change");
            $("#" + tipo + "Modal #lixeira-" + (i + 1)).val("opt-" + data[i].lixeira);
            $("#" + tipo + "Modal #preco-" + (i + 1)).val(data[i].preco).trigger("keyup");
            $("#" + tipo + "Modal #minimo-" + (i + 1)).val(parseInt(data[i].minimo)).trigger("keyup");
            $("#" + tipo + "Modal #maximo-" + (i + 1)).val(parseInt(data[i].maximo)).trigger("keyup");
        }
        if (abrir) {
            if (!validacao_bloqueada) $("#" + tipo + "Modal").modal();
            $(tipo == "cp" ? "#cpModal .id-produto" : "#mpModal .id-maquina").each(function() {
                $(this).trigger("change");
            });
            $("#" + tipo + "Modal .minimo, #" + tipo + "Modal .maximo").each(function() {
                limitar($(this), true);
            });
        }
    })
}

function Atribuicoes(grade, _psm_valor) {
    let idatb = 0;
    let hab = true;
    const that = this;

    this.obter_psm = function() {
        if (location.href.indexOf("colaboradores") > -1) return "P";
        if (location.href.indexOf("maquinas") > -1) return "M";
        return "S";
    }

    this.psm_val = _psm_valor;

    const mostrar = function(_id) {
        if (_id === undefined) _id = 0;
        idatb = _id;
        const _tipo2 = that.obter_psm();
        $.get(URL + "/atribuicoes/listar", {
            id : _psm_valor,
            tipo : grade ? "R" : "P",
            tipo2 : _tipo2
        }, function(data) {
            let resultado = "";
            if (typeof data == "string") data = $.parseJSON(data);
            if (data.length) {
                resultado += "<thead>" +
                    "<tr>" +
                        "<th>" + (grade ? "Referência" : "Produto") + "</th>" +
                        "<th>Obrigatório?</th>" +
                        "<th class = 'text-right'>Qtde.</th>" +
                        "<th class = 'text-right'>Validade</th>" +
                        "<th>&nbsp;</th>" +
                    "</tr>" +
                "</thead>" +
                "<tbody>";
                data.forEach((atribuicao) => {
                    let acoes = "";
                    if (grade) acoes += "<i class = 'my-icon far fa-eye' title = 'Detalhar' onclick = 'atribuicao.detalhar(" + atribuicao.id + ")'></i>";
                    if (_tipo2 == "P") acoes += "<i class = 'my-icon far fa-hand-holding-box' title = 'Retirar' onclick = 'atribuicao.retirar(" + atribuicao.id + ")'></i>";
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
                if (that.obter_psm() == "M") $($("#atribuicoesModal div.atribuicoes").parent()).addClass("mb-5");
                else $($("#atribuicoesModal div.atribuicoes").parent()).removeClass("mb-5");
                hab = true;
            } else {
                $($("#table-atribuicoes").parent()).removeClass("pb-4");
                $($("#atribuicoesModal div.atribuicoes").parent()).removeClass("mb-5");
            }
            $("#table-atribuicoes").html(resultado);
            $("#referencia").attr("disabled", false);
            $("#produto").attr("disabled", false);
            if (document.querySelector("#atribuicoesModal .linha-atb.new") !== null) $("#col-btn-salvar").removeClass("d-none").addClass("d-flex");
            else $("#col-btn-salvar").addClass("d-none").removeClass("d-flex");
        });
    }

    const retirarMain = async function(id, _supervisor) {
        if (_supervisor === undefined) _supervisor = 0;
        await $.post(URL + "/retiradas/salvar", {
            _token : $("meta[name='csrf-token']").attr("content"),
            supervisor : _supervisor,
            atribuicao : id,
            pessoa : _psm_valor,
            produto : $("#variacao").val().replace("prod-", ""),
            data : $("#data-ret").val(),
            quantidade : $("#quantidade2").val()
        });
        $("#supervisorModal").modal("hide");
        $("#retiradasModal").modal("hide");
        await s_alert({icon : "success"});
        listar();
    }

    this.salvar = function() {
        if (hab) {
            hab = false;
            $.post(URL + "/atribuicoes/salvar", {
                _token : $("meta[name='csrf-token']").attr("content"),
                id : idatb,
                psm_chave : that.obter_psm(),
                psm_valor : _psm_valor,
                pr_chave : grade ? "R" : "P",
                pr_valor : $("#" + (grade ? "referencia" : "produto")).val(),
                validade : $("#validade").val(),
                qtd : $("#quantidade").val(),
                obrigatorio : $("#obrigatorio").val().replace("opt-", "")
            }, function(ret) {
                ret = parseInt(ret);
                if (ret != 201) hab = true;
                switch(ret) {
                    case 201:
                        $("#id_produto").val("");
                        $("#referencia").val("");
                        $("#produto").val("");
                        $("#quantidade").val(1);
                        $("#validade").val(1);
                        $("#obrigatorio").val("opt-0");
                        mostrar();
                        break;
                    case 403:
                        s_alert(grade ? "Referência inválida" : "Produto inválido");
                        break;
                    case 404:
                        s_alert(grade ? "Referência não encontrada" : "Produto não encontrado");
                        break;
                }
            });
        }
    }

    this.editar = function(id) {
        if (idatb != id) {
            const campo = grade ? "referencia" : "produto";
            $.get(URL + "/atribuicoes/mostrar/" + id, function(data) {
                $("#estiloAux").html(".autocomplete-result{display:none}");
                $("#" + campo + ", #validade, #quantidade, #obrigatorio").each(function() {
                    $(this).attr("disabled", true);
                });
                if (typeof data == "string") data = $.parseJSON(data);
                $("#" + campo).val(data.descr).trigger("keyup");
                setTimeout(function() {
                    $($(".autocomplete-line").first()).trigger("click");
                }, 500);
                setTimeout(function() {
                    $("#validade").val(data.validade);
                    $("#quantidade").val(parseInt(data.qtd));
                    $("#obrigatorio").val("opt-" + data.obrigatorio);
                    $("#estiloAux").html("");
                    $("#validade, #quantidade, #obrigatorio").each(function() {
                        $(this).attr("disabled", false);
                    });
                    mostrar(id);
                }, 1000);
            });
        }
    }

    this.excluir = function(id) {
        if (hab) {
            hab = false;
            let aviso = "Tem certeza que deseja excluir ess";
            aviso += grade ? "a referência?" : "e produto?";
            excluirMain(id, "/atribuicoes", aviso, function() {
                mostrar();
            });
        }
    }

    this.tentar = function(e) {
        if (e.keyCode == 13) that.salvar();
    }

    this.detalhar = function(id) {
        $.get(URL + "/atribuicoes/grade/" + id, function(data) {
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

    this.preencherValidade = function(id_produto) {
        $.get(URL + "/produtos/validade", {
            id : id_produto,
            tipo : grade ? "R" : "P"
        }, function(validade) {
            $("#validade").val(parseInt(validade)).trigger("change");
        })
    }

    this.atualizarQtd = function() {
        $("#quantidade2_label").html($("#quantidade2").val());
    }

    this.retirar = function(id) {
        $("#quantidade2").val(1);
        that.atualizarQtd();
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
            $("#btn-retirada").off("click").on("click", function() {
                let erro = "";
                
                if (!$("#data-ret").val()) erro = "Preencha o campo";
                else if (eFuturo($("#data-ret").val())) erro = "A retirada não pode ser no futuro";
                
                if (!erro) {
                    $.get(URL + "/retiradas/consultar", {
                        atribuicao : id,
                        qtd : $("#quantidade2").val(),
                        pessoa : _psm_valor
                    }, function(ok) {
                        if (!parseInt(ok)) {
                            idatb = id;
                            modal2("supervisorModal", ["cpf2", "senha2"]);
                        } else retirarMain(id);
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
            that.atualizarQtd();
            $("#data-ret").val("");
            $("#retiradasModal").modal();
        });
    }

    this.validarSpv = function() {
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
                cpf : apenasNumeros($("#cpf2").val()),
                senha : $("#senha2").val()
            }, function(ok) {
                if (parseInt(ok)) retirarMain(idatb, ok);
                else s_alert("Supervisor inválido");
            });
        } else s_alert(erro);
    }

    this.recalcular = function() {
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
            _token : $("meta[name='csrf-token']").attr("content")
        }, function(data) {
            lista.forEach((el) => {
                el.style.removeProperty("z-index");
            });
            modal1.removeProperty("z-index");
            modal2.removeProperty("z-index");
            loader.removeProperty("display");
            mostrar();
        });
    }

    this.pergunta_salvar = async function() {
        const resp = await s_alert({
            html : "Deseja salvar as alterações?",
            ync : true
        });
        if (resp.isDenied) {
            await $.post(URL + "/atribuicoes/descartar", {
                _token : $("meta[name='csrf-token']").attr("content")
            });
        } else if (resp.isConfirmed) that.recalcular();
        else $("#atribuicoesModal").modal();
    }
    
    $($("#table-atribuicoes").parent()).removeClass("pb-4");
    $("#table-atribuicoes").html("");
    modal("atribuicoesModal", 0, function() {
        const _psm_chave = that.obter_psm();
        $.get(URL + "/" + (
            _psm_chave == "P" ? 
                "colaboradores" :
            _psm_chave == "S" ? 
                "setores" : 
            "maquinas"
        ) + "/mostrar/" + _psm_valor, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            $("#atribuicoesModalLabel").html(data[_psm_chave == "P" ? "nome" : "descr"].toUpperCase() + " - Atribuindo " + (grade ? "grades" : "produtos"));
            if (grade) {
                $("#referencia").attr("data-filter", _psm_valor);
                $("#div-produto").addClass("d-none");
                $("#div-referencia").removeClass("d-none");
            } else {
                $("#div-produto").removeClass("d-none");
                $("#div-referencia").addClass("d-none");
            }
            $("#obrigatorio").val("opt-0");
            mostrar();
            $("#atribuicoesModal input[type=number]").each(function() {
                $(this).off("change").on("change", function() {
                    limitar($(this));
                }).off("keyup").on("keyup", function(e) {
                    $(this).trigger("change");
                    atribuicao.tentar(e);
                }).trigger("change");
            })
        });
    });
}

function Excecoes(_id_atribuicao) {
    let hab = true;
    let idexc = 0;

    const mostrar = function(_id) {
        if (_id === undefined) _id = 0;
        idexc = _id;
        $.get(URL + "/atribuicoes/excecoes/listar/" + _id_atribuicao, function(data) {
            let resultado = "";
            if (typeof data == "string") data = $.parseJSON(data);
            let pessoa = false;
            let setor = false;
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
                    if (excecao.ps_chave == "P") pessoa = true;
                    if (excecao.ps_chave == "S") setor = true;
                    resultado += "<tr>" +
                        "<td class = 'exc-tipo'>" + (excecao.ps_chave == "P" ? "FUNCIONÁRIO" : "CENTRO DE CUSTO") + "</td>" +
                        "<td>" +
                            "<span class = 'linha-atb " + (excecao.rascunho == "S" ? "old" : "new") + "'>" + excecao.pr_valor + "</span>" +
                        "</td>" +
                        "<td class = 'text-center manter-junto'>" + (
                            parseInt(excecao.pode_editar) ? "<i class = 'my-icon far fa-edit' title = 'Editar' onclick = 'excecao.editar(" + excecao.id + ")'></i>" +
                                "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excecao.excluir(" + excecao.id + ")'></i>" 
                            : 
                                "---"
                        ) + "</td>" +
                    "</tr>";
                });
                resultado += "</tbody>";
                $($("#table-excecoes").parent()).addClass("pb-4");
                hab = true;
            } else $($("#table-excecoes").parent()).removeClass("pb-4");
            $("#table-excecoes").html(resultado);
            if (data.length) {
                let titulo = "";
                if (pessoa) titulo += "Nome";
                if (setor) {
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

    this.salvar = function() {
        if (hab) {
            hab = false;
            $.post(URL + "/atribuicoes/excecoes/salvar", {
                _token : $("meta[name='csrf-token']").attr("content"),
                ps_chave : $("#exc-ps-chave").val(),
                ps_valor : $("#exc-ps-valor").val(),
                ps_id : $("#exc-ps-id").val(),
                id_atribuicao : _id_atribuicao
            }, function(ret) {
                ret = parseInt(ret);
                if (ret != 201) hab = true;
                switch(ret) {
                    case 201:
                        $("#exc-ps-chave").val("P");
                        $("#exc-ps-valor").val("");
                        $("#exc-ps-id").val("");
                        mostrar();
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

    this.editar = function(id) {
        if (idatb != id) {
            $.get(URL + "/atribuicoes/excecoes/mostrar/" + id, function(data) {
                $("#estiloAux").html(".autocomplete-result{display:none}");
                $("#exc-ps-chave, #exc-ps-valor").each(function() {
                    $(this).attr("disabled", true);
                });
                if (typeof data == "string") data = $.parseJSON(data);
                $("#exc-ps-valor").val(data.descr).trigger("keyup");
                setTimeout(function() {
                    $($(".autocomplete-line").first()).trigger("click");
                }, 500);
                setTimeout(function() {
                    $("#exc-ps-chave").val(data.ps_chave == "P" ? "Funcionário" : "Centro de custo");
                    $("#exc-ps-valor").val(data.ps_valor);
                    $("#exc-ps-id").val(data.ps_id);
                    $("#exc-ps-chave, #exc-ps-valor").each(function() {
                        $(this).attr("disabled", false);
                    });
                    mostrar(idexc);
                }, 1000);
            });
        }
    }

    this.excluir = function(id) {
        if (hab) {
            hab = false;
            excluirMain(id, "/atribuicoes/excecoes", "Tem certeza que deseja excluir essa exceção?", function() {
                mostrar();
            });
        }
    }

    this.mudarTipo = function(chave) {
        $("#lbl-exc-ps-valor").html((chave == "P" ? "Nome" : "Descrição") + ": *");
        $("#exc-ps-valor").attr("data-table", chave == "P" ? "pessoas" : "setores");
        $("#exc-ps-valor").attr("data-column", chave == "P" ? "nome" : "descr");
        $("#exc-ps-valor").attr("data-filter", atribuicao.psm_val);
        $("#exc-ps-valor").attr("data-filter_col", atribuicao.obter_psm() == "M" ? "v_maquina" : "id_setor");
        $("#exc-atalho").attr("data-atalho", chave == "P" ? "pessoas" : "setores");
        carrega_atalhos();
    }
    
    modal("excecoesModal", 0, function() {
        setTimeout(function() {
            $("#exc-ps-chave").val("P").trigger("change");
            if (atribuicao.obter_psm() == "S") {
                $($("#exp-ps-chave").parent()).addClass("d-none");
                $($("#exp-ps-valor").parent()).removeClass("col-7").addClass("col-11");
            } else {
                $($("#exp-ps-chave").parent()).removeClass("d-none");
                $($("#exp-ps-valor").parent()).removeClass("col-11").addClass("col-7");
            }
            mostrar();
        }, 0);
    });
}

function RelatorioBilateral(_grupo) {
    let that = this;
    let grupo = _grupo;

    this.validar = function() {
        limpar_invalido();
        let elementos = relObterElementos(["empresa1", "maquina1"]);
        let valores = relObterElementosValor(elementos, ["empresa", "maquina"]);
        valores.prioridade = grupo == "maquinas-por-empresa" ? "empresas" : "maquinas";
        $("#rel-prioridade").val(valores.prioridade);
        $.get(URL + "/relatorios/bilateral/consultar", valores, function(erro) {
            if (erro) {
                $(elementos[erro]).addClass("invalido");
                erro = erro == "empresa" ? "Empresa" : "Máquina";
                erro += " não encontrada";
                s_alert(erro);
            } else $("#relatorioBilateralModal form").submit();
        });
    }

    this.inverter = function() {
        const arr = [1, 0];
        let wrapper = document.querySelectorAll("#relatorioBilateralModal .container");
        let items = wrapper[0].children;
        let elements = document.createDocumentFragment();
        arr.forEach(function(idx) {
        	elements.appendChild(items[idx].cloneNode(true));
        });
        wrapper[0].innerHTML = null;
        wrapper[0].appendChild(elements);
        Array.from(document.querySelectorAll(".modal-body .row:not(.sem-margem)")).forEach((el) => {
            el.style.removeProperty("margin-top");
            if ($(el).prev().hasClass("row")) $(el).css("margin-top", $(el).prev().find(".tam-max").length ? "-14px" : "11px");
        });
    }

    let titulo = "Empresas por máquina";
    if (grupo == "maquinas-por-empresa") {
        that.inverter();
        titulo = "Máquinas por empresa";
    }
    $("#relatorioBilateralModalLabel").html(titulo);
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioBilateralModal", 0, function() {
            $("#rel-grupo1").val(grupo);
        });
    }, 0);
}

function RelatorioItens(tipo, maquina) {
    const resumido = tipo == "S";
    let elementos = relObterElementos(["inicio1", "fim1", "produto", "maquina2"]);
    
    this.validar = function() {
        limpar_invalido();
        if (tipo == "P") {
            $(elementos.inicio).val("01/01/2000");
            $(elementos.fim).val("01/01/3000");
        }

        let erro = "";
        if (resumido && (!($(elementos.inicio).val() && $(elementos.fim).val())) && $("#rel-tipo").val() == "G") {
            if (!$(elementos.inicio).val()) $(elementos.inicio).addClass("invalido");
            if (!$(elementos.fim).val()) $(elementos.fim).addClass("invalido");
            s_alert("Preencha as datas");
            return;
        }

        if ($(elementos.inicio).val() && $(elementos.fim).val()) erro = validar_datas($(elementos.inicio), $(elementos.fim), false);
        let req = ["produto"];
        if (maquina === undefined) req.push("maquina");
        req = relObterElementosValor(elementos, req);
        req.inicio = $(elementos.inicio).val();
        req.fim = $(elementos.fim).val();
        if (maquina !== undefined) req.id_maquina = maquina;
        $.get(URL + "/relatorios/extrato/consultar", req, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            if (data.el && !erro) {
                const lista = data.el.split(",");
                lista.forEach((el) => {
                    $(elementos[el]).addClass("invalido");
                    $(elementos[el]).val(data[el + "_correto"]);
                });
                if (["maquina", "produto"].indexOf(data.el) == -1) {
                    if (tipo != "P") {
                        if (lista.length > 1) {
                            erro = "As datas foram corrigidas";
                            if (data.varias_maquinas == "N") erro += " para o início e o fim programado para a locação desta máquina";
                        } else erro = "A locação " + (data.varias_maquinas == "S" ? "desta máquina" : data.el == "inicio" ? "mais antiga" : "mais recente") + (data.el == "inicio" ? " foi iniciada em " + data.inicio_correto : " tem seu término programado para " + data.fim_correto) + ", sendo essa a " + (data.el == "inicio" ? "menor" : "maior") + " data possível para pesquisar.<br>A data foi corrigida";
                        erro += ".<br>Tente novamente.";
                    }
                } else erro = erro == "maquina" ? "Máquina não encontrada" : "Produto não encontrado";
            }
            if (!erro) $("#relatorioItensModal form").submit();
            else s_alert(erro);
        });
    }

    this.mudaTipo = function() {
        let elDias = $("#rel-dias");
        const giro = $("#rel-tipo2").val() == "G";
        $(elDias).attr("disabled", !giro);
        if (giro) $(elDias).focus();
        else $(elementos.maquina).focus();
    }
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioItensModal", 0, function() {
            $("#rel-lm").val("N");
            $(elementos.inicio).val(hoje());
            $(elementos.fim).val(hoje());
            $("#rel-id_maquina2").val(maquina !== undefined ? maquina : 0);
            $("#relatorioItensModalLabel").html(resumido ? maquina === undefined ? "Sugestão de compra" : "Solicitação de compra" : tipo == "E" ? "Extrato de itens" : "Posição de estoque");
            $("#resumo").val(resumido ? "S" : "N");
            $("#rel-lm-chk").prop("checked", tipo == "E");
            $("#rel-lm-chk").trigger("change");
            let pai = $($($($("#rel-lm-chk").parent()).parent()).parent());
            if (!resumido) $(pai).addClass("d-none");
            else $(pai).removeClass("d-none");
            $("label[for='rel-lm-chk']").html(resumido ? "Listar apenas produtos cuja compra é sugerida" : "Listar movimentação");
            $("#relatorioItensModal form").attr("action", resumido ? maquina === undefined ? URL + "/relatorios/sugestao" : URL + "/solicitacoes" : URL + "/relatorios/extrato");
            let el_maq = $($($("#rel-maquina2").parent()).parent());
            if (maquina !== undefined) $(el_maq).addClass("d-none");
            else $(el_maq).removeClass("d-none");
            if (resumido) $("#rel-modo-resumo").removeClass("d-none");
            else $("#rel-modo-resumo").addClass("d-none");
            if (tipo == "P") $("#rel-datas").addClass("d-none");
            else $("#rel-datas").removeClass("d-none");
        });
    }, 0);
}

function RelatorioControle() {
    let elementos = relObterElementos(["inicio2", "fim2", "pessoa1", "consumo1"]);

    this.validar = function() {
        limpar_invalido();
        let erro = "";
        if ($(elementos.inicio).val() && $(elementos.fim).val()) erro = validar_datas($(elementos.inicio), $(elementos.fim), false);
        $.get(URL + "/relatorios/controle/consultar", relObterElementosValor(elementos, ["pessoa"]), function(data) {
            if (data && !erro) {
                $(elementos.pessoa).addClass("invalido");
                erro = "Colaborador não encontrado";
            }
            if (!erro) {
                /*if (!$(elementos.id_pessoa).val().trim()) {
                    $.get(URL + "/relatorios/controle/pessoas", function(data2) {
                        if (typeof data2 == "string") data2 = $.parseJSON(data2);
                        controleTodos(data2);
                    });
                } else*/ $("#relatorioControleModal form").submit();
            } else s_alert(erro);
        });
    }
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioControleModal", 0, function() {
            $(elementos.inicio).val(hoje());
            $(elementos.fim).val(hoje());
            $(elementos.consumo).val("todos");
       });
    }, 0);
}

function RelatorioRetiradas(quebra) {
    let elementos = relObterElementos(["inicio3", "fim3", "empresa2", "pessoa2", "setor", "consumo2", "tipo"]);

    this.atualizarTabela = function(tipo) {
        let tabela = "pessoas";
        switch(tipo) {
            case "inativos":
                tabela += "_lixeira";
                break;
            case "todos":
                tabela += "_todos";
                break;
        }

        $("#rel-pessoa2").val("");
        $("#rel-pessoa2").attr("data-table", tabela);
        $("#rel-id_pessoa2").val("");
    }

    this.validar = function() {
        limpar_invalido();
        let erro = "";
        if ($(elementos.inicio).val() && $(elementos.fim).val()) erro = validar_datas($(elementos.inicio), $(elementos.fim), false);
        $.get(
            URL + "/relatorios/retiradas/consultar",
            relObterElementosValor(elementos, ["empresa", "pessoa", "setor"]),
            function(data) {
                if (data && !erro) {
                    $(elementos[data]).addClass("invalido");
                    erro = data != "maquina" ? "Centro de custo" : "Máquina";
                    erro += " não encontrad";
                    erro += data == "setor" ? "o" : "a";
                }
                if (!erro) $("#relatorioRetiradasModal form").submit();
                else s_alert(erro);
            }
        );
    }
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioRetiradasModal", 0, function() {
            $("#rel-pessoa-tipo, #rel-consumo2, #rel-tipo").each(function() {
                let el = $($(this).parent());
                $(el).addClass(quebra == "pessoa" ? "col-4" : "col-6");
                $(el).removeClass(quebra == "pessoa" ? "col-6" : "col-4");
            });
            $(elementos.inicio).val(hoje());
            $(elementos.fim).val(hoje());
            if (quebra == "setor") {
                $($(elementos.pessoa).parent()).addClass("d-none");
                $($(elementos.setor).parent()).removeClass("d-none");
                $($("#rel-pessoa-tipo").parent()).addClass("d-none");
            } else {
                $($(elementos.setor).parent()).addClass("d-none");
                $($(elementos.pessoa).parent()).removeClass("d-none");
                $($("#rel-pessoa-tipo").parent()).removeClass("d-none");
            }
            $("#rel-pessoa-tipo").val("todos");
            $(elementos.consumo).val("todos");
            $(elementos.tipo).val("A");
            let titulo = "Consumo por ";
            titulo += quebra == "pessoa" ? "colaborador" : "centro de custo";
            $("#relatorioRetiradasModalLabel").html(titulo);
            $("#rel-grupo2").val(quebra);
        });
    }, 0);
}

function RelatorioRanking() {
    let elementos = relObterElementos(["inicio4", "fim4"]);

    this.validar = function() {
        limpar_invalido();
        let erro = "";
        if ($(elementos.inicio).val() && $(elementos.fim).val()) erro = validar_datas($(elementos.inicio), $(elementos.fim), false);
        if (!erro) $("#relatorioRankingModal form").submit();
        else s_alert(erro);
    }
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioRankingModal", 0, function() {
            $(elementos.inicio).val(hoje());
            $(elementos.fim).val(hoje());
            $("#rel-tipo3").val("todos");
        });
    }, 0);
}