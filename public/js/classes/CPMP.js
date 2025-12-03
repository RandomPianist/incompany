class CPMP {
    #tipo;
    #total = 0;
    #requests = new Array();

    constructor(tipo) {
        this.#tipo = tipo;
        this.listeners();
        this.listar(true);
    }

    #limparReq = (naoCarregar) => {
        this.#requests = new Array();
        if (!naoCarregar) iniciarCarregamento(this.#tipo + "Modal");
    }

    #contar_main = () => {
        let titulo = $("#" + this.#tipo + "ModalLabel").html();
        if (titulo.indexOf("|") > -1) titulo = titulo.split("|")[0].trim();
        titulo += " | Listando " + document.querySelectorAll("#" + this.#tipo + "Modal .form-search.old").length + " de " + this.#total;
        $("#" + this.#tipo + "ModalLabel").html(titulo);
    }

    #limpar = () => {
        $("#" + this.#tipo + "Modal .remove-linha").each(function() {
            $(this).trigger("click");
        });
        $(this.#tipo == "cp" ? "#cpModal #produto-1" : "#mpModal #maquina-1").val("");
        $(this.#tipo == "cp" ? "#cpModal #id_produto-1" : "#mpModal #id_maquina-1").val("");
        $("#" + this.#tipo + "Modal #lixeira-1").val("opt-0");
        $("#" + this.#tipo + "Modal #preco-1").val(0).trigger("keyup");
        $("#" + this.#tipo + "Modal #minimo-1").val(0).trigger("keyup");
        $("#" + this.#tipo + "Modal #maximo-1").val(0).trigger("keyup");
    }

    #validar_main = async () => {
        limpar_invalido();
        let erro = "";
        let req = this.#tipo == "cp" ? {
            produtos_descr : obter_vetor("produto", "cp"),
            produtos_id : obter_vetor("id-produto", "cp"),
            id_maquina : $($(".id_maquina")[0]).val()
        } : {
            maquinas_descr : obter_vetor("maquina", "mp"),
            maquinas_id : obter_vetor("id-maquina", "mp"),
            id_produto : $("#id_produto").val()
        };
        req.precos = obter_vetor("preco", this.#tipo);
        req.maximos = obter_vetor("maximo", this.#tipo);
        
        let data = await $.get(URL + "/" + (this.#tipo == "cp" ? "maquinas/produto" : "produtos/maquina") + "/consultar", req);
        if (typeof data == "string") data = $.parseJSON(data);
        
        if (!erro && data.texto) {
            for (let i = 0; i < data.campos.length; i++) {
                let el = $("#" + this.#tipo + "Modal #" + data.campos[i]); 
                $(el).val(data.valores[i]);
                $(el).trigger("keyup");
                $(el).addClass("invalido");
            }
            erro = data.texto;
        }
        
        if (erro) return erro;
        
        $("#" + this.#tipo + "Modal .preco").each(function() {
            $(this).val(parseInt(apenasNumeros($(this).val())) / 100);
        });
        
        iniciarCarregamento(this.#tipo + "Modal");
        $("#" + this.#tipo + "Modal form").submit();
        return "";
    }

    async validar() {
        const erro = await this.#validar_main();
        if (erro) s_alert(erro);
    }

    async pergunta_salvar() {
        const resp = await s_alert({
            html : "Deseja salvar as alterações?",
            ync : true
        });

        if (resp.isConfirmed) {
            let erro = await this.validar();
            if (erro) {
                this.limpar_tudo();
                s_alert({
                    icon : "error",
                    title : "Não foi possível salvar"
                });
            }
        } else if (resp.isDenied) this.limpar_tudo();
        else $("#" + this.#tipo + "Modal").modal();
    }

    listeners() {
        const that = this;

        $((this.#tipo == "mp" ? "#mpModal .id-maquina" : "#cpModal .id-produto") + ", #" + this.#tipo + "Modal .minimo, #" + this.#tipo + "Modal .maximo, #" + this.#tipo + "Modal .preco, #" + this.#tipo + "Modal .lixeira").each(function() {
            $(this).off("change").on("change", function() {
                const linha = $($($(this).parent()).parent())[0];

                if ($(this).val().trim()) {
                    var request = $.get(URL + "/maquinas/produto/verificar-novo", {
                        preco : parseInt(apenasNumeros($($(linha).find(".preco")[0]).val())) / 100, // Assumindo global
                        minimo : $($(linha).find(".minimo")[0]).val(),
                        maximo : $($(linha).find(".maximo")[0]).val(),
                        lixeira : $($(linha).find(".lixeira")[0]).val().replace("opt-", ""),
                        id_produto : that.#tipo == "mp" ? $("#id_produto").val() : $($(linha).find(".id-produto")[0]).val(),
                        id_maquina : that.#tipo == "mp" ? $($(linha).find(".id-maquina")[0]).val() : $($(".id_maquina")[0]).val()
                    }, function(novo) {
                        const el = $($(linha).find(".form-search")[0]);
                        if (parseInt(novo)) $(el).addClass("new").removeClass("old");
                        else $(el).addClass("old").removeClass("new");
                    });
                } else {
                    $($(linha).find(".form-search")[0]).addClass("new").removeClass("old");
                    var request = $.Deferred().resolve(); 
                }

                that.#requests.push(request);

                if ($(this).hasClass(that.#tipo == "cp" ? "id-produto" : "id-maquina")) atualizaPreco(apenasNumeros($(this).attr("id")), that.#tipo);
                if ($(this).hasClass("maximo") || $(this).hasClass("minimo")) limitar($(this), true);
            });
            $(this).off("keyup").on("keyup", function() {
                if ($(this).hasClass("maximo") || $(this).hasClass("minimo")) limitar($(this), true);
            });
        });
    }

    limpar_tudo() {
        this.#limpar();
        const lista = this.#tipo == "cp" ? ["busca-prod", "busca-refer", "busca-cat"] : ["busca-maq"];
        lista.forEach((id) => {
            $("#" + id).val("");
        });
    }

    contar(naoCarregar) {
        if (naoCarregar === undefined) naoCarregar = false;
        const requestsToWaitFor = this.#requests;
        this.#limparReq(naoCarregar);
        $.when(...requestsToWaitFor).always(() => {
            this.#contar_main();
            Array.from(document.getElementsByClassName("btn-primary")).forEach((el) => {
                el.style.removeProperty("z-index");
            });
            document.getElementById("loader").style.removeProperty("display");
            document.getElementById(this.#tipo + "Modal").style.removeProperty("z-index");
        });
    }

    adicionar_campo() {
        const cont = ($("#" + this.#tipo + "Modal input[type=number]").length / 2) + 1;
        const linha = $($("#" + this.#tipo + "Modal #template-linha").html());

        if (this.#tipo == "cp") {
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

        $($(linha).find(".remove-linha")[0]).on("click", () => {
            $(linha).remove();
            let classes = ["lixeira", "minimo", "maximo", "preco"];
            if (this.#tipo == "cp") classes.push("produto", "id_produto");
            else classes.push("maquina", "id_maquina");
            
            classes.forEach((classe) => {
                $("#" + this.#tipo + "Modal ." + classe).each(function(i) {
                    $(this).attr("id", classe + "-" + (i + 1));
                });
            });
            this.#contar_main();
        });

        $("#" + this.#tipo + "Modal .modal-tudo").append($(linha));

        this.listeners();
        // carrega_autocomplete();
        carrega_dinheiro();

        $(".form-control").keydown(function() {
            $(this).removeClass("invalido");
        });

        this.#limparReq(false);
        $($(linha).find(this.#tipo == "cp" ? ".id-produto" : ".id-maquina")[0]).trigger("change");
        $($(linha).find(".minimo")[0]).trigger("change");
        $($(linha).find(".maximo")[0]).trigger("change");
        this.contar();
    }

    listar(abrir) {
        if (abrir === undefined) abrir = false;
        
        const params = this.#tipo == "cp" ? {
            id_maquina : $($(".id_maquina")[0]).val(),
            filtro : $("#busca-prod").val(),
            filtro_ref : $("#busca-ref").val(),
            filtro_cat : $("#busca-cat").val()
        } : {
            id_produto : $("#id_produto").val(),
            filtro : $("#busca-maq").val()
        };

        const url = URL + "/" + (this.#tipo == "mp" ? "produtos/maquina" : "maquinas") + "/listar";

        $.get(url, params, (data) => {
            this.#limpar();
            if (typeof data == "string") data = $.parseJSON(data);
            
            this.#total = data.total;
            data = data.lista;
            this.#limparReq(false);
            
            for (let i = 0; i < data.length; i++) {
                if (i > 0) this.adicionar_campo();
                
                $((this.#tipo == "cp" ? "#cpModal #produto-" : "#mpModal #maquina-") + (i + 1)).val(data[i][this.#tipo == "cp" ? "produto" : "maquina"]);
                $((this.#tipo == "cp" ? "#cpModal #id_produto-" : "#mpModal #id_maquina-") + (i + 1)).val(data[i][this.#tipo == "cp" ? "id_produto" : "id_maquina"]).trigger("change"); 
                $("#" + this.#tipo + "Modal #lixeira-" + (i + 1)).val("opt-" + data[i].lixeira);
                $("#" + this.#tipo + "Modal #preco-" + (i + 1)).val(data[i].preco).trigger("keyup");
                $("#" + this.#tipo + "Modal #minimo-" + (i + 1)).val(parseInt(data[i].minimo)).trigger("keyup");
                $("#" + this.#tipo + "Modal #maximo-" + (i + 1)).val(parseInt(data[i].maximo)).trigger("keyup");
            }
            
            if (abrir && !validacao_bloqueada) $("#" + this.#tipo + "Modal").modal(); 
            
            $(this.#tipo == "cp" ? "#cpModal .id-produto" : "#mpModal .id-maquina").each(function() {
                $(this).trigger("change");
            });
            
            $("#" + this.#tipo + "Modal .minimo, #" + this.#tipo + "Modal .maximo").each(function() {
                limitar($(this), true);
            });

            this.contar();
        });
    }
}