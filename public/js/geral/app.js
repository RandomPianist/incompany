let relatorio, pessoa, pessoa_atribuindo, gradeGlobal, idatbglobal, colGlobal;
let anteriores = new Array();
let validacao_bloqueada = false;

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
    $(".modal-body .row").each(function() {
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
                    $(dp.dpDiv[0]).css("width", (elem.offsetWidth > 244 ? elem.offsetWidth : 244) + "px");
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
            let resultado = $(that).val().replace(/\D/g, "");
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

    $("#atribuicoesModal").on("hide.bs.modal", function() {
        idatbglobal = 0;
    });

    $("#estoqueModal").on("hide.bs.modal", function() {
        $(".remove-produto").each(function() {
            $(this).trigger("click");
        });
        $("#produto-1").val("");
        $("#id_produto-1").val("");
        $("#es-1").val("E");
        $("#qtd-1").val(1);
        $("#obs-1").val("ENTRADA");
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
            do {
                var el = $($("#" + that.id + " input[type=text]")[cont]);
                el.focus();
                cont++;
            } while ($($(el).parent()).hasClass("d-none") || $(el).attr("disabled"))
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
    
    listar(location.href.indexOf("produtos") > -1 ? 1 : 0);

    avisarSolicitacao();
});

function ordenar(coluna) {
    if (coluna === undefined) {
        coluna = colGlobal;
        $($(".sortable-columns").children()[coluna]).addClass("nao-inverte");
    }
    $($(".sortable-columns").children()[coluna]).trigger("click");
}

function contar_char(el, max) {
    $(el).removeClass("invalido");
    $(el).val($(el).val().substring(0, max));
    $(el).next().html($(el).val().length + "/" + max);
}

function modal(nome, id, callback) {
    const concluir = function() {
        if (!id && ["pessoasModal", "setoresModal", "valoresModal"].indexOf(nome) > -1) {
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
            if (TIPO !== undefined) {
                if (TIPO != "A") $("#pessoa-empresa-select").val(primeiro);
            }
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
        div_result = $("<div class = 'autocomplete-result' style = 'width:" + document.getElementById($(element).attr("id")).offsetWidth + "px'>");
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

function verifica_vazios(arr, _erro) {
    if (_erro === undefined) _erro = "";
    let _alterou = false;
    arr.forEach((id) => {
        let el = $("#" + id);
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
        Array.from(document.querySelectorAll(".modal-body .row")).forEach((el) => {
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

function RelatorioItens(resumido, maquina) {
    let elementos = relObterElementos(["inicio1", "fim1", "produto", "maquina2"]);
    
    this.validar = function() {
        limpar_invalido();
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
                    if (lista.length > 1) {
                        erro = "As datas foram corrigidas";
                        if (data.varias_maquinas == "N") erro += " para o início e o fim programado para a locação desta máquina";
                    } else erro = "A locação " + (data.varias_maquinas == "S" ? "desta máquina" : data.el == "inicio" ? "mais antiga" : "mais recente") + (data.el == "inicio" ? " foi iniciada em " + data.inicio_correto : " tem seu término programado para " + data.fim_correto) + ", sendo essa a " + (data.el == "inicio" ? "menor" : "maior") + " data possível para pesquisar.<br>A data foi corrigida";
                    erro += ".<br>Tente novamente.";
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
            $("#relatorioItensModalLabel").html(resumido ? maquina === undefined ? "Sugestão de compra" : "Solicitação de compra" : "Extrato de itens");
            $("#resumo").val(resumido ? "S" : "N");
            $("#rel-lm-chk").prop("checked", false);
            $("label[for='rel-lm-chk']").html(resumido ? "Listar apenas produtos cuja compra é sugerida" : "Listar movimentação");
            $("#relatorioItensModal form").attr("action", resumido ? maquina === undefined ? URL + "/relatorios/sugestao" : URL + "/solicitacoes" : URL + "/relatorios/extrato");
            let el_maq = $($($("#rel-maquina2").parent()).parent());
            if (maquina !== undefined) $(el_maq).addClass("d-none");
            else $(el_maq).removeClass("d-none");
            if (resumido) $("#rel-modo-resumo").removeClass("d-none");
            else $("#rel-modo-resumo").addClass("d-none");
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
                if (!$(elementos.id_pessoa).val().trim()) {
                    $.get(URL + "/relatorios/controle/pessoas", function(data2) {
                        if (typeof data2 == "string") data2 = $.parseJSON(data2);
                        controleTodos(data2);
                    });
                } else $("#relatorioControleModal form").submit();
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
        $("#rel-pessoa2").data("table", tabela);
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

function limitar(el) {
    let texto = $(el).val().toString();
    if (!texto.length || parseInt(texto) < 1) $(el).val(1);
    if (texto.length > 11) $(el).val("".padStart(11, "9"));
}

function numerico(el) {
    $(el).val($(el).val().replace(/\D/g, "").substring(0, 4));
}

function mostrar_atribuicoes(_id) {
    if (_id === undefined) _id = 0;
    idatbglobal = _id;
    $.get(URL + "/atribuicoes/listar", {
        id : pessoa_atribuindo,
        tipo : gradeGlobal ? "R" : "P",
        tipo2 : location.href.indexOf("colaboradores") > -1 ? "P" : "S"
    }, function(data) {
        let resultado = "";
        if (typeof data == "string") data = $.parseJSON(data);
        if (data.length) {
            resultado += "<thead>" +
                "<tr>" +
                    "<th>" + (gradeGlobal ? "Referência" : "Produto") + "</th>" +
                    "<th>Obrigatório?</th>" +
                    "<th class = 'text-right'>Qtde.</th>" +
                    "<th class = 'text-right'>Validade</th>" +
                    "<th>&nbsp;</th>" +
                "</tr>" +
            "</thead>" +
            "<tbody>";
            data.forEach((atribuicao) => {
                let acoes = "";
                if (gradeGlobal) acoes += "<i class = 'my-icon far fa-eye' title = 'Detalhar' onclick = 'detalhar_atribuicao(" + atribuicao.id + ")'></i>";
                if (location.href.indexOf("colaboradores") > -1) acoes += "<i class = 'my-icon far fa-hand-holding-box' title = 'Retirar' onclick = 'retirar(" + atribuicao.id + ")'></i>";
                if (parseInt(atribuicao.pode_editar)) {
                    acoes += "<i class = 'my-icon far fa-edit' title = 'Editar' onclick = 'editar_atribuicao(" + atribuicao.id + ")'></i>" +
                        "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excluir_atribuicao(" + atribuicao.id + ")'></i>";
                }
                if (!acoes) acoes = "---";
                resultado += "<tr>" +
                    "<td>" + atribuicao.produto_ou_referencia_valor + "</td>" +
                    "<td>" + atribuicao.obrigatorio + "</td>" +
                    "<td class = 'text-right'>" + atribuicao.qtd + "</td>" +
                    "<td class = 'text-right'>" + atribuicao.validade + "</td>" +
                    "<td class = 'text-center manter-junto'>" + acoes + "</td>" +
                "</tr>";
            });
            resultado += "</tbody>";
            $($("#table-atribuicoes").parent()).addClass("pb-4");
        } else $($("#table-atribuicoes").parent()).removeClass("pb-4");
        $("#table-atribuicoes").html(resultado);
    });
}

function atribuicao(grade, id) {
    modal("atribuicoesModal", 0, function() {
        pessoa_atribuindo = id;
        $.get(URL + "/" + (location.href.indexOf("colaboradores") > -1 ? "colaboradores" : "setores") + "/mostrar/" + id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            $("#atribuicoesModalLabel").html(
                data[location.href.indexOf("colaboradores") > -1 ? "nome" : "descr"].toUpperCase() + " - Atribuindo " + (grade ? "grades" : "produtos")
            );
            if (grade) {
                $("#referencia").data().filter = id;
                $("#div-produto").addClass("d-none");
                $("#div-referencia").removeClass("d-none");
            } else {
                $("#div-produto").removeClass("d-none");
                $("#div-referencia").addClass("d-none");
            }
            $("#obrigatorio").val("opt-0");
            gradeGlobal = grade;
            mostrar_atribuicoes();
        });
    });
}

function atribuir() {
    const campo = gradeGlobal ? "R" : "P";
    $.post(URL + "/atribuicoes/salvar", {
        _token : $("meta[name='csrf-token']").attr("content"),
        id : idatbglobal,
        pessoa_ou_setor_chave : location.href.indexOf("colaboradores") > -1 ? "P" : "S",
        pessoa_ou_setor_valor : pessoa_atribuindo,
        produto_ou_referencia_chave : campo,
        produto_ou_referencia_valor : $("#" + (gradeGlobal ? "referencia" : "produto")).val(),
        validade : $("#validade").val(),
        qtd : $("#quantidade").val(),
        obrigatorio : $("#obrigatorio").val().replace("opt-", "")
    }, function(ret) {
        ret = parseInt(ret);
        switch(ret) {
            case 201:
                $("#id_produto").val("");
                $("#referencia").val("");
                $("#produto").val("");
                $("#quantidade").val(1);
                $("#validade").val(1);
                $("#obrigatorio").val("opt-0");
                mostrar_atribuicoes();
                break;
            case 403:
                s_alert(gradeGlobal ? "Referência inválida" : "Produto inválido");
                break;
            case 404:
                s_alert(gradeGlobal ? "Referência não encontrada" : "Produto não encontrado");
                break;
        }
    });
}

function editar_atribuicao(id) {
    if (idatbglobal != id) {
        const campo = gradeGlobal ? "referencia" : "produto";
        $.get(URL + "/atribuicoes/mostrar/" + id, function(data) {
            $("#estiloAux").html(".autocomplete-result{display:none}");
            $("#" + campo + ", #validade, #quantidade, #obrigatorio").each(function() {
                $(this).attr("disabled", true);
            });
            if (typeof data == "string") data = $.parseJSON(data);
            $("#" + campo).val(data.descr);
            $("#" + campo).trigger("keyup");
            setTimeout(function() {
                $($(".autocomplete-line").first()).trigger("click");
            }, 500);
            setTimeout(function() {
                $("#validade").val(data.validade);
                $("#quantidade").val(parseInt(data.qtd));
                $("#obrigatorio").val("opt-" + data.obrigatorio);
                $("#estiloAux").html("");
                $("#" + campo + ", #validade, #quantidade, #obrigatorio").each(function() {
                    $(this).attr("disabled", true);
                });
                mostrar_atribuicoes(id);
            }, 1000);
        });
    }
}

function excluir_atribuicao(_id) {
    let aviso = "Tem certeza que deseja excluir ess";
    aviso += gradeGlobal ? "a referência?" : "e produto?";
    excluirMain(_id, "/atribuicoes", aviso, function() {
        mostrar_atribuicoes();
    });
}

function tentarAtribuir(e) {
    if (e.keyCode == 13) atribuir();
}

function detalhar_atribuicao(id) {
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
    __cpf = __cpf.replace(/\D/g, "");
    if (__cpf == "00000000000") return false;
    if (__cpf.length != 11) return false;
    let soma = 0;
    for (let i = 1; i <= 9; i++) soma = soma + (parseInt(__cpf.substring(i - 1, i)) * (11 - i));
    let resto = (soma * 10) % 11;
    if ((resto == 10) || (resto == 11)) resto = 0;
    if (resto != parseInt(__cpf.substring(9, 10))) return false;
    soma = 0;
    for (i = 1; i <= 10; i++) soma = soma + (parseInt(__cpf.substring(i - 1, i)) * (12 - i));
    resto = (soma * 10) % 11;
    if ((resto == 10) || (resto == 11)) resto = 0;
    if (resto != parseInt(__cpf.substring(10, 11))) return false;
    return true;
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