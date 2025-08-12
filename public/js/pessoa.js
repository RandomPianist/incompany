function Pessoa(id) {
    let that = this;
    let ant_id_setor = 0;
    let ant_id_empresa = 0;

    this.toggle_user = function(setor) {
        $.get(URL + "/setores/mostrar/" + setor, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            Array.from(document.getElementsByClassName("usr-info")).forEach((el) => {
                if (parseInt(data.cria_usuario)) el.classList.remove("d-none");
                else el.classList.add("d-none");
            });
            let pes_info = document.getElementById("pes-info").classList;
            let palavras = document.getElementById("pessoasModalLabel").innerHTML.split(" ");
            if (parseInt(data.cria_usuario)) {
                pes_info.add("d-none");
                palavras[1] = "administrador";
            } else {
                pes_info.remove("d-none");
                palavras[1] = "colaborador";
            }
            document.getElementById("pessoasModalLabel").innerHTML = palavras.join(" ");
        })
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

        that.alterarEmpresa(function() {
            that.alterarSetor(function() {
                limpar_invalido();
                let erro = "";

                const id_setor = document.getElementById("pessoa-id_setor").value;

                let _email = document.getElementById("email");
                let _cpf = document.getElementById("cpf");

                $.get(URL + "/setores/mostrar/" + id_setor, function(data) {
                    if (typeof data == "string") data = $.parseJSON(data);
                    if (parseInt(data.cria_usuario)) {
                        if (!_email.value) {
                            erro = "Preencha o campo";
                            _email.classList.add("invalido");
                        }
                    }
                    if (!_cpf.value) {
                        if (!erro) erro = "Preencha o campo";
                        else erro = "Preencha os campos";
                        _cpf.classList.add("invalido");
                    }
                    let lista = ["nome", /*"pessoa-setor", "pessoa-empresa", */"funcao", "admissao", "supervisor"];
                    if (!parseInt(document.getElementById("pessoa-id").value)) lista.push(parseInt(data.cria_usuario) ? "password" : "senha");
                    let aux = verifica_vazios(lista, erro);
                    erro = aux.erro;
                    let alterou = aux.alterou;
                    
                    if (parseInt(data.cria_usuario)) {
                        if (!erro && !validar_email(_email.value)) {
                            erro = "E-mail inválido";
                            _email.classList.add("invalido");
                        }
                        if (
                            document.getElementById("password").value ||
                            _email.value.toLowerCase() != anteriores.email.toLowerCase()
                        ) alterou = true;
                    } else if (document.getElementById("senha").value) alterou = true;
                    
                    // if (!erro && !validar_cpf(_cpf.value)/* && _cpf.value.trim()*/) {
                    //     erro = "CPF inválido";
                    //     _cpf.classList.add("invalido");
                    // }
                    // if (_cpf.value != anteriores.cpf) alterou = true;
                    
                    if (!erro && document.getElementById("pessoa-id_setor").value != ant_id_setor) alterou = true;
                    if (!erro && document.getElementById("pessoa-id_empresa").value != ant_id_empresa) alterou = true;

                    aux = document.getElementById("admissao").value;
                    if (aux) {
                        if (!erro && eFuturo(aux)) erro = "A admissão não pode ser no futuro";
                    }
                    
                    let _id_setor = document.getElementById("pessoa-id_setor").value;
                    $.get(URL + "/colaboradores/consultar/", {
                        cpf : _cpf.value.replace(/\D/g, ""),
                        email : _email.value,
                        empresa : document.getElementById("pessoa-empresa").value,
                        id_empresa : document.getElementById("pessoa-id_empresa").value.replace("-", "").trim(),
                        setor : document.getElementById("pessoa-setor").value,
                        id_setor : _id_setor
                    }, function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        let id_pessoa = parseInt(document.getElementById("pessoa-id").value);
                        if (!erro && data.tipo == "invalido") {
                            erro = data.dado + " não encontrad" + (data.dado == "Empresa" ? "a" : "o");
                            document.getElementById("pessoa-" + data.dado.toLowerCase() + "-select").classList.add("invalido");
                        }
                        if (!erro && data.tipo == "duplicado" && !id_pessoa) {
                            erro = "Já existe um registro com esse " + data.dado;
                            document.getElementById(data.dado == "CPF" ? "cpf" : "email").classList.add("invalido");
                        }
                        if (!erro && !alterou && !document.querySelector("#pessoasModal input[type=file]").value) erro = "Altere pelo menos um campo para salvar";
                        if (!erro) {
                            $.get(URL + "/colaboradores/consultar2", {
                                id : id_pessoa,
                                id_setor : _id_setor
                            }, function(ret) {
                                if (parseInt(ret)) {
                                    _cpf.value = _cpf.value.replace(/\D/g, "");
                                    document.querySelector("#pessoasModal form").submit();
                                } else s_alert("Você não tem permissão para " + (id_pessoa ? "editar" : "criar") + " esse administrador");
                            });
                        } else s_alert(erro.replace("Setor", "Centro de custo"));
                    });
                });
            });
        });
    }

    this.alterarEmpresa = function(callback) {
        setTimeout(function() {
            try {
                document.getElementById("pessoa-empresa").value = document.querySelector("#pessoa-empresa-select option[value='" + document.getElementById("pessoa-id_empresa").value + "']").innerHTML;
                document.getElementById("pessoa-id_empresa").value = document.getElementById("pessoa-empresa-select").value;
            } catch(err) {
                document.getElementById("pessoa-empresa").value = "--";
                document.getElementById("pessoa-empresa-select").value = "0";
                document.getElementById("pessoa-id_empresa").value = 0;
            }
            if (callback !== undefined) callback();
        }, 0);
    }

    this.alterarSetor = function(callback) {
        setTimeout(function() {
            try {
                document.getElementById("pessoa-setor").value = document.querySelector("#pessoa-setor-select option[value='" + document.getElementById("pessoa-id_setor").value + "']").innerHTML;
                document.getElementById("pessoa-id_setor").value = document.getElementById("pessoa-setor-select").value; 
            } catch(err) {
                document.getElementById("pessoa-setor").value = "--";
                document.getElementById("pessoa-setor-select").value = "0";
                document.getElementById("pessoa-id_setor").value = 0;
            }
            $("#pessoa-id_setor").trigger("change");
            if (callback !== undefined) callback();
        }, 0);
    }

    let titulo = id ? "Editando" : "Cadastrando";
    titulo += " colaborador";
    document.getElementById("pessoasModalLabel").innerHTML = titulo;
    let estilo_bloco_senha = document.getElementById("password").parentElement.parentElement.style;
    if (id) {
        $.get(URL + "/colaboradores/mostrar/" + id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            ["nome", "cpf", "pessoa-setor", "pessoa-empresa", "pessoa-id_setor", "pessoa-id_empresa", "email", "funcao", "admissao", "supervisor"].forEach((_id) => {
                document.getElementById(_id).value = data[_id.replace("pessoa-", "")];
            });
            ant_id_setor = parseInt(document.getElementById("pessoa-id_setor").value);
            ant_id_empresa = parseInt(document.getElementById("pessoa-id_empresa").value);
            setTimeout(function() {
                modal("pessoasModal", id, function() {
                    that.toggle_user(parseInt(data.id_setor));
                    estilo_bloco_senha.display = id != USUARIO && document.getElementById("pessoasModalLabel").innerHTML.indexOf("administrador") > -1 ? "none" : "";
                    document.getElementById("pessoa-setor-select").disabled = id == USUARIO;
                    document.getElementById("supervisor-chk").checked = parseInt(data.supervisor) == 1;
                    Array.from(document.getElementsByClassName("pessoa-senha")).forEach((el) => {
                        el.innerHTML = "Senha:";
                    });
                    document.getElementById("pessoa-setor-select").value = data.id_setor;
                    document.getElementById("pessoa-empresa-select").value = data.id_empresa;
                    that.alterarEmpresa();
                    that.alterarSetor();
                    document.querySelector("#pessoasModal .user-pic").parentElement.classList.remove("d-none");
                    if (!data.foto) {
                        let nome = data.nome.toUpperCase().replace("DE ", "").split(" ");
                        iniciais = "";
                        iniciais += nome[0][0];
                        if (nome.length > 1) iniciais += nome[nome.length - 1][0];
                        document.querySelector("#pessoasModal .user-pic span").innerHTML = iniciais;
                    }
                    foto_pessoa("#pessoasModal .user-pic", data.foto ? data.foto : "");
                    $("#pessoa-id_setor").trigger("change");
                });
            }, 0);
        });
    } else {
        setTimeout(function() {
            modal("pessoasModal", id, function() {
                that.toggle_user(id);
                estilo_bloco_senha.removeProperty("display");
                document.getElementById("pessoa-setor-select").disabled = false;
                Array.from(document.getElementsByClassName("pessoa-senha")).forEach((el) => {
                    el.innerHTML = "Senha: *";
                });
                document.querySelector("#pessoasModal .user-pic").parentElement.classList.add("d-none");
                document.getElementById("supervisor").value = "0";
                const tipo = document.getElementById("titulo-tela").innerHTML.charAt(0);
                if (tipo == "A" || tipo == "U") {
                    $.get(URL + "/setores/primeiro-admin", function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        document.getElementById("pessoa-setor-select").value = data.id;
                        document.getElementById("pessoa-id_setor").value = data.id;
                        that.toggle_user(data.id);
                        that.alterarEmpresa();
                        that.alterarSetor();
                    });
                } else if (tipo == "S") {
                    document.getElementById("supervisor-chk").checked = true;
                    document.getElementById("supervisor").value = 1;
                    that.alterarEmpresa();
                    that.alterarSetor();
                } else {
                    that.alterarEmpresa();
                    that.alterarSetor();
                }
                $("#pessoa-id_setor").trigger("change");
            });
        }, 0);
    }
}