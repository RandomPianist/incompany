class Relatorios {
    _relObterElementos(lista) {
        const resultado = {};
        lista.forEach((item) => {
            const chave = item.replace(/[0-9]/g, '');
            resultado[chave] = $("#rel-" + item);
            const el = document.getElementById("rel-id_" + item);
            if (el !== null) resultado["id_" + chave] = $(el);
        });
        return resultado;
    }

    _relObterElementosValor(elementos, chaves) {
        const resultado = {};
        chaves.forEach((chave) => {
            resultado[chave] = $(elementos[chave]).val();
            resultado["id_" + chave] = $(elementos["id_" + chave]).val();
        });
        return resultado;
    }

    _validarDatas(inicio, fim, erro) {
        if (erro === undefined) erro = "";
        if ($(inicio).val() && $(fim).val()) erro = validar_datas($(inicio), $(fim), false);
        return erro;
    }
}

class RelatorioBilateral extends Relatorios {
    #grupo;
    #titulo;

    constructor(_grupo) {
        super();
        this.#grupo = _grupo;
        this.#titulo = "Empresas por máquina";

        if (this.#grupo == "maquinas-por-empresa") {
            this.inverter();
            this.#titulo = "Máquinas por empresa";
        }

        $("#relatorioBilateralModalLabel").html(this.#titulo);

        limpar_invalido();
        setTimeout(() => {
            modal("relatorioBilateralModal", 0, () => {
                $("#rel-grupo1").val(this.#grupo);
            });
        }, 0);
    }

    validar() {
        limpar_invalido();
        const elementos = this._relObterElementos(["empresa1", "maquina1"]);
        const valores = this._relObterElementosValor(elementos, ["empresa", "maquina"]);
        valores.prioridade = this.#grupo == "maquinas-por-empresa" ? "empresas" : "maquinas";
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

    inverter() {
        const arr = [1, 0];
        const wrapper = document.querySelectorAll("#relatorioBilateralModal .container");
        const items = wrapper[0].children;
        const elements = document.createDocumentFragment();

        arr.forEach(idx => {
            elements.appendChild(items[idx].cloneNode(true));
        });

        wrapper[0].innerHTML = null;
        wrapper[0].appendChild(elements);

        dimensionar_linhas();
    }
}

class RelatorioItens extends Relatorios {
    #tipo;
    #maquina;
    #resumido;
    #elementos;

    constructor(tipo, maquina) {
        super();
        this.#tipo = tipo;
        this.#maquina = maquina;
        this.#resumido = tipo == "S";
        this.#elementos = this._relObterElementos(["inicio1", "fim1", "produto", "maquina2"]);

        limpar_invalido();
        setTimeout(() => {
            modal("relatorioItensModal", 0, () => {
                $("#rel-lm").val("N");
                $(this.#elementos.inicio).val(hoje());
                $(this.#elementos.fim).val(hoje());
                $("#rel-id_maquina2").val(this.#maquina !== undefined ? this.#maquina : 0);
                $("#relatorioItensModalLabel").html(
                    this.#resumido
                        ? this.#maquina === undefined ? "Sugestão de compra" : "Solicitação de compra"
                        : this.#tipo == "E" ? "Extrato de itens" : "Posição de estoque"
                );
                $("#resumo").val(this.#resumido ? "S" : "N");
                $("#rel-lm-chk").prop("checked", this.#tipo == "E");
                $("#rel-lm-chk").trigger("change");

                const pai = $($($($("#rel-lm-chk").parent()).parent()).parent());
                if (!this.#resumido) $(pai).addClass("d-none");
                else $(pai).removeClass("d-none");

                $("label[for='rel-lm-chk']").html(
                    this.#resumido ? "Listar apenas produtos cuja compra é sugerida" : "Listar movimentação"
                );

                $("#relatorioItensModal form").attr(
                    "action",
                    this.#resumido
                        ? this.#maquina === undefined ? URL + "/relatorios/sugestao" : URL + "/solicitacoes"
                        : URL + "/relatorios/extrato"
                );

                const el_maq = $($($("#rel-maquina2").parent()).parent());
                if (this.#maquina !== undefined) $(el_maq).addClass("d-none");
                else $(el_maq).removeClass("d-none");

                if (this.#resumido) $("#rel-modo-resumo").removeClass("d-none");
                else $("#rel-modo-resumo").addClass("d-none");

                if (this.#tipo == "P") $("#rel-datas").addClass("d-none");
                else $("#rel-datas").removeClass("d-none");
            });
        }, 0);
    }

    validar() {
        limpar_invalido();
        if (this.#tipo == "P") {
            $(this.#elementos.inicio).val("01/01/2000");
            $(this.#elementos.fim).val("01/01/3000");
        }

        let erro = "";
        if (
            this.#resumido &&
            (!($(this.#elementos.inicio).val() && $(this.#elementos.fim).val())) &&
            $("#rel-tipo").val() == "G"
        ) {
            if (!$(this.#elementos.inicio).val()) $(this.#elementos.inicio).addClass("invalido");
            if (!$(this.#elementos.fim).val()) $(this.#elementos.fim).addClass("invalido");
            s_alert("Preencha as datas");
            return;
        }

        erro = this._validarDatas($(this.#elementos.inicio), $(this.#elementos.fim), erro);

        let req = ["produto"];
        if (this.#maquina === undefined) req.push("maquina");
        req = this._relObterElementosValor(this.#elementos, req);
        req.inicio = $(this.#elementos.inicio).val();
        req.fim = $(this.#elementos.fim).val();
        if (this.#maquina !== undefined) req.id_maquina = this.#maquina;

        $.get(URL + "/relatorios/extrato/consultar", req, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            if (data.el && !erro) {
                const lista = data.el.split(",");
                lista.forEach((el) => {
                    $(this.#elementos[el]).addClass("invalido");
                    $(this.#elementos[el]).val(data[el + "_correto"]);
                });
            }
            if (!erro) $("#relatorioItensModal form").submit();
            else s_alert(erro);
        }.bind(this));
    }

    mudaTipo() {
        const elDias = $("#rel-dias");
        const giro = $("#rel-tipo2").val() == "G";
        $(elDias).attr("disabled", !giro);
        if (giro) $(elDias).focus();
        else $(this.#elementos.maquina).focus();
    }
}

class RelatorioControle extends Relatorios {
    #elementos;

    constructor() {
        super();
        this.#elementos = this._relObterElementos(["inicio2", "fim2", "pessoa1", "consumo1"]);

        limpar_invalido();
        setTimeout(() => {
            modal("relatorioControleModal", 0, () => {
                $(this.#elementos.inicio).val(hoje());
                $(this.#elementos.fim).val(hoje());
                $(this.#elementos.consumo).val("todos");
            });
        }, 0);
    }

    validar() {
        limpar_invalido();
        let erro = this._validarDatas($(this.#elementos.inicio), $(this.#elementos.fim));

        $.get(URL + "/relatorios/controle/consultar", this._relObterElementosValor(this.#elementos, ["pessoa"]), (data) => {
            if (data && !erro) {
                $(this.#elementos.pessoa).addClass("invalido");
                erro = "Colaborador não encontrado";
            }
            if (!erro) $("#relatorioControleModal form").submit();
            else s_alert(erro);
        });
    }
}

class RelatorioRetiradas extends Relatorios {
    #elementos;
    #quebra;

    constructor(quebra) {
        super();
        this.#quebra = quebra;
        this.#elementos = this._relObterElementos(["inicio3", "fim3", "empresa2", "pessoa2", "setor1", "consumo2", "tipo"]);

        limpar_invalido();
        setTimeout(() => {
            modal("relatorioRetiradasModal", 0, () => {
                $("#rel-pessoa-tipo, #rel-consumo2, #rel-tipo").each((_, el) => {
                    const parent = $($(el).parent());
                    parent.addClass(this.#quebra == "pessoa" ? "col-4" : "col-6");
                    parent.removeClass(this.#quebra == "pessoa" ? "col-6" : "col-4");
                });

                $(this.#elementos.inicio).val(hoje());
                $(this.#elementos.fim).val(hoje());

                if (this.#quebra == "setor") {
                    $($(this.#elementos.pessoa).parent()).addClass("d-none");
                    $($(this.#elementos.setor).parent()).removeClass("d-none");
                    $($("#rel-pessoa-tipo").parent()).addClass("d-none");
                } else {
                    $($(this.#elementos.setor).parent()).addClass("d-none");
                    $($(this.#elementos.pessoa).parent()).removeClass("d-none");
                    $($("#rel-pessoa-tipo").parent()).removeClass("d-none");
                }

                $("#rel-pessoa-tipo").val("todos");
                $(this.#elementos.consumo).val("todos");
                $(this.#elementos.tipo).val("A");

                const titulo = "Consumo por " + (this.#quebra == "pessoa" ? "colaborador" : "centro de custo");
                $("#relatorioRetiradasModalLabel").html(titulo);
                $("#rel-grupo2").val(this.#quebra);
            });
        }, 0);
    }

    atualizarTabela(tipo) {
        let tabela = "pessoas";
        switch (tipo) {
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

    validar() {
        limpar_invalido();
        let erro = this._validarDatas($(this.#elementos.inicio), $(this.#elementos.fim));

        $.get(
            URL + "/relatorios/retiradas/consultar",
            this._relObterElementosValor(this.#elementos, ["empresa", "pessoa", "setor"]),
            (data) => {
                if (data && !erro) {
                    $(this.#elementos[data]).addClass("invalido");
                    erro = data !== "maquina" ? "Centro de custo" : "Máquina";
                    erro += data == "setor" ? " não encontrado" : " não encontrada";
                }
                if (!erro) $("#relatorioRetiradasModal form").submit();
                else s_alert(erro);
            }
        );
    }
}

class RelatorioRanking extends Relatorios {
    #elementos;

    constructor() {
        super();
        this.#elementos = this._relObterElementos(["inicio4", "fim4"]);

        limpar_invalido();
        setTimeout(() => {
            modal("relatorioRankingModal", 0, () => {
                $(this.#elementos.inicio).val(hoje());
                $(this.#elementos.fim).val(hoje());
                $("#rel-tipo3").val("todos");
            });
        }, 0);
    }

    validar() {
        limpar_invalido();
        let erro = this._validarDatas($(this.#elementos.inicio), $(this.#elementos.fim));

        if (!erro) $("#relatorioRankingModal form").submit();
        else s_alert(erro);
    }
}

class RelatorioPessoas extends Relatorios {
    constructor() {
        super();
    
        limpar_invalido();
        setTimeout(() => {
            modal("relatorioPessoasModal", 0, () => {
                $("#rel-biometria").val("todos");
            });
        }, 0);
    }

    async validar() {
        limpar_invalido();

        let erro = "";
        let respEmp = await $.get(URL + "/empresas/consultar2", {
            id_empresa : $("#rel-id_empresa3").val(),
            empresa : $("#rel-empresa3").val()
        });
        if (respEmp != "ok") {
            $("#rel-empresa3").addClass("invalido");
            s_alert("Empresa não encontrada");
            return;
        }

        let respSetor = await $.get(URL + "/setores/consultar2", {
            id_empresa : $("#rel-id_setor2").val(),
            empresa : $("#rel-setor2").val()
        });
        if (respSetor != "ok") {
            $("#rel-setor2").addClass("invalido");
            s_alert("Setor não encontrado");
            return;
        }

        $("#relatorioPessoasModal form").submit();
    }

    mudou_empresa() {
        $.get(URL + "/empresas/consultar2", {
            id_empresa : $("#rel-id_empresa3").val(),
            empresa : $("#rel-empresa3").val()
        }, function(resp) {
            if (resp == "ok") {
                $("#rel-pessoa3, #rel-id_pessoa3, #rel-setor2, #rel-id_setor2").each(function() {
                    $(this).val("");
                });
                $("#rel-pessoa3").attr("data-filter_col", "id_empresa");
                $("#rel-pessoa3").attr("data-filter", $("#rel-id_empresa3").val());
                $("#rel-setor2").attr("data-filter_col", "id_empresa");
                $("#rel-setor2").attr("data-filter", $("#rel-id_empresa3").val());
            }
        });
    }

    mudou_setor() {
        $.get(URL + "/setores/consultar2", {
            id_empresa : $("#rel-id_setor2").val(),
            empresa : $("#rel-setor2").val()
        }, function(resp) {
            if (resp == "ok") {
                $("#rel-pessoa3, #rel-id_pessoa3").each(function() {
                    $(this).val("");
                });
                $("#rel-pessoa3").attr("data-filter_col", "id_setor");
                $("#rel-pessoa3").attr("data-filter", $("#rel-id_setor2").val());
            }
        });
    }
}