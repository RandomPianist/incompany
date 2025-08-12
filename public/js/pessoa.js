function Pessoa(_id) {
    let that = this;
    let ant_id_setor, ant_id_empresa, validaUsuario;

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
                validaUsuario = true;
            } else {
                pes_info.remove("d-none");
                palavras[1] = "colaborador";
                validaUsuario = false;
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

        limpar_invalido();
        let erro = "";

        let _email = document.getElementById("email");
        let _cpf = document.getElementById("cpf");
        let _admissao = document.getElementById("admissao");

        if (validaUsuario && !_email.value) {
            erro = "Preencha o campo";
            _email.classList.add("invalido");
        }
        if (!_cpf.value) {
            if (!erro) erro = "Preencha o campo";
            else erro = "Preencha os campos";
            _cpf.classList.add("invalido");
        }
        let lista = ["nome", "funcao", "admissao", "supervisor"];
        if (!_id) lista.push(validaUsuario ? "password" : "senha");
        const aux = verifica_vazios(lista, erro);
        erro = aux.erro;
        let alterou = aux.alterou;
        if (validaUsuario) {
            if (!erro && !validar_email(_email.value)) {
                erro = "E-mail inválido";
                _email.classList.add("invalido");
            }
            if (
                document.getElementById("password").value ||
                _email.value.toLowerCase() != anteriores.email.toLowerCase()
            ) alterou = true;
        } else if (document.getElementById("senha").value) alterou = true;
        if (!erro && document.getElementById("pessoa-setor-select").value != ant_id_setor) alterou = true;
        if (!erro && document.getElementById("pessoa-empresa-select").value != ant_id_empresa) alterou = true;
        if (_admissao.value) {
            if (!erro && eFuturo(_admissao.value)) {
                erro = "A admissão não pode ser no futuro";
                _admissao.classList.add("invalido");
            }
        }
        $.get(URL + "/colaboradores/consultar/", {
            cpf : _cpf.value.replace(/\D/g, ""),
            email : _email.value,
            id : _id,
            id_setor : document.getElementById("pessoa-setor-select").value
        }, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            if (!erro && data.tipo == "duplicado") {
                erro = "Já existe um registro com esse " + data.dado;
                document.getElementById(data.dado.replace("-", "").toLowerCase()).classList.add("invalido");
            }
            if (!erro && data.tipo == "permissao") erro = "Você não tem permissão para " + (_id ? "editar esse" : "criar um") + " administrador";
            if (!erro && !alterou && !document.querySelector("#pessoasModal input[type=file]").value) erro = "Altere pelo menos um campo para salvar";
            if (!erro) {
                _cpf.value = _cpf.value.replace(/\D/g, "");
                document.querySelector("#pessoasModal form").submit();
            } else s_alert(erro);
        });
    }

    let titulo = _id ? "Editando" : "Cadastrando";
    titulo += " colaborador";
    document.getElementById("pessoasModalLabel").innerHTML = titulo;
    let estilo_bloco_senha = document.getElementById("password").parentElement.parentElement.style;
    let el_setor = document.getElementById("pessoa-setor-select");
    let el_empresa = document.getElementById("pessoa-empresa-select");
    let el_sup_chk = document.getElementById("supervisor-chk");
    if (_id) {
        $.get(URL + "/colaboradores/mostrar/" + _id, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            ["nome", "cpf", "pessoa-setor-select", "pessoa-empresa-select", "email", "funcao", "admissao", "supervisor"].forEach((__id) => {
                document.getElementById(__id).value = data[__id.replace("pessoa-", "id_").replace("-select", "")];
            });
            ant_id_setor = parseInt(el_setor.value);
            ant_id_empresa = parseInt(el_empresa.value);
            setTimeout(function() {
                modal("pessoasModal", _id, function() {
                    that.toggle_user(parseInt(data.id_setor));
                    estilo_bloco_senha.display = id != USUARIO && validaUsuario ? "none" : "";
                    el_setor.disabled = _id == USUARIO;
                    el_sup_chk.checked = parseInt(data.supervisor) == 1;
                    Array.from(document.getElementsByClassName("pessoa-senha")).forEach((el) => {
                        el.innerHTML = "Senha:";
                    });
                    el_setor.value = data.id_setor;
                    el_empresa.value = data.id_empresa;
                    that.toggle_user(data.id_setor);
                    document.querySelector("#pessoasModal .user-pic").parentElement.classList.remove("d-none");
                    if (!data.foto) {
                        let nome = data.nome.toUpperCase().replace("DE ", "").split(" ");
                        iniciais = "";
                        iniciais += nome[0][0];
                        if (nome.length > 1) iniciais += nome[nome.length - 1][0];
                        document.querySelector("#pessoasModal .user-pic span").innerHTML = iniciais;
                    }
                    foto_pessoa("#pessoasModal .user-pic", data.foto ? data.foto : "");
                });
            }, 0);
        });
    } else {
        setTimeout(function() {
            modal("pessoasModal", 0, function() {
                estilo_bloco_senha.removeProperty("display");
                el_setor.disabled = false;
                Array.from(document.getElementsByClassName("pessoa-senha")).forEach((el) => {
                    el.innerHTML = "Senha: *";
                });
                document.querySelector("#pessoasModal .user-pic").parentElement.classList.add("d-none");
                const tipo = document.getElementById("titulo-tela").innerHTML.charAt(0);
                el_sup_chk.checked = tipo == "S";
                document.getElementById("supervisor").value = tipo == "S" ? "1" : "0";

                if (tipo == "A" || tipo == "U") {
                    $.get(URL + "/setores/primeiro-admin", function(data) {
                        if (typeof data == "string") data = $.parseJSON(data);
                        el_setor.value = data.id;
                        that.toggle_user(data.id);
                    });
                } else that.toggle_user(0);
            });
        }, 0);
    }
}