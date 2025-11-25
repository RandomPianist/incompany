class JanelaDinamica {
    #prefixo;
    #modalId;

    permissoesRascunho = [];
    supervisor = false;
    visitante = false;

    constructor(config) {
        if (this.constructor === JanelaDinamica) {
            throw new Error("A classe JanelaDinamica é abstrata e não pode ser instanciada diretamente.");
        }
        this.#prefixo = config.prefixo;
        this.#modalId = config.modalId;
    }

    _getLabelTexto(chave, titulo) {
        const setor = titulo === undefined;
        if (setor) titulo = "centro de custo";
        const texto = (
            setor ?
                "Pessoas nesse " + titulo + " " + (chave == "financeiro" ? "têm" : "podem") + ", por padrão,"
            :
                "Esse " + titulo + " " + (chave == "financeiro" ? "tem" : "pode")
        ) + " "; 
        switch (chave) {
            case "financeiro":
                return texto + "acesso ao módulo financeiro.";
            case "atribuicoes":
                return texto + "atribuir produtos e grades a funcionários.";
            case "retiradas":
                return texto + "fazer retiradas retroativas.";
            case "solicitacoes":
                return texto + "solicitar reposição de produtos.";
            default:
                return texto + "criar, editar e excluir " + (chave == "usuarios" ? "usuários, exceto administradores" : "funcionários") + ".";
        }
    }

    _obterHtmlPermissoes(usuario, titulo) {
        $(this.#modalId + " .linha-permissao").remove();
        const prefixoCompleto = this.#prefixo + "-";
        let resultado = "";

        if (!usuario) this.permissoesRascunho = new Array();
        for (let x in permissoes) {
            resultado += "<div class = 'row linha-permissao" + (!usuario ? " d-none" : "") + "'>" +
                "<div class = 'col-12'>" +
                    "<div class = 'custom-control custom-switch'>" +
                        "<input id = '" + prefixoCompleto + x + "' name = '" + x + "' type = 'hidden' />" +
                        "<input id = '" + prefixoCompleto + x + "-chk' class = 'checkbox custom-control-input' type = 'checkbox' />" +
                        "<label id = '" + prefixoCompleto + x + "-lbl' for = '" + prefixoCompleto + x + "-chk' class = 'custom-control-label lbl-permissao'>" +
                            this._getLabelTexto(x, titulo) +
                        "</label>" +
                    "</div>" +
                "</div>" +
            "</div>";
        }
        return resultado.replace("linha-permissao'", "linha-permissao mt-3'");
    }

    _permissoesPreencher({
        ehSetorSistema = false,
        titulo = 'item' 
    }) {
        const prefixoCompleto = "#" + this.#prefixo + "-";

        for (let x in permissoes) {
            $(prefixoCompleto + x + "-chk").off("change").on("change", function() {
                atualizarChk(prefixoCompleto.replace("#", "") + x, true);
            });

            const permissao = this.permissoesRascunho[x] || false;
            $(prefixoCompleto + x + "-chk").prop("checked", permissao).attr("disabled", ehSetorSistema || !permissoes[x]);
            $(prefixoCompleto + x).val(permissao ? "1" : "0");

            const labelSeletor = prefixoCompleto + x + "-lbl";
            if (ehSetorSistema) $(labelSeletor).attr("title", "Não é permitido alterar essa configuração em um centro de custo do sistema");
            else if (!permissoes[x]) $(labelSeletor).attr("title", "Não é possível " + (permissao ? "retirar d" : "atribuir a ") + "esse " + titulo + " permissões que seu usuário não tem");
            else $(labelSeletor).removeAttr("title");
        }
    }

    _ajustarSupervisor(obj, titulo) {
        const cadastro = titulo === undefined ? "setor" : "pessoa";
        titulo = "";
        $("#" + cadastro + "-supervisor-chk").off("change").on("change", function() {
            $("#" + cadastro + "-supervisor").val($(this).prop("checked") ? "1" : "0");
            obj.supervisor = $(this).prop("checked");
            if (cadastro == "pessoa") pessoa.mudaTitulo();
        });

        $("#" + cadastro + "-supervisor").val(obj.supervisor ? "1" : "0");
        if (cadastro == "pessoa") {
            $("#pessoa-supervisor-chk").prop("checked", obj.supervisor).attr("disabled", (!SUPERVISOR || obj.visitante) ? true : false);
            
            if (!SUPERVISOR) $("#pessoa-supervisor-lbl").attr("title", "Não é possível " + (obj.supervisor ? "retirar d" : "atribuir a ") + "esse " + titulo + " permissões que seu usuário não tem");
            else if (obj.visitante) $("#pessoa-supervisor-lbl").attr("title", "Visitantes não podem ser supervisores");
            else $("#pessoa-supervisor-lbl").removeAttr("title");
        } else {
            $("#setor-supervisor-chk").prop("checked", obj.supervisor).attr("disabled", !SUPERVISOR);
            
            if (!SUPERVISOR) $("#setor-supervisor-lbl").attr("title", "Não é possível " + (obj.supervisor ? "retirar d" : "atribuir a ") + "esse centro de custo permissões que seu usuário não tem");
            else $("#setor-supervisor-lbl").removeAttr("title");
        }
    }
}

class Pessoa extends JanelaDinamica {
    #id;
    #mostrandoSenha = false;
    #impedirMostrarSenha = false;
    #dados;
    #htmlPermissoes;
    #usuario;
    #id_setor;
    #id_empresa;
    #ant_id_setor;
    #ant_id_empresa;
    #ant_supervisor;
    #ant_visitante;
    #obrigatorios;

    #validar_email(__email) {
        if ((__email == null) || (__email.length < 4)) return false;
        let partes = __email.split("@");
        if (partes.length != 2) return false;
        let pre = partes[0];
        if (!pre.length) return false;
        if (!/^[a-zA-Z0-9_.-/+]+$/.test(pre)) return false;
        let partesDoDominio = partes[1].split(".");
        if (partesDoDominio.length < 2) return false;
        let valido = true;
        partesDoDominio.forEach((parteDoDominio) => {
            if (!parteDoDominio.length) valido = false;
            if (!/^[a-zA-Z0-9-]+$/.test(parteDoDominio)) valido = false;
        })
        return valido;
    }

    constructor(_id) {
        super({
            prefixo: 'pessoa',
            modalId: '#pessoasModal'
        });
        this.#id = _id;

        $('#pessoa-empresa-select, #pessoa-setor-select').select2({
            width: '100%',
            language: {
                noResults: function () {
                    return "Nenhum resultado encontrado";
                }
            }
        });

        $($("#pessoa-empresa-select").next()).off("click").on("click", function() {
            Array.from(document.querySelectorAll(".select2-container--default .select2-results>.select2-results__options")).forEach((el) => {
                el.scrollTo(0,0);
            });
        })

        $('#pessoa-setor-select').on('select2:select', (e) => this.mudou_setor(e.params.data.id, undefined, true));
        $('#pessoa-empresa-select').on('select2:select', (e) => this.mudou_empresa(e.params.data.id, undefined, true));

        $.get(URL + "/empresas/minhas", (minhasEmpresas) => {
            const concluir = (_id_empresa) => {
                $("#pessoa-empresa-select").val(_id_empresa);
                this.mudou_empresa($("#pessoa-empresa-select").val(), () => {
                    if (this.#dados !== undefined) {
                        const that = this;
                        $("#nome, #cpf, #email, #funcao, #admissao, #telefone", "#matricula").each(function() {   
                            $(this).val(that.#dados[$(this).attr("id")]).trigger("keyup");
                        });
                        $($("#pessoasModal .user-pic").parent()).removeClass("d-none");
                        if (!this.#dados.foto) {
                            let nome = this.#dados.nome.toUpperCase().replace("DE ", "").split(" ");
                            let iniciais = "";
                            iniciais += nome[0][0];
                            if (nome.length > 1) iniciais += nome[nome.length - 1][0];
                            $("#pessoasModal .user-pic span").html(iniciais);
                        }
                        foto_pessoa("#pessoasModal .user-pic", this.#dados.foto ? this.#dados.foto : "");
                        this.#ant_id_empresa = parseInt(this.#dados.id_empresa);
                        this.#ant_id_setor = parseInt(this.#dados.id_setor);
                        for (let x in permissoes) this.permissoesRascunho[x] = parseInt(this.#dados[x]) ? true : false;
                        this.supervisor = parseInt(this.#dados.supervisor);
                        this.visitante = parseInt(this.#dados.visitante);
                        this.#ant_supervisor = this.supervisor;
                        this.#ant_visitante = this.visitante;
                        this.mudaTitulo();
                    } else {
                        this.#ant_id_empresa = 0;
                        this.#ant_id_setor = 0;
                        $($("#pessoasModal .user-pic").parent()).addClass("d-none");
                    }
                    modal("pessoasModal", this.#id, () => {
                        $("#id_setor").val(this.#id_setor);
                        $("#id_empresa").val(this.#id_empresa);
                        $("#pessoa-setor-select").val(this.#id_setor);
                        $("#pessoa-empresa-select").val(this.#id_empresa).trigger("change");
                    });
                });
            }

            if (typeof minhasEmpresas == "string") minhasEmpresas = $.parseJSON(minhasEmpresas);
            let resultado = !EMPRESA ? "<option value = '0'>--</option>" : "";
            let primeiro = 0;
            minhasEmpresas.empresas.forEach((empresa) => {
                resultado += "<option value = '" + empresa.id + "'" + (minhasEmpresas.filial == "S" ? " disabled" : "") + ">" + empresa.nome_fantasia + "</option>";
                if (!primeiro && minhasEmpresas.filial == "N") primeiro = empresa.id;
                empresa.filiais.forEach((filial) => {
                    if (!primeiro && minhasEmpresas.filial == "S") primeiro = filial.id;
                    resultado += "<option value = '" + filial.id + "'>- " + filial.nome_fantasia + "</option>";
                });
            });
            $("#pessoa-empresa-select").html(resultado);

            if (this.#id) {
                $.get(URL + "/colaboradores/mostrar/" + this.#id, (_dados) => {
                    if (typeof _dados == "string") _dados = $.parseJSON(_dados);
                    this.#dados = _dados;
                    concluir(this.#dados.id_empresa);
                });
            } else {
                if (!EMPRESA) {
                    try {
                        if (TIPO == "A") primeiro = 0;
                    } catch(err) {}
                }
                concluir(primeiro);
            }
        });
    }

    mudaTitulo() {
        const administrador = !parseInt($("#pessoa-empresa-select").val());
        const supervisor = this.supervisor;
        let titulo = "";

        if (this.visitante) titulo = "visitante";
        else if (administrador) titulo = "administrador";
        else if (this.#usuario) titulo = "usuário";
        else if (supervisor) titulo = "supervisor";
        else titulo = "funcionário";

        $("#pessoasModal .linha-permissao").remove();
        $("#pessoasModalLabel").html((this.#id ? "Editando" : "Cadastrando") + " " + titulo);
        
        $("#pessoa-supervisor-lbl").html("Esse " + titulo + " pode usar sua senha para autorizar retiradas de produtos antes do vencimento.");
        this._ajustarSupervisor(this, titulo);
        $("#pessoa-supervisor-chk").prop("checked", supervisor ? true : false);
        $("#pessoa-supervisor").val(supervisor ? "1" : "0");

        const naoVisitante = (administrador || this.#usuario || supervisor);
        $("#pessoa-visitante-lbl").html("Essa pessoa é visitante.");
        $("#pessoa-visitante-chk").attr("disabled", naoVisitante ? true : false);
        if (naoVisitante) {
            this.visitante = false;
            $("#pessoa-visitante-lbl").attr("title", (administrador ? "Administradores" : this.#usuario ? "Usuários" : "Supervisores") + " não podem ser visitantes");
        } else $("#pessoa-visitante-lbl").removeAttr("title");

        let that = this;
        $("#pessoa-visitante-chk").off("change").on("change", function() {
            const marcado = $(this).prop("checked");
            that.visitante = marcado;
            $("#pessoa-visitante").val(marcado ? "1" : "0");
            that.mudaTitulo();
        });
        
        $("#pessoa-visitante-chk").prop("checked", this.visitante ? true : false);
        $("#pessoa-visitante").val(this.visitante ? "1" : "0");

        const htmlPermissoes = this._obterHtmlPermissoes(this.#usuario, titulo);
        $("#pessoasModal .container").append(htmlPermissoes);
        this._permissoesPreencher({ titulo: titulo });

        let id_usuario = 0;
        if (this.#dados !== undefined) id_usuario = parseInt(this.#dados.id_usuario);
        const _usuario = ["a", "u"].indexOf(titulo.charAt(0)) > -1;
        const mostrar_password = _usuario && (!this.#id || this.#id == USUARIO || !id_usuario);

        if (mostrar_password) {
            $($("#senha").parent()).removeClass("col-11").addClass("col-5");
            $($("#password").parent()).removeClass("d-none");
        } else {
            $($("#senha").parent()).removeClass("col-5").addClass("col-11");
            $($("#password").parent()).addClass("d-none");
        }
        $("#senha").attr("title", "Senha para retirar produtos " + (supervisor ? "e autorizar retiradas de produtos antes do vencimento" : ""));

        this.#obrigatorios = ["nome", "telefone", "cpf"];
        if (titulo.charAt(0) != "a") this.#obrigatorios.push("funcao", "admissao");
        if (_usuario) this.#obrigatorios.push("email");
        if (!this.#id) this.#obrigatorios.push("senha");
        if (_usuario && (!this.#id || !id_usuario)) this.#obrigatorios.push("password");

        $("#email-lbl").html(_usuario ? "Email: *" : "Email:");
        $("#senha-lbl").html(!this.#id ? "Senha numérica: *" : "Senha numérica: (deixe em branco para não alterar)");
        $("#password-lbl").html((_usuario && (!this.#id || !id_usuario)) ? "Senha alfanumérica: *" : "Senha alfanumérica: (deixe em branco para não alterar)");

        Array.from(document.querySelectorAll("#pessoasModal .row")).forEach((el) => {
            el.style.removeProperty("margin-top");
        });
        dimensionar_linhas();
        $("#pessoa-empresa-select").attr("disabled", this.#id == USUARIO ? true : false);
        $("#pessoa-setor-select").attr("disabled", this.#id == USUARIO ? true : false);
    }

    mudou_empresa(id_empresa, callback, manual = false) {
        id_empresa = parseInt(id_empresa);
        $("#pessoa-id_empresa").val(id_empresa);
        this.#id_empresa = id_empresa;
        if (id_empresa) {
            $.get(URL + "/empresas/setores/" + id_empresa, (data) => {
                if (typeof data == "string") data = $.parseJSON(data);
                let resultado = "";
                let primeiro = 0;
                data.forEach((setor) => {
                    let ativo = parseInt(setor.ativo);
                    resultado += "<option value = '" + setor.id + "'" + (!ativo ? " disabled" : "") + ">" + setor.descr + "</option>";
                    if (ativo) {
                        try {
                            if (!primeiro && parseInt(setor.cria_usuario) && ["A", "U"].indexOf(TIPO) > -1) primeiro = parseInt(setor.id);
                        } catch (err) {}
                        try {
                            if (!primeiro && parseInt(setor.supervisor) && !parseInt(setor.cria_usuario) && TIPO == "S") primeiro = parseInt(setor.id);
                        } catch (err) {}
                        try {
                            if (!primeiro && !parseInt(setor.supervisor) && !parseInt(setor.cria_usuario) && TIPO == "F") primeiro = parseInt(setor.id);
                        } catch (err) {}
                        try {
                            if (!primeiro && parseInt(setor.visitante) && TIPO == "V") primeiro = parseInt(setor.id);
                        } catch (err) {}
                    }
                });
                $("#pessoa-setor-select").html(resultado);
                $(".row-setor").removeClass("d-none");

                let setor = 0;
                if (this.#dados === undefined) setor = primeiro;
                else if (this.#dados.id_empresa == id_empresa) setor = parseInt(this.#dados.id_setor);
                
                if (setor) $("#pessoa-setor-select").val(setor);
                this.mudou_setor($("#pessoa-setor-select").val(), callback, manual);
            });
        } else {
            $(".row-setor").addClass("d-none");
            this.mudou_setor(0, callback, manual);
        }
    }

    mudou_setor(id_setor, callback, manual = false) {
        const concluir = (_cria_usuario) => {
            this.#htmlPermissoes = this._obterHtmlPermissoes(_cria_usuario);
            this.#usuario = _cria_usuario;
            this.mudaTitulo();
            if (callback !== undefined) callback();
        }

        id_setor = parseInt(id_setor);
        $("#id_setor").val(id_setor);
        this.#id_setor = id_setor;
        if (id_setor) {
            $.get(URL + "/setores/permissoes/" + id_setor, (data) => {
                if (typeof data == "string") data = $.parseJSON(data);
                if (manual || this.#dados === undefined) {
                    for (let x in permissoes) this.permissoesRascunho[x] = data[x] && permissoes[x];
                    this.supervisor = data.supervisor && SUPERVISOR;
                    this.visitante = data.visitante;
                }
                concluir(data.cria_usuario);
            });
        } else {
            if (manual || this.#dados === undefined) {
                for (let x in permissoes) this.permissoesRascunho[x] = permissoes[x];
                this.supervisor = SUPERVISOR;
                this.visitante = false;
            }
            concluir(true);
        }
    }

    mostrar_senha() {
        if (!this.#impedirMostrarSenha) {
            this.#impedirMostrarSenha = true;
            setTimeout(() => {
                this.#mostrandoSenha = !this.#mostrandoSenha;
                const concluir = (senha, voltar) => {
                    $("#senha").attr("type", this.#mostrandoSenha ? "text" : "password");
                    $("#senha").val(senha);
                    $("#mostrar_senha").removeClass(this.#mostrandoSenha ? "fa-eye-slash" : "fa-eye").addClass(this.#mostrandoSenha ? "fa-eye" : "fa-eye-slash");
                    if (!this.#mostrandoSenha) this.#impedirMostrarSenha = false;
                    if (voltar) {
                        setTimeout(() => {
                            this.#impedirMostrarSenha = false;
                            this.mostrar_senha();
                        }, 2000);
                    }
                }
                if (this.#mostrandoSenha) {
                    if (this.#id) {
                        $.post(URL + "/colaboradores/senha", {
                            _token: $("meta[name='csrf-token']").attr("content"),
                            id: this.#id
                        }, (data) => {
                            concluir(data, true);
                        });
                    } else concluir($("#senha").val(), true);
                } else concluir(this.#id ? "" : $("#senha").val(), false);
            }, 10);
        }
    }

    validar() {
        limpar_invalido();
        let erro = "";

        if (this.#usuario && !$("#email").val()) {
            erro = "Preencha o campo";
            $("#email").addClass("invalido");
        }
        if (!$("#cpf").val()) {
            if (!erro) erro = "Preencha o campo";
            else erro = "Preencha os campos";
            $("#cpf").addClass("invalido");
        }

        const aux = verifica_vazios(this.#obrigatorios, erro);
        erro = aux.erro;
        let alterou = aux.alterou;

        if (this.#usuario) {
            if (!erro && !this.#validar_email($("#email").val())) {
                erro = "E-mail inválido";
                $("#email").addClass("invalido");
            }
            if ($("#password").val() || $("#email").val().toLowerCase() != anteriores.email.toLowerCase()) alterou = true;
        } else if ($("#senha").val()) alterou = true;

        if (
            $("#pessoa-setor-select").val() != this.#ant_id_setor ||
            $("#pessoa-empresa-select").val() != this.#ant_id_empresa ||
            $("#pessoa-supervisor").val() != this.#ant_supervisor ||
            $("#pessoa-visitante").val() != this.#ant_visitante
        ) alterou = true;
        for (let x in permissoes) {
            if ($("#" + permissoes[x]).val() != anteriores[permissoes[x]]) alterou = true;
        }

        if ($("#admissao").val()) {
            if (!erro && eFuturo($("#admissao").val())) {
                erro = "A admissão não pode ser no futuro";
                $("#admissao").addClass("invalido");
            }
        }

        if (apenasNumeros($("#cpf").val()).length != 11) {
            erro = "CPF inválido";
            $("#cpf").addClass("invalido");
        }

        $.get(URL + "/colaboradores/consultar/", {
            cpf: apenasNumeros($("#cpf").val()),
            email: $("#email").val(),
            id: this.#id
        }, (data) => {
            if (typeof data == "string") data = $.parseJSON(data);
            if (!erro && data.tipo == "duplicado") {
                erro = "Já existe um registro com esse " + data.dado;
                $("#" + data.dado.replace("-", "").toLowerCase()).addClass("invalido");
            }
            if (!erro && !alterou && !document.querySelector("#pessoasModal input[type=file]").value) erro = "Altere pelo menos um campo para salvar";
            if (!erro) {
                $("#pessoa-empresa-select").attr("disabled", false);
                $("#pessoa-setor-select").attr("disabled", false);
                $("#telefone").val(apenasNumeros($("#telefone").val()));
                $("#cpf").val(apenasNumeros($("#cpf").val()));
                $("#pessoasModal form").submit();
            } else s_alert(erro);
        });
    }
}

class Setor extends JanelaDinamica {
    #id;
    #dados = [];

    constructor(_id) {
        super({
            prefixo: 'setor',
            modalId: '#setoresModal'
        });
        this.#id = _id;

        $("#setoresModalLabel").html((this.#id ? "Editando" : "Cadastrando") + " centro de custo");

        if (this.#id) {
            $.get(URL + "/setores/mostrar/" + this.#id, (_dados) => {
                if (typeof _dados == "string") _dados = $.parseJSON(_dados);
                this.#dados = _dados;
                $("#descr").val(this.#dados.descr);
                $("#setor-id_empresa").val(this.#dados.id_empresa);
                $("#setor-empresa").val(this.#dados.empresa);
                ["cria_usuario", "setor-supervisor", "setor-visitante"].forEach((id_el) => {
                    let chave = id_el.replace("setor-", "");
                    $("#" + id_el + "-chk").prop("checked", parseInt(this.#dados[chave]) ? true : false);
                    $("#" + id_el).val(this.#dados[chave]);
                    if (id_el != "cria_usuario") this[chave] = parseInt(this.#dados[chave]) ? true : false;
                });
                for (let x in permissoes) this.permissoesRascunho[x] = parseInt(this.#dados[x]) ? true : false;
                this.#mostrar();
            });
        } else {
            this.#dados = {
                sistema: "0",
                meu_setor: "0",
                pessoas: "0",
                usuarios: "0"
            };
            ["cria_usuario", "setor-supervisor", "setor-visitante"].forEach((id_el) => {
                $("#" + id_el + "-chk").prop("checked", false);
                $("#" + id_el).val("0");
            });
            this.#mostrar();
        }
    }

    #mostrar() {
        const setorDoSistema = parseInt(this.#dados.sistema);
        const pessoas = parseInt(this.#dados.pessoas);
        const usuarios = parseInt(this.#dados.usuarios);
        const meuSetor = parseInt(this.#dados.meu_setor);

        $("#setor-empresa").attr("disabled", (setorDoSistema || pessoas) ? true : false);
        if (setorDoSistema) $("#setor-empresa").attr("title", "Não é possível alterar a empresa de um centro de custo do sistema");
        else if (pessoas) $("#setor-empresa").attr("title", "Não é possível alterar a empresa desse centro de custo porque existem pessoas vinculadas a ele");
        else $("#setor-empresa").removeAttr("title");

        $("#setor-supervisor-lbl").html("Pessoas nesse centro de custo podem, por padrão, usar suas senhas para autorizar retiradas de produtos antes do vencimento.");
        this._ajustarSupervisor(this);
        $("#setor-supervisor-chk").attr("disabled", setorDoSistema ? true : false);
        if (setorDoSistema) $("#setor-supervisor-lbl").attr("title", "Não é permitido alterar essa configuração em um setor do sistema");

        $("#setor-visitante-lbl").html("Pessoas nesse centro de custo são, por padrão, visitantes.");
        $("#setor-visitante-chk").attr("disabled", (setorDoSistema || usuarios) ? true : false);
        if (setorDoSistema) $("#setor-visitante-lbl").attr("title", "Não é permitido alterar essa configuração em um setor do sistema");
        else if (usuarios) $("#setor-visitante-lbl").attr("title", "Não é permitido alterar essa configuração de um setor que possui usuários");
        else $("#setor-visitante-lbl").removeAttr("title");

        let that = this;
        $("#setor-visitante-chk").off("change").on("change", function() {
            const marcado = $(this).prop("checked");
            if (marcado) {
                $("#cria_usuario-chk").prop("checked", false);
                $("#setor-supervisor-chk").prop("checked", false).trigger("change");
            }
            that.muda_cria_usuario();
            $("#cria_usuario-chk").attr("disabled", (!permissoes.usuarios || setorDoSistema || meuSetor || marcado) ? true : false);
            if (meuSetor) $("#cria_usuario-lbl").attr("title", "Alterar essa opção apagaria seu usuário");
            else if (setorDoSistema) $("#cria_usuario-lbl").attr("title", "Não é permitido alterar essa configuração em um setor do sistema");
            else if (!permissoes.usuarios) $("#cria_usuario-lbl").attr("title", "Não é possível " + ($("#cria_usuario-chk").prop("checked") ? "retirar d" : "atribuir a") + "esse centro de custo permissões que seu usuário não tem");
            else if (marcado) $("#cria_usuario-lbl").attr("title", "Não é permitido alterar essa configuração em um setor de visitantes");
            else $("#cria_usuario-lbl").removeAttr("title");

            $("#setor-supervisor-chk").attr("disabled", (!SUPERVISOR || setorDoSistema || marcado) ? true : false);
            if (setorDoSistema) $("#setor-supervisor-lbl").attr("title", "Não é permitido alterar essa configuração em um setor do sistema");
            else if (!SUPERVISOR) $("#setor-supervisor-lbl").attr("title", "Não é possível " + ($("#setor-supervisor-chk").prop("checked") ? "retirar d" : "atribuir a") + "esse centro de custo permissões que seu usuário não tem");
            else if (marcado) $("#setor-supervisor-lbl").attr("title", "Não é permitido alterar essa configuração em um setor de visitantes");
            else $("#setor-supervisor-lbl").removeAttr("title");

            that.visitante = marcado;
            $("#setor-visitante").val(marcado ? "1" : "0");
        });

        $("#setor-visitante-chk").trigger("change");
        modal("setoresModal", this.#id);
    }

    validar() {
        limpar_invalido();

        const _descr = $("#descr").val().toUpperCase().trim();
        const _empresa = $("#setor-empresa").val();
        let erro;

        let lista = ["descr"];
        $(".validar").each(function() {
            lista.push($(this).attr("id"));
        });
        erro = verifica_vazios(lista).erro;

        let alterouPermissoes = false;
        for (let x in permissoes) {
            if ($("#" + permissoes[x]).val() != anteriores[permissoes[x]]) alterouPermissoes = true;
        }

        if (
            parseInt($("#id").val()) &&
            !erro &&
            !alterouPermissoes &&
            $("#cria_usuario").val() == anteriores.cria_usuario &&
            $("#setor-supervisor").val() == anteriores["setor-supervisor"] &&
            $("#setor-visitante").val() == anteriores["setor-visitante"] &&
            _descr == anteriores.descr.toUpperCase().trim() &&
            _empresa.toUpperCase().trim() == anteriores["setor-empresa"].toUpperCase().trim()
        ) erro = "Não há alterações para salvar";

        $.get(URL + "/setores/consultar", {
            id : $("#id").val(),
            descr: _descr,
            id_empresa: $("#setor-id_empresa").val(),
            empresa: _empresa
        }, (data) => {
            if (typeof data == "string") data = $.parseJSON(data);
            if (data.msg && !erro) {
                erro = data.msg;
                $("#" + data.el).addClass("invalido");
            }
            if (!erro) {
                $(".telefone").each(function() {
                    $(this).val(apenasNumeros($(this).val()));
                });
                $("#setoresModal form").submit();
            } else s_alert(erro);
        });
    }

    muda_cria_usuario() {
        let obter_campo_senha = (_i, _nome) => {
            return "<input type = 'password' name = 'password[]' class = 'validar form-control' id = 'senha-" + _i + "' placeholder = 'Senha de " + _nome + "' />";
        };

        const concluir = (texto = "") => {
            const setorDoSistema = parseInt(this.#dados.sistema);
            if (texto) $("#setoresModal > .modal-dialog").addClass("modal-xl modal-xl-kx").removeClass("modal-lg");
            else $("#setoresModal > .modal-dialog").removeClass("modal-xl modal-xl-kx").addClass("modal-lg");

            $("#setoresModal .linha-permissao, #setoresModal .linha-usuario").remove();

            const htmlPermissoes = this._obterHtmlPermissoes($("#cria_usuario-chk").prop("checked"));
            $("#setoresModal .container").append(texto + htmlPermissoes);

            $("#setoresModal .linha-usuario:last-child").addClass("mb-4");
            this._permissoesPreencher({
                ehSetorSistema: !!setorDoSistema,
                titulo: 'centro de custo'
            });

            atualizarChk("cria_usuario", true);
        };

        if (!this.#id) concluir();

        $("#setor-visitante-chk").attr("disabled", $("#cria_usuario-chk").prop("checked"));
        
        if ($("#cria_usuario-chk").prop("checked")) {
            $("#setor-visitante-lbl").attr("title", "Usuários não podem ser visitantes");
            if (!this.#id) return;
            $.get(URL + "/setores/pessoas/" + this.#id, (data) => {
                if (typeof data == "string") data = $.parseJSON(data);
                const mostrar_email = parseInt(data.mostrar_email);
                const mostrar_fone = parseInt(data.mostrar_fone);
                let resultado = "";
                for (let i = 1; i <= data.consulta.length; i++) {
                    let pessoa = data.consulta[i - 1];
                    let nome = pessoa.nome;

                    resultado += "<div class = 'row linha-usuario mb-2" + (i == 1 ? " mt-3" : "") + "'>" +
                        "<input type = 'hidden' name = 'id_pessoa[]' value = '" + pessoa.id + "' />";
                    let campo_email = "<input type = 'text' name = 'email[]' class = 'validar form-control' id = 'email-" + i + "' placeholder = 'Email de " + nome + "' value = '" + pessoa.email + "' />";
                    let campo_fone = "<input type = 'text' name = 'phone[]' class = 'validar telefone form-control' id = 'phone-" + i + "' placeholder = 'Telefone de " + nome + "' value = '" + pessoa.telefone + "' onkeyup = 'this.value=phoneMask(this.value)' />";
                    if (mostrar_email && mostrar_fone) {
                        resultado += "<div class = 'col-4 pr-1'>" +
                            campo_email +
                        "</div>" +
                        "<div class = 'col-4 px-1'>" +
                            campo_fone +
                        "</div>" +
                        "<div class = 'col-4 pl-1'>" +
                            obter_campo_senha(i, nome) +
                        "</div>";
                    } else if (mostrar_email) {
                        resultado += "<div class = 'col-6 pr-1'>" +
                            campo_email +
                        "</div>" +
                        "<div class = 'col-6 pl-1'>" +
                            obter_campo_senha(i, nome) +
                        "</div>";
                    } else if (mostrar_fone) {
                        resultado += "<div class = 'col-4 pr-1'>" +
                            campo_fone +
                        "</div>" +
                        "<div class = 'col-4 pl-1'>" +
                            obter_campo_senha(i, nome) +
                        "</div>";
                    } else {
                        resultado += "<div class = 'col-12'>" +
                            obter_campo_senha(i, nome) +
                        "</div>";
                    }
                    resultado += "</div>";
                }
                concluir(resultado);
            });
        } else {
            $("#setor-visitante-lbl").removeAttr("title");
            if (!this.#id) return;
            $.get(URL + "/setores/usuarios/" + this.#id, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                let resultado = "";
                for (let i = 1; i <= data.length; i++) {
                    resultado += "<div class = 'row linha-usuario mb-2" + (i == 1 ? " mt-3" : "") + "'>" +
                        "<input type = 'hidden' name = 'id_pessoa[]' value = '" + data[i - 1].id + "' />" +
                        "<div class = 'col-12'>" +
                            obter_campo_senha(i, data[i - 1].nome) +
                        "</div>" +
                    "</div>";
                }
                concluir(resultado);
            });
        }
    }
}