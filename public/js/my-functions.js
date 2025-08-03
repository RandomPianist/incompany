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

window.onload = function() {
    Array.from(document.querySelectorAll(".modal-body .row")).forEach((el) => {
        if ($(el).prev().hasClass("row")) $(el).css("margin-top", $(el).prev().find(".tam-max").length ? "-14px" : "11px");
    });

    Array.from(document.querySelectorAll(".modal-body button")).forEach((el) => {
        el.parentElement.style.paddingTop = "1px";
    });

    let el_busca = document.getElementById("busca");
    if (el_busca !== null) {
        el_busca.onkeyup = function(e) {
            if (e.keyCode == 13) listar();
        }
    }

    $(document).on("keydown", "form", function(event) { 
        const enter = event.key == "Enter";
        if (enter && !validacao_bloqueada) {
            try {
                validar();
            } catch(err) {
                try {
                    validar_estoque();
                } catch(err) {
                    try {
                        validar_comodato();
                    } catch(err) {
                        try {
                            relatorio.validar();
                        } catch(err) {
                            pessoa.validar();
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
        $(this).datepicker({
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
                    let tamanho = elem.offsetWidth > 244 ? elem.offsetWidth : 244;
                    dp.dpDiv[0].style.width = tamanho + "px";
                }, 0);
            }
        });
        $(this).keyup(function() {
            let resultado = $(this).val().replace(/\D/g, "");
            if (resultado.length >= 8) {
                resultado = resultado.substring(0, 8);
                resultado = resultado.substring(0, 2) + "/" + resultado.substring(2, 4) + "/" + resultado.substring(4, 8);
                $(this).val(resultado);    
            }
        });
        $(this).blur(function() {
            let aux = $(this).val().split("/");
            data = new Date(parseInt(aux[2]), parseInt(aux[1]) - 1, parseInt(aux[0]));
            if (data.getFullYear() != aux[2] || data.getMonth() + 1 != aux[1] || data.getDate() != aux[0]) $(this).val("");
        });
    });

    $("#relatorioBilateralModal").on("hide.bs.modal", function() {
        if (document.getElementById("rel-grupo1").value == "maquinas-por-empresa") relatorio.inverter();
    });

    $("#atribuicoesModal").on("hide.bs.modal", function() {
        idatbglobal = 0;
    });

    $("#estoqueModal").on("hide.bs.modal", function() {
        Array.from(document.getElementsByClassName("remove-produto")).forEach((el) => {
            $(el).trigger("click");
        });
        document.getElementById("produto-1").value = "";
        document.getElementById("id_produto-1").value = "";
        document.getElementById("es-1").value = "E";
        document.getElementById("qtd-1").value = 1;
        document.getElementById("obs-1").value = "ENTRADA";
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

    Array.from(document.querySelectorAll(".user-pic .m-auto")).forEach((el) => {
        let conteudo = el.innerHTML;
        while (conteudo.indexOf("\n") > -1) conteudo = conteudo.replace("\n", "");
        while (conteudo.indexOf(" ") > -1) conteudo = conteudo.replace(" ", "");
        el.innerHTML = conteudo;
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
}

function ordenar(coluna) {
    if (coluna === undefined) {
        coluna = colGlobal;
        $($(".sortable-columns").children()[coluna]).addClass("nao-inverte");
    }
    $($(".sortable-columns").children()[coluna]).trigger("click");
}

function contar_char(el, max) {
    el.classList.remove("invalido");
    el.value = el.value.substring(0, max);
    el.nextElementSibling.innerHTML = el.value.length + "/" + max;
}

function dinheiro(texto_final) {
    texto_final = texto_final.replace(/\D/g, "");
    if (texto_final.length > 2) {
        let valor_inteiro = parseInt(texto_final.substring(0, texto_final.length - 2)).toString();
        let resultado_pontuado = "";
        let cont = 0;
        for (var i = valor_inteiro.length - 1; i >= 0; i--) {
            if (cont % 3 == 0 && cont > 0) resultado_pontuado = "." + resultado_pontuado;
            resultado_pontuado = valor_inteiro[i] + resultado_pontuado;
            cont++;
        }
        texto_final = resultado_pontuado + "," + texto_final.substring(texto_final.length - 2).padStart(2, "0");
    } else texto_final = "0," + texto_final.padStart(2, "0");
    texto_final = "R$ " + texto_final;
    return texto_final;
}

function modal(nome, id, callback) {
    const concluir = function() {
        $("#" + nome).modal();
        callback();
    }

    limpar_invalido();
    if (callback === undefined) callback = function() {}
    if (id) document.getElementById(nome == "pessoasModal" ? "pessoa-id" : "id").value = id;
    Array.from(document.querySelectorAll("#" + nome + " input, #" + nome + " textarea")).forEach((el) => {
        if (!id && el.name != "_token" && (!(nome == "pessoasModal" && el.name == "tipo"))) el.value = "";
        if (!$(el).hasClass("autocomplete")) $(el).trigger("keyup");
        anteriores[el.id] = el.value;
    });
    if (nome == "pessoasModal") {
        $.get(URL + "/colaboradores/modal", function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            let resultado = "<option value = '0'>--</option>";
            data.empresas.forEach((empresa) => {
                resultado += "<option value = '" + empresa.id + "'>" + empresa.nome_fantasia + "</option>";
                empresa.filiais.forEach((filial) => {
                    resultado += "<option value = '" + filial.id + "'>- " + filial.nome_fantasia + "</option>";
                });
            });
            document.getElementById("pessoa-empresa-select").innerHTML = resultado;
            resultado = "<option value = '0'>--</option>";
            data.setores.forEach((setor) => {
                resultado += "<option value = '" + setor.id + "'>" + setor.descr + "</option>";
            });
            document.getElementById("pessoa-setor-select").innerHTML = resultado;
            pessoa.alterarEmpresa();
            pessoa.alterarSetor();
            concluir();
        });
    } else concluir();
}

function modal2(nome, limpar) {
    limpar_invalido();
    limpar.forEach((id) => {
        document.getElementById(id).value = "";
    });
    $("#" + nome).modal();
}

function excluirMain(_id, prefixo, aviso, callback) {
    s_confirm(aviso, function() {
        $.post(URL + prefixo + "/excluir", {
            _token : $("meta[name='csrf-token']").attr("content"),
            id : _id
        }, function() {
            callback();
        });
    });
}

function s_confirm(texto, funcao) {
    Swal.fire({
        title: "Aviso",
        html : texto,
        showDenyButton : true,
        confirmButtonText : "NÃO",
        confirmButtonColor : "rgb(31, 41, 55)",
        denyButtonText : "SIM"
    }).then((result) => {
        if (result.isDenied) funcao();
    });
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

function s_alert(texto) {
    Swal.fire({
        icon : "warning",
        title : "Atenção",
        html : texto,
        confirmButtonColor : "rgb(31, 41, 55)"
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

    let filtro_tratado = "";
    let filtro_teste = _filter.split(",");
    filtro_teste.forEach((restricao) => {
        if (filtro_tratado) filtro_tratado += ",";
        if (restricao.indexOf("id=") === 0) restricao = document.getElementById(restricao.replace("id=", "")).value;
        filtro_tratado += restricao;
    });
    _filter = filtro_tratado;

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
        let retira_chars = function(texto) {
            let entityMap = {
                '&amp;': '&',
                '&lt;': '<',
                '&gt;': '>',
                '&quot': '"',
                '&#39;': "'",
                '&#x2F;': '/'
            };
            return String(texto).replace(/&amp;|&lt;|&gt;|&quot|&#39;|&#x2F;/g, function (s) {
                return entityMap[s];
            });
        }
        element.parent().find(".autocomplete-line").each(function () {
            $(this).click(function () {
                $(input_id).val($(this).data().id).trigger("change");
                element.val(retira_chars($(this).html().toString().split("|")[0].trim()));
                div_result.remove();
            });

            $(this).mouseover(function () {
                $(input_id).val($(this).data().id).trigger("change");
                element.val(retira_chars($(this).html().toString().split("|")[0].trim()));
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
        let el = document.getElementById(id);
        let erro_ou_vazio = !el.value;
        if (!erro_ou_vazio && id.indexOf("qtd-") > -1) erro_ou_vazio = !parseInt(el.value);
        if (erro_ou_vazio) {
            if (!_erro) _erro = "Preencha o campo";
            else _erro = "Preencha os campos";
            el.classList.add("invalido");
        }
        try {
            if (el.value.toString().toUpperCase().trim() != anteriores[id].toString().toUpperCase().trim()) _alterou = true;
        } catch(err) {}
    });
    return {
        alterou : _alterou,
        erro : _erro
    };
}

function limpar_invalido() {
    Array.from(document.querySelectorAll("input, select")).forEach((el) => {
        el.classList.remove("invalido");
    });
}

function hoje() {
    return new Date().toJSON().slice(0, 10).split('-').reverse().join('/');
}

function validar_datas(el_inicio, el_fim, comodato) {
    let erro = "";
    let aux = el_inicio.value.split("/");
    const inicio = new Date(aux[2], aux[1] - 1, aux[0]);
    aux = el_fim.value.split("/");
    const fim = new Date(aux[2], aux[1] - 1, aux[0]);
    if (inicio > fim) erro = "A data inicial não pode ser maior que a data final";
    else if (inicio.getTime() == fim.getTime() && comodato) erro = "A locação precisa durar mais de um dia";
    if (!comodato && erro) {
        el_inicio.classList.add("invalido");
        el_fim.classList.add("invalido");
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
        resultado[chave] = document.getElementById("rel-" + item);
        let el = document.getElementById("rel-id_" + item);
        if (el !== null) resultado["id_" + chave] = el;
    });
    return resultado;
}

function relObterElementosValor(elementos, chaves) {
    let resultado = {};
    chaves.forEach((chave) => {
        resultado[chave] = elementos[chave].value;
        resultado["id_" + chave] = elementos["id_" + chave].value;
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
        $.get(URL + "/relatorios/bilateral/consultar", valores, function(erro) {
            if (erro) {
                elementos[erro].classList.add("invalido");
                erro = erro == "empresa" ? "Empresa" : "Máquina";
                erro += " não encontrada";
                s_alert(erro);
            } else document.querySelector("#relatorioBilateralModal form").submit();
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
    document.getElementById("relatorioBilateralModalLabel").innerHTML = titulo;
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioBilateralModal", 0, function() {
            document.getElementById("rel-grupo1").value = grupo;
        });
    }, 0);
}

function RelatorioItens(resumido, maquina) {
    let elementos = relObterElementos(["inicio1", "fim1", "produto", "maquina2"]);
    
    this.validar = function() {
        limpar_invalido();
        let erro = "";
        if (resumido && (!(elementos.inicio.value && elementos.fim.value)) && document.getElementById("rel-tipo").value == "G") {
            if (!elementos.inicio.value) elementos.inicio.classList.add("invalido");
            if (!elementos.fim.value) elementos.fim.classList.add("invalido");
            s_alert("Preencha as datas");
            return;
        }

        if (elementos.inicio.value && elementos.fim.value) erro = validar_datas(elementos.inicio, elementos.fim, false);
        let req = ["produto"];
        if (maquina === undefined) req.push("maquina");
        $.get(URL + "/relatorios/extrato/consultar", relObterElementosValor(elementos, req), function(data) {
            if (data && !erro) {
                elementos[data].classList.add("invalido");
                erro == "maquina" ? "Máquina não encontrada" : "Produto não encontrado";
            }
            if (!erro) document.querySelector("#relatorioItensModal form").submit();
            else s_alert(erro);
        });
    }
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioItensModal", 0, function() {
            let modo_resumo = document.getElementById("rel-modo-resumo").classList;
            document.getElementById("rel-lm").value = "N";
            elementos.inicio.value = hoje();
            elementos.fim.value = hoje();
            document.getElementById("rel-id_maquina2").value = maquina !== undefined ? maquina : 0;
            document.getElementById("relatorioItensModalLabel").innerHTML = resumido ? maquina === undefined ? "Sugestão de compra" : "Solicitação de compra" : "Extrato de itens";
            document.getElementById("resumo").value = resumido ? "S" : "N";
            document.getElementById("rel-lm-chk").checked = false;
            document.querySelector("label[for='rel-lm-chk']").innerHTML = resumido ? "Listar apenas produtos cuja compra é sugerida" : "Listar movimentação";
            document.querySelector("#relatorioItensModal form").action = resumido ? maquina === undefined ? URL + "/relatorios/sugestao" : URL + "/solicitacoes" : URL + "/relatorios/extrato";
            let el_maq = document.getElementById("rel-maquina2").parentElement.parentElement.classList;
            if (maquina !== undefined) el_maq.add("d-none");
            else el_maq.remove("d-none");
            if (resumido) modo_resumo.remove("d-none");
            else modo_resumo.add("d-none");
        });
    }, 0);
}

function RelatorioControle() {
    let elementos = relObterElementos(["inicio2", "fim2", "pessoa1", "consumo1"]);

    this.validar = function() {
        limpar_invalido();
        let erro = "";
        if (elementos.inicio.value && elementos.fim.value) erro = validar_datas(elementos.inicio, elementos.fim, false);
        $.get(URL + "/relatorios/controle/consultar", relObterElementosValor(elementos, ["pessoa"]), function(data) {
            if (data && !erro) {
                elementos.pessoa.classList.add("invalido");
                erro = "Colaborador não encontrado";
            }
            if (!erro) {
                if (!elementos.id_pessoa.value.trim()) {
                    $.get(URL + "/relatorios/controle/pessoas", function(data2) {
                        if (typeof data2 == "string") data2 = $.parseJSON(data2);
                        controleTodos(data2);
                    });
                } else document.querySelector("#relatorioControleModal form").submit();    
            } else s_alert(erro);
        });
    }
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioControleModal", 0, function() {
            elementos.inicio.value = hoje();
            elementos.fim.value = hoje();
            elementos.consumo.value = "todos";
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

        let elemento = document.getElementById("rel-pessoa2");
        elemento.value = "";
        elemento.dataset.table = tabela;

        document.getElementById("rel-id_pessoa2").value = "";
    }

    this.validar = function() {
        limpar_invalido();
        let erro = "";
        if (elementos.inicio.value && elementos.fim.value) erro = validar_datas(elementos.inicio, elementos.fim, false);
        $.get(
            URL + "/relatorios/retiradas/consultar",
            relObterElementosValor(elementos, ["empresa", "pessoa", "setor"]),
            function(data) {
                if (data && !erro) {
                    elementos[data].classList.add("invalido");
                    erro = data != "maquina" ? data.charAt(0).toUpperCase() + data.substring(1) : "Máquina";
                    erro += " não encontrad";
                    erro += data == "setor" ? "o" : "a";
                }
                if (!erro) document.querySelector("#relatorioRetiradasModal form").submit();
                else s_alert(erro);
            }
        );
    }
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioRetiradasModal", 0, function() {
            ["rel-pessoa-tipo", "rel-consumo2", "rel-tipo"].forEach((id) => {
                let el = document.getElementById(id).parentElement.classList;
                if (quebra == "pessoa") {
                    el.add("col-4");
                    el.remove("col-6");
                } else {
                    el.add("col-6");
                    el.remove("col-4");
                }
            });
            let el_tipo = document.getElementById("rel-pessoa-tipo");
            elementos.inicio.value = hoje();
            elementos.fim.value = hoje();
            if (quebra == "setor") {
                elementos.pessoa.parentElement.classList.add("d-none");
                elementos.setor.parentElement.classList.remove("d-none");
                el_tipo.parentElement.classList.add("d-none");
            } else {
                elementos.setor.parentElement.classList.add("d-none");
                elementos.pessoa.parentElement.classList.remove("d-none");
                el_tipo.parentElement.classList.remove("d-none");
            }
            el_tipo.value = "todos";
            elementos.consumo.value = "todos";
            elementos.tipo.value = "A";
            let titulo = "Consumo por ";
            titulo += quebra == "pessoa" ? "colaborador" : "centro de custo";
            document.getElementById("relatorioRetiradasModalLabel").innerHTML = titulo;
            document.getElementById("rel-grupo2").value = quebra;
        });
    }, 0);
}

function limitar(el) {
    let texto = el.value.toString();
    if (!texto.length || parseInt(texto) < 1) el.value = 1;
    if (texto.length > 11) el.value = "".padStart(11, "9");
}

function numerico(el) {
    el.value = el.value.replace(/\D/g, "");
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
        let elRes = document.getElementById("table-atribuicoes");
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
            elRes.parentElement.classList.add("pb-4");
        } else elRes.parentElement.classList.remove("pb-4");
        elRes.innerHTML = resultado;
    });
}

function atribuicao(grade, id) {
    modal("atribuicoesModal", 0, function() {
        pessoa_atribuindo = id;
        $.get(URL + "/" + (location.href.indexOf("colaboradores") > -1 ? "colaboradores" : "setores") + "/mostrar/" + id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            let div_produto = document.getElementById("div-produto").classList;
            let div_referencia = document.getElementById("div-referencia").classList;
            let nome = location.href.indexOf("colaboradores") > -1 ? data.nome.toUpperCase() : data.descr.toUpperCase();
            document.getElementById("atribuicoesModalLabel").innerHTML = nome + " - Atribuindo " + (grade ? "grades" : "produtos");
            if (grade) {
                document.getElementById("referencia").dataset.filter = id;
                div_produto.add("d-none");
                div_referencia.remove("d-none");
            } else {
                div_produto.remove("d-none");
                div_referencia.add("d-none");
            }
            document.getElementById("obrigatorio").value = "opt-0";
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
        produto_ou_referencia_valor : document.getElementById(gradeGlobal ? "referencia" : "produto").value,
        validade : document.getElementById("validade").value,
        qtd : document.getElementById("quantidade").value,
        obrigatorio : document.getElementById("obrigatorio").value.replace("opt-", "")
    }, function(ret) {
        ret = parseInt(ret);
        switch(ret) {
            case 201:
                document.getElementById("id_produto").value = "";
                document.getElementById("referencia").value = "";
                document.getElementById("produto").value = "";
                document.getElementById("quantidade").value = 1;
                document.getElementById("validade").value = 1;
                document.getElementById("obrigatorio").value = "opt-0";
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
            document.getElementById("estiloAux").innerHTML = ".autocomplete-result{display:none}";
            [campo, "validade", "quantidade", "obrigatorio"].forEach((el) => {
                document.getElementById(el).disabled = true;
            });
            if (typeof data == "string") data = $.parseJSON(data);
            document.getElementById(campo).value = data.descr;
            $("#" + campo).trigger("keyup");
            setTimeout(function() {
                $($(".autocomplete-line").first()).trigger("click");
            }, 500);
            setTimeout(function() {
                document.getElementById("validade").value = data.validade;
                document.getElementById("quantidade").value = parseInt(data.qtd);
                document.getElementById("obrigatorio").value = "opt-" + data.obrigatorio;
                document.getElementById("estiloAux").innerHTML = "";
                [campo, "validade", "quantidade", "obrigatorio"].forEach((el) => {
                    document.getElementById(el).disabled = false;
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

function foto_pessoa(seletor, caminho) {
    let el = document.querySelector(seletor);
    if (caminho) caminho = URL + "/storage/" + caminho;
    el.style.backgroundImage = caminho ? "url('" + caminho + "')" : "";
    el.firstElementChild.classList.remove("d-none");
    if (caminho) {
        el.style.backgroundSize = "100% 100%";
        el.firstElementChild.classList.add("d-none");
    }
}

function formatar_cpf(el) {
    el.classList.remove("invalido");
    let cpf = el.value;
    let num = cpf.replace(/[^\d]/g, '');
    let len = num.length;
    if (len <= 6) cpf = num.replace(/(\d{3})(\d{1,3})/g, '$1.$2');
    else if (len <= 9) cpf = num.replace(/(\d{3})(\d{3})(\d{1,3})/g, '$1.$2.$3');
    else {
        cpf = num.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/g, "$1.$2.$3-$4");
        cpf = cpf.substring(0, 14);
    }
    el.value = cpf;
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
        elementos.id_pessoa.value = ids[i];
        let existe = await $.get(URL + "/relatorios/controle/existe", {
            id_pessoa : ids[i],
            consumo : elementos.consumo.value,
            inicio : elementos.inicio.value,
            fim : elementos.fim.value
        });
        if (parseInt(existe)) {
            algum_existe = true;
            document.querySelector("#relatorioControleModal form").submit();
        }
    }
    lista.forEach((el) => {
        el.style.removeProperty("z-index");
    });
    modal.removeProperty("z-index");
    loader.removeProperty("display");
    elementos.id_pessoa.value = "";
    if (!algum_existe) {
        elementos.pessoa.classList.add("invalido");
        s_alert("Colaborador não encontrado");
    }
}

function RelatorioRanking() {
    let elementos = relObterElementos(["inicio4", "fim4"]);

    this.validar = function() {
        limpar_invalido();
        let erro = "";
        if (elementos.inicio.value && elementos.fim.value) erro = validar_datas(elementos.inicio, elementos.fim, false);
        if (!erro) document.querySelector("#relatorioRankingModal form").submit();
        else s_alert(erro);
    }
    
    limpar_invalido();
    setTimeout(function() {
        modal("relatorioRankingModal", 0, function() {
            elementos.inicio.value = hoje();
            elementos.fim.value = hoje();
        });
    }, 0);
}

function trocarEmpresa() {
    $.post(URL + "/colaboradores/alterar-empresa", {
        _token : $("meta[name='csrf-token']").attr("content"),
        idEmpresa : document.getElementById("empresa-select").value
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
        document.getElementById("empresa-select").innerHTML = resultado;
        document.querySelector("#empresa-select option[value='" + EMPRESA + "']").selected = true;
        $("#trocarEmpresaModal").modal();    
    });
}

async function avisarSolicitacao() {
    let comodatos = await $.get(URL + "/solicitacoes/meus_comodatos");
    if (typeof comodatos == "string") comodatos = $.parseJSON(comodatos);

    for (let i = 0; i < comodatos.length; i++) {
        let retorno;
        try {
            retorno = await $.get(URL + "/solicitacoes/aviso/" + comodatos[i]);
            if (typeof retorno == "string") retorno = $.parseJSON(retorno);
        } catch (err) {
            continue;
        }
        let texto = "Sua solicitação feita no dia " + retorno.criacao + " foi ";
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
        if (retorno.possui_inconsistencias) {
            texto += ".<br>Deseja verificar as diferenças?";
            let viz = await Swal.fire({
                icon : "sucess",
                html : texto,
                showDenyButton : true,
                confirmButtonText : "SIM",
                confirmButtonColor : "rgb(31, 41, 55)",
                denyButtonText : "NÃO"
            })
            if (viz.isConfirmed) {
                let link = document.createElement("a");
                link.href = URL + "/relatorios/solicitacao/" + retorno.id;
                link.target = "_blank";
                link.click();
            }
        } else {
            await Swal.fire({
                icon: "EF".indexOf(retorno.status) > -1 ? "success" : "warning",
                text: texto,
                confirmButtonColor: "rgb(31, 41, 55)"
            });
        }
    }
}