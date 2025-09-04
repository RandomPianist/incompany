function Pessoa(_id) {
    let that = this;
    let ant_id_setor, ant_id_empresa, validaUsuario;

    let tipo = function() {
        return !parseInt($("#pessoa-empresa-select").val()) ? "administrador" : "usuário";
    }

    this.toggle_user = function(setor) {
        $.get(URL + "/setores/mostrar/" + setor, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            $(".usr-info").each(function() {
                if (parseInt(data.cria_usuario)) $(this).removeClass("d-none");
                else $(this).addClass("d-none");
            });
            let palavras = $("#pessoasModalLabel").html().split(" ");
            if (parseInt(data.cria_usuario)) {
                $("#pes-info").addClass("d-none");
                $("#line-pass").addClass("d-none");
                $("#line-sup").addClass("mb-3");
                palavras[1] = tipo();
                validaUsuario = true;
            } else {
                $("#pes-info").removeClass("d-none");
                $("#line-pass").removeClass("d-none");
                $("#line-sup").removeClass("mb-3");
                palavras[1] = parseInt($("#supervisor").val()) ? "supervisor" : "funcionário";
                validaUsuario = false;
            }
            $("#pessoasModalLabel").html(palavras.join(" "));
        })
    }

    this.setorPorEmpresa = function(callback) {
        $.get(URL + "/setores/por-empresa/" + $("#pessoa-empresa-select").val(), function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            let resultado = "<option value = '0'>--</option>";
            data.forEach((setor) => {
                resultado += "<option value = '" + setor.id + "'>" + setor.descr + "</option>";
            });
            $("#pessoa-setor-select").html(resultado);
            if (TIPO == "A" || TIPO == "U") {
                $.get(URL + "/setores/primeiro-admin/" + $("#pessoa-empresa-select").val(), function(resp) {
                    if (typeof resp == "string") resp = $.parseJSON(resp);
                    $("#pessoa-setor-select").val(resp.id);
                    that.toggle_user(resp.id);
                    if (callback !== undefined) callback();
                });
            } else {
                that.toggle_user(0);
                if (callback !== undefined) callback();
            }
        })
    }

    this.toggle_emp = function() {
        that.setorPorEmpresa(function() {
            $("#cpf").focus();
        });
    }

    this.mostrar_senha = function() {
        setTimeout(function() {
            const chk = $("#mostrar-senha-chk").prop("checked");
            const concluir = function(senha) {
                $("#senha").attr("type", chk ? "text" : "password");
                $("#senha").val(senha);
            }
            if (chk) {
                if (_id) {
                    $.post(URL + "/colaboradores/senha", {
                        _token : $("meta[name='csrf-token']").attr("content"),
                        id : _id 
                    }, function(data) {
                        concluir(data);
                        setTimeout(function() {
                            $("#mostrar-senha-chk").prop("checked", false);
                            that.mostrar_senha();
                        }, 2000);
                    });
                } else concluir($("#senha").val());
            } else concluir("");
        }, 10);
    }

    this.toggle_sup = function() {
        $("#supervisor").val($(this).prop("checked") ? "1" : "0");
        that.toggle_user($("#pessoa-setor-select").val());
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

        if (validaUsuario && !$("#email").val()) {
            erro = "Preencha o campo";
            $("#email").addClass("invalido");
        }
        if (!$("#cpf").val()) {
            if (!erro) erro = "Preencha o campo";
            else erro = "Preencha os campos";
            $("#cpf").addClass("invalido");
        }
        let lista = ["nome", "funcao", "admissao", "supervisor"];
        if (!_id) lista.push(validaUsuario ? "password" : "senha");
        const aux = verifica_vazios(lista, erro);
        erro = aux.erro;
        let alterou = aux.alterou;
        if ($("#pessoa-setor-select").val() == "0") {
            if (!erro) erro = "Preencha o campo";
            else erro = "Preencha os campos";
            $("#pessoa-setor-select").addClass("invalido");
        }
        if (validaUsuario) {
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
            cpf : $("#cpf").val().replace(/\D/g, ""),
            email : $("#email").val(),
            id : _id,
            id_setor : $("#pessoa-setor-select").val()
        }, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            if (!erro && data.tipo == "duplicado") {
                erro = "Já existe um registro com esse " + data.dado;
                $("#" + data.dado.replace("-", "").toLowerCase()).addClass("invalido");
            }
            if (!erro && data.tipo == "permissao") erro = "Você não tem permissão para " + (_id ? "editar esse" : "criar um") + " " + tipo();
            if (!erro && !alterou && !document.querySelector("#pessoasModal input[type=file]").value) erro = "Altere pelo menos um campo para salvar";
            if (!erro) {
                $("#pessoa-setor-select").attr("disabled", false);
                $("#cpf").val($("#cpf").val().replace(/\D/g, ""));
                $("#pessoasModal form").submit();
            } else s_alert(erro);
        });
    }

    $("#pessoasModalLabel").html((_id ? "Editando" : "Cadastrando") + " colaborador");
    let estilo_bloco_senha = document.getElementById("password").parentElement.parentElement.style;
    if (_id) {
        $.get(URL + "/colaboradores/mostrar/" + _id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            $("#nome, #cpf, #pessoa-setor-select, #pessoa-empresa-select, #email, #funcao, #admissao, #supervisor").each(function() {
                $(this).val(data[$(this).attr("id").replace("pessoa-", "id_").replace("-select", "")]);
            });
            ant_id_setor = parseInt($("#pessoa-setor-select").val());
            ant_id_empresa = parseInt($("#pessoa-empresa-select").val());
            setTimeout(function() {
                modal("pessoasModal", _id, function() {
                    that.toggle_user(parseInt(data.id_setor));
                    estilo_bloco_senha.display = id != USUARIO && validaUsuario ? "none" : "";
                    $("#pessoa-setor-select").attr("disabled", _id == USUARIO);
                    $("#supervisor-chk").prop("checked", parseInt(data.supervisor) == 1);
                    $(".pessoa-senha").each(function() {
                        $(this).html("Senha:");
                    });
                    $("#pessoa-setor-select").val(data.id_setor);
                    $("#pessoa-empresa-select").val(data.id_empresa);
                    that.toggle_user(data.id_setor);
                    $($("#pessoasModal .user-pic").parent()).removeClass("d-none");
                    if (!data.foto) {
                        let nome = data.nome.toUpperCase().replace("DE ", "").split(" ");
                        iniciais = "";
                        iniciais += nome[0][0];
                        if (nome.length > 1) iniciais += nome[nome.length - 1][0];
                        $("#pessoasModal .user-pic span").html(iniciais);
                    }
                    foto_pessoa("#pessoasModal .user-pic", data.foto ? data.foto : "");
                });
            }, 0);
        });
    } else {
        setTimeout(function() {
            modal("pessoasModal", 0, function() {
                estilo_bloco_senha.removeProperty("display");
                $("#pessoa-setor-select").attr("disabled", false);
                $(".pessoa-senha").each(function() {
                    $(this).html("Senha: *");
                });
                $($("#pessoasModal .user-pic").parent()).removeClass("d-none");
                $("#supervisor-chk").prop("checked", TIPO == "S");
                $("#supervisor").val(TIPO == "S" ? "1" : "0");

                if (TIPO == "A" || TIPO == "U") {
                    $.get(URL + "/setores/primeiro-admin/" + $("#pessoa-empresa-select").val(), function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        $("#pessoa-setor-select").val(data.id);
                        that.toggle_user(data.id);
                    });
                } else that.toggle_user(0);
            });
        }, 0);
    }
}