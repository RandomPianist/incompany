function Setor(_id) {
    let dados = new Array();
    let that = this;
    this.permissoesRascunho = new Array();

    const mostrar = function() {
        const setorDoSistema = parseInt(dados.sistema);
        const pessoas = parseInt(dados.pessoas);
        const meuSetor = parseInt(dados.meu_setor);
        $("#setor-empresa").attr("disabled", setorDoSistema || pessoas);
        if (setorDoSistema) $("#setor-empresa").attr("title", "Não é possível alterar a empresa de um centro de custo do sistema");
        else if (pessoas) $("#setor-empresa").attr("titile", "Não é possível alterar a empresa desse centro de custo porque existem pessoas vinculadas a ele");
        else $("#setor-empresa").removeAttr("title");
        $("#cria_usuario-chk").attr("disabled", !permissoes.usuarios || setorDoSistema || meuSetor);
        if (meuSetor) $("#cria_usuario-lbl").attr("title", "Alterar essa opção apagaria seu usuário");
        else if (setorDoSistema) $("#cria_usuario-lbl").attr("title", "Não é permitido alterar essa configuração em um setor do sistema");
        else if (!permissoes.usuarios) $("#cria_usuario-lbl").attr("title", "Não é possível atribuir a esse centro de custo permissões que seu usuário não tem");
        else $("#cria_usuario-lbl").removeAttr("title");
        that.muda_cria_usuario(function() {
            modal("setoresModal", _id);
        });
    }

    this.validar = function() {
        limpar_invalido();
    
        const _descr = $("#descr").toUpperCase().trim();
        const _empresa = $("#setor-empresa").val();
    
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
            _descr == anteriores.descr.toUpperCase().trim() &&
            _empresa.toUpperCase().trim() == anteriores["setor-empresa"].toUpperCase().trim()
        ) erro = "Não há alterações para salvar";
    
        $.get(URL + "/setores/consultar", {
            descr : _descr,
            id_empresa : $("#setor-id_empresa").val(),
            empresa : _empresa
        }, function(data) {
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

    this.muda_cria_usuario = function(callback) {
        let obter_campo_senha = function(_i, _nome) {
            return "<input type = 'password' name = 'password[]' class = 'validar form-control' id = 'senha-" + _i + "' placeholder = 'Senha de " + _nome + "' />"
        }

        const concluir = function(texto) {
            const setorDoSistema = parseInt(dados.sistema);
            if (texto === undefined) texto = "";
            if (texto) $("#setoresModal > .modal-dialog").addClass("modal-xl", "modal-xl-kx").removeClass("modal-lg");
            else $("#setoresModal > .modal-dialog").removeClass("modal-xl", "modal-xl-kx").addClass("modal-lg");
            $("#setoresModal .linha-permissao, #setoresModal .linha-usuario").each(function() {
                $(this).remove();
            });
            $("#setoresModal .container").append(texto + obterHtmlPermissoes(true, $("#cria_usuario-chk").prop("checked")));
            $("#setoresModal .linha-usuario:last-child").addClass("mb-4");
            permissoesPreencher(setorDoSistema);
            if (callback !== undefined) callback();
        }

        if (!_id) {
            concluir();
            return;
        }

        if ($("#cria_usuario-chk").prop("checked")) {
            $.get(URL + "/setores/pessoas/" + _id, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                const mostrar_email = parseInt(data.mostrar_email);
                const mostrar_fone = parseInt(data.mostrar_fone);
                let resultado = "";
                for (let i = 1; i <= data.consulta.length; i++) {
                    let nome = data.consulta[i - 1].nome;
                    resultado += "<div class = 'row linha-usuario mb-2'>" +
                        "<input type = 'hidden' name = 'id_pessoa[]' value = '" + data.consulta[i - 1].id + "' />";
                    let campo_email = "<input type = 'text' name = 'email[]' class = 'validar form-control' id = 'email-" + i + "' placeholder = 'Email de " + nome + "' value = '" + data.consulta[i - 1].email + "' />";
                    let campo_fone = "<input type = 'text' name = 'phone[]' class = 'validar telefone form-control' id = 'phone-" + i + "' placeholder = 'Telefone de " + nome + "' value = '" + data.consulta[i - 1].telefone + "' onkeyup = 'this.value=phoneMask(this.value)' />";
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
            $.get(URL + "/setores/usuarios/" + _id, function(data) {
                if (typeof data == "string") data = $.parseJSON(data);
                let resultado = "";
                for (let i = 1; i <= data.length; i++) {
                    resultado += "<div class = 'row linha-usuario mb-2'>" +
                        "<input type = 'hidden' name = 'id_pessoa[]' value = '" + data[i - 1].id + "' />" +
                        "<div class = 'col-12'>" +
                            obter_campo_senha(i, data.nome[i - 1]) +
                        "</div>" +
                    "</div>";
                }
                concluir(resultado);
            });
        }
    }

    $("#setoresModalLabel").html((_id ? "Editando" : "Cadastrando") + " centro de custo");
    if (_id) {
        $.get(URL + "/setores/mostrar/" + _id, function(_dados) {
            if (typeof _dados == "string") _dados = $.parseJSON(_dados);
            dados = _dados;
            $("#descr").val(dados.descr);
            $("#setor-id_empresa").val(dados.id_empresa);
            $("#setor-empresa").val(dados.empresa);
            $("#cria_usuario-chk").prop("checked", parseInt(dados.cria_usario) ? true : false);
            $("#cria_usuario").val(dados.cria_usuario);
            for (let x in permissoes) that.permissoesRascunho[x] = parseInt(dados[x]) ? true : false;
            mostrar();
        });
    } else {
        dados.sistema = "0";
        dados.meu_setor = "0";
        dados.pessoas = "0";
        $("#cria_usuario-chk").prop("checked", false);
        $("#cria_usuario").val("0");
        mostrar();
    }
}

function listar(coluna) {
    $.get(URL + "/setores/listar", {
        filtro : $("#busca").val()
    }, function(data) {
        let resultado = "";
        while (typeof data == "string") data = $.parseJSON(data);
        if (data.length) {
            esconderImagemErro();
            data.forEach((linha) => {
                resultado += "<tr>" +
                    "<td class = 'text-right' width = '10%'>" + linha.id.toString().padStart(4, "0") + "</td>" +
                    "<td width = '35%'>" + linha.descr + "</td>" +
                    "<td width = '40%'>" + linha.empresa + "</td>" +
                    "<td class = 'text-center btn-table-action' width = '15%'>";
                if (permissoes.atribuicoes) {
                    resultado += "<i class = 'my-icon far fa-box' title = 'Atribuir produto' onclick = 'atribuicao = new Atribuicoes(false, " + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-tshirt'       title = 'Atribuir grade'   onclick = 'atribuicao = new Atribuicoes(true, " + linha.id + ")'></i>";
                }
                resultado += "<i class = 'my-icon far fa-edit' title = 'Editar'  onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                        "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excluir(" + linha.id + ", " + '"/setores"' + ")'></i>" +
                    "</td>" +
                "</tr>";
            });
            $("#table-dados").html(resultado);
            ordenar(coluna);
        } else mostrarImagemErro();
    });
}

function chamar_modal(id) {
    setor = new Setor(id);
}