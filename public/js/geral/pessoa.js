function Pessoa(_id) {
    let that = this;
    let carregando = true;
    let mostrandoSenha = false;
    let impedirMostrarSenha = false;
    let dados, htmlPermissoes, usuario, ant_id_setor, ant_id_empresa;

    this.permissoesRascunho = new Array();

    this.mudaTitulo = function() {
        if (that.permissoesRascunho.supervisor !== undefined) var supervisor = that.permissoesRascunho.supervisor;
        else var supervisor = false;
        let titulo = "";
        if (!parseInt($("#pessoa-empresa-select").val())) titulo = "administrador";
        else if (usuario) titulo = "usuário";
        else if (supervisor) titulo = "supervisor";
        else titulo = "funcionário";
        $("#pessoasModalLabel").html((_id ? "Editando" : "Cadastrando") + " " + titulo);
        $("#pessoasModal .container").append(htmlPermissoes.replaceAll("??", titulo));
        permissoesListeners(false);
        for (x in permissoes) {
            if (that.permissoesRascunho[x] !== undefined) var permissao = that.permissoesRascunho[x];
            else var permissao = false;
            $("#pessoa-" + x + "-chk").prop("checked", permissao).attr("disabled", !permissoes[x]);
            if (permissoes[x]) $("#pessoa-" + x + "-lbl").removeAttr("title");
            else ("#pessoa-" + x + "-lbl").attr("title", "Não é possível atribuir a esse " + titulo + " permissões que seu usuário não tem");
        }
        let id_usuario = 0;
        if (dados !== undefined) id_usuario = parseInt(dados.id_usuario);
        const _usuario = ["a", "u"].indexOf(titulo.charAt(0)) > -1;
        const mostrar_password = _usuario && (!_id || _id == USUARIO || !id_usuario);
        if (mostrar_password) {
            $($("#senha").parent()).removeClass("col-11").addClass("col-5");
            $($("#password").parent()).removeClass("d-none");
        } else {
            $($("#senha").parent()).removeClass("col-5").addClass("col-11");
            $($("#password").parent()).addClass("d-none");
        }
        $("#senha").attr("title", "Senha para retirar produtos " + (supervisor ? "e autorizar retiradas de produtos antes do vencimento" : ""));
        if (_usuario && _id && _id != USUARIO && id_usuario) $(".row-senha").addClass("d-none");
        else $(".row-senha").removeClass("d-none");
    }

    this.mudou_empresa = function(id_empresa, callback) {
        id_empresa = parseInt(id_empresa);
        if (id_empresa) {
            $.get(URL + "/empresas/setores/" + id_empresa, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                let resultado = "";
                let primeiro = 0;
                data.forEach((setor) => {
                    resultado += "<option value = '" + setor.id + "'" + (!parseInt(setor.ativo) ? " disabled" : "") + ">" + setor.descr + "</option>";
                    try {
                        if (parseInt(setor.cria_usuario) && ["A", "U"].indexOf(TIPO) > -1) primeiro = parseInt(setor.id);
                    } catch(err) {}
                    try {
                        if (parseInt(setor.supervisor) && TIPO == "S") primeiro = parseInt(setor.id);
                    } catch(err) {}
                });
                $("#pessoa-setor-select").html(resultado);
                $(".row-setor").each(function() {
                    $(this).removeClass("d-none");
                });
                let setor = 0;
                if (dados === undefined) setor = primeiro;
                else if (dados.id_empresa == id_empresa) setor = parseInt(dados.id_setor);
                if (setor) $("#pessoa-setor-select").val(setor);
                that.mudou_setor($("#pessoa-setor-select").val(), callback);
            });
        } else {
            $(".row-setor").each(function() {
                $(this).addClass("d-none");
            });
            that.mudou_setor(0, callback);
        }
    }

    this.mudou_setor = function(id_setor, callback) {
        const concluir = function() {
            that.mudaTitulo();
            if (callback !== undefined) callback();
        }

        id_setor = parseInt(id_setor);
        $("#id_setor").val(id_setor);
        if (id_setor) {
            $.get(URL + "/setores/permissoes/" + id_setor, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                if (!carregando) {
                    for (x in permissoes) that.permissoesRascunho[x] = data[x] && permissoes[x];
                }
                htmlPermissoes = obterHtmlPermissoes(false, data.cria_usuario);
                usuario = data.cria_usuario;
                concluir();
            });
        } else {
            if (!carregando) {
                for (x in permissoes) that.permissoesRascunho[x] = permissoes[x];
            }
            htmlPermissoes = obterHtmlPermissoes(false, true);
            usuario = true;
            concluir();
        }
    }

    this.mostrar_senha = function() {
        if (!impedirMostrarSenha) {
            impedirMostrarSenha = true;
            setTimeout(function() {
                mostrandoSenha = !mostrandoSenha;
                const concluir = function(senha) {
                    $("#senha").attr("type", mostrandoSenha ? "text" : "password");
                    $("#senha").val(senha);
                    $("#mostrar_senha").removeClass(mostrandoSenha ? "fa-eye-slash" : "fa-eye").addClass(mostrandoSenha ? "fa-eye" : "fa-eye-slash");
                    if (!mostrandoSenha) impedirMostrarSenha = false;
                }
                if (mostrandoSenha) {
                    if (_id) {
                        $.post(URL + "/colaboradores/senha", {
                            _token : $("meta[name='csrf-token']").attr("content"),
                            id : _id 
                        }, function(data) {
                            concluir(data);
                            setTimeout(function() {
                                impedirMostrarSenha = false;
                                that.mostrar_senha();
                            }, 2000);
                        });
                    } else concluir($("#senha").val());
                } else concluir("");
            }, 10);
        }
    }

    this.validar = function() {
        let validar_email = function(__email) {
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

        limpar_invalido();
        let erro = "";

        if (usuario && !$("#email").val()) {
            erro = "Preencha o campo";
            $("#email").addClass("invalido");
        }
        if (!$("#cpf").val()) {
            if (!erro) erro = "Preencha o campo";
            else erro = "Preencha os campos";
            $("#cpf").addClass("invalido");
        }
        let lista = ["nome", "funcao", "admissao", "supervisor", "telefone"];
        if (!_id) lista.push(usuario ? "password" : "senha");
        const aux = verifica_vazios(lista, erro);
        erro = aux.erro;
        let alterou = aux.alterou;
        if (usuario) {
            if (!erro && !validar_email($("#email").val())) {
                erro = "E-mail inválido";
                $("#email").addClass("invalido");
            }
            if (
                $("#password").val() ||
                $("#email").val().toLowerCase() != anteriores.email.toLowerCase()
            ) alterou = true;
        } else if ($("#senha").val()) alterou = true;
        if (!erro && $("#pessoa-setor-select").val() != ant_id_setor) alterou = true;
        if (!erro && $("#pessoa-empresa-select").val() != ant_id_empresa) alterou = true;
        if ($("#admissao").val()) {
            if (!erro && eFuturo($("#admissao").val())) {
                erro = "A admissão não pode ser no futuro";
                $("#admissao").addClass("invalido");
            }
        }
        $.get(URL + "/colaboradores/consultar/", {
            cpf : apenasNumeros($("#cpf").val()),
            email : $("#email").val(),
            id : _id
        }, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            if (!erro && data.tipo == "duplicado") {
                erro = "Já existe um registro com esse " + data.dado;
                $("#" + data.dado.replace("-", "").toLowerCase()).addClass("invalido");
            }
            if (!erro && !alterou && !document.querySelector("#pessoasModal input[type=file]").value) erro = "Altere pelo menos um campo para salvar";
            if (!erro) {
                $("#pessoa-setor-select").attr("disabled", false);
                $("#telefone").val(apenasNumeros($("#telefone").val()));
                $("#cpf").val(apenasNumeros($("#cpf").val()));
                $("#pessoasModal form").submit();
            } else s_alert(erro);
        });
    }

    $.get(URL + "/empresas/minhas", function(minhasEmpresas) {
        const concluir = function() {
            that.mudou_empresa($("#pessoa-empresa-select").val(), function() {
                if (dados !== undefined) {
                    $("#nome, #cpf, #email, #funcao, #admissao, #telefone").each(function() {
                        $(this).val(dados[$(this).attr("id")]).trigger("keyup");
                    });
                    $($("#pessoasModal .user-pic").parent()).removeClass("d-none");
                    if (!dados.foto) {
                        let nome = dados.nome.toUpperCase().replace("DE ", "").split(" ");
                        iniciais = "";
                        iniciais += nome[0][0];
                        if (nome.length > 1) iniciais += nome[nome.length - 1][0];
                        $("#pessoasModal .user-pic span").html(iniciais);
                    }
                    foto_pessoa("#pessoasModal .user-pic", dados.foto ? dados.foto : "");
                    ant_id_empresa = parseInt(dados.id_empresa);
                    ant_id_setor = parseInt(dados.id_setor);
                    for (x in permissoes) that.permissoesRascunho[x] = parseInt(dados[x]) ? true : false;
                    htmlPermissoes = obterHtmlPermissoes(false, parseInt(dados.cria_usuario));
                    that.mudaTitulo();
                } else {
                    ant_id_empresa = 0;
                    ant_id_setor = 0;
                    $($("#pessoasModal .user-pic").parent()).removeClass("d-none");
                }
                modal("pessoasModal", _id, function() {
                    carregando = false;
                });
            });
        }

        if (typeof minhasEmpresas == "string") minhasEmpresas = $.parseJSON(minhasEmpresas);
        let resultado = !EMPRESA ? "<option value = '0'>--</option>" : "";
        minhasEmpresas.empresas.forEach((empresa) => {
            resultado += "<option value = '" + empresa.id + "'" + (minhasEmpresas.filial == "S" ? " disabled" : "") + ">" + empresa.nome_fantasia + "</option>";
            empresa.filiais.forEach((filial) => {
                resultado += "<option value = '" + filial.id + "'>- " + filial.nome_fantasia + "</option>";
            });
        });
        $("#pessoa-empresa-select").html(resultado);
        if (_id) {
            $.get(URL + "/colaboradores/mostrar/" + _id, function(_dados) {
                if (typeof _dados == "string") _dados = $.parseJSON(_dados);
                dados = _dados;
                $("#pessoa-empresa-select").val(dados.id_empresa);
                concluir();
            });
        } else concluir();
    })
}