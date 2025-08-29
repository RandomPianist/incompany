@extends("layouts.app")

@section("content")
    <div class = "container-fluid h-100 px-3">
        <div class = "row">
            <table class = "w-100">
                <tr>
                    <td class = "w-100">
                        <h3 class = "col header-color mb-3">Produtos</h3>
                    </td>
                    <td class = "ultima-atualizacao">
                        <span class = "custom-label-form">{{ $ultima_atualizacao }}</span>
                    </td>
                </tr>
            </table>
            @include("components.busca")
        </div>
        <div class = "custom-table card">
            <div class = "table-header-scroll">
                <table>
                    <thead>
                        <tr class = "sortable-columns" for = "#table-dados">
                            <th width = "5%" class = "nao-ordena">&nbsp;</span>
                            <th width = "20%" class = "text-center">
                                <span>Código Kx-safe</span>
                            </th>
                            <th width = "27.5%">
                                <span>Descrição</span>
                            </th>
                            <th width = "27.5%">
                                <span>Categoria</span>
                            </th>
                            <th width = "10%" class = "text-right">
                                <span>Preço</span>
                            </th>
                            <th width = "10%" class = "text-center nao-ordena">
                                <span>Ações</span>
                            </th>
                        </tr>
                    </thead>
                </table>
            </div>
            @include("components.table_dados")
        </div>
    </div>
    @include("components.naoencontrado")
    <script type = "text/javascript" language = "JavaScript">
        let ant_consumo = false;

        function listar(coluna) {
            $.get(URL + "/produtos/listar", {
                filtro : $("#busca").val()
            }, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
                if (data.length) {
                    esconderImagemErro();
                    data.forEach((linha) => {
                        resultado += "<tr>" +
                            "<td width = '5%' class = 'text-center'>" +
                                "<img class = 'user-photo-sm' src = '" + linha.foto + "'" + ' onerror = "this.onerror=null;' + "this.classList.add('d-none');$(this).next().removeClass('d-none')" + '" />' +
                                "<i class = 'fa-light fa-image d-none' style = 'font-size:20px'></i>" +
                            "</td>" +
                            "<td width = '20%' class = 'text-center'>" + linha.cod_externo + "</td>" +
                            "<td width = '27.5%'>" + linha.descr + "</td>" +
                            "<td width = '27.5%'>" + linha.categoria + "</td>" +
                            "<td width = '10%' class = 'dinheiro'>" + linha.preco + "</td>" +
                            "<td class = 'text-center btn-table-action' width = '10%'>" +
                                "<i class = 'my-icon far fa-edit'      title = 'Editar'  onclick = 'chamar_modal(" + linha.id + ")'></i>" +
                                "<i class = 'my-icon far fa-trash-alt' title = 'Excluir' onclick = 'excluir(" + linha.id + ", " + '"/produtos"' + ")'></i>"
                            "</td>" +
                        "</tr>";
                    });
                    $("#table-dados").html(resultado);
                    $(".dinheiro").each(function() {
                        let texto_final = (parseFloat($(this).html()) * 100).toString();
                        if (texto_final.indexOf(".") > -1) texto_final = texto_final.substring(0, texto_final.indexOf("."));
                        if (texto_final == "") $(this).html("R$ 0,00");
                        $(this).html(dinheiro(texto_final));
                        $(this).addClass("text-right");
                    });
                    ordenar(coluna);
                } else mostrarImagemErro();
            });
        }

        function validar() {
            limpar_invalido();
            const aux = verifica_vazios(["cod_externo", "descr", "ca", "validade", "categoria", "tamanho", "validade_ca"]);
            let erro = aux.erro;
            let alterou = aux.alterou;
            if (!erro && parseInt($("#preco").val().replace(/\D/g, "")) <= 0) {
                erro = "Valor inválido";
                $("#preco").addClass("invalido");
            }
            if ($("#preco").val().trim() != dinheiro(anteriores.preco.toString()) || $("#consumo-chk").prop("checked") != ant_consumo) alterou = true;
            $.get(URL + "/produtos/consultar/", {
                id : $("#id").val(),
                cod_externo : $("#cod_externo").val(),
                categoria : $("#categoria").val(),
                id_categoria : $("#id_categoria").val(),
                referencia : $("#referencia").val(),
                preco : parseInt($("#preco").val().replace(/\D/g, "")) / 100
            }, function(data) {
                if (!erro && data == "invalido") {
                    erro = "Categoria não encontrada";
                    $("#categoria").addClass("invalido");
                }
                if (!erro && data == "duplicado") {
                    erro = "Já existe um registro com esse código";
                    $("#cod_externo").addClass("invalido");
                }
                if (!erro && data.indexOf("preco") > -1) {
                    erro = "O preço está inferior ao preço mínimo de " + dinheiro(data.replace("preco", ""));
                    $("#preco").addClass("invalido");
                }
                if (!erro && !alterou && !document.querySelector("#produtosModal input[type=file]").value) erro = "Altere pelo menos um campo para salvar";
                if (!erro) {
                    const fn = function() {
                        $("#preco").val(parseInt($("#preco").val().replace(/\D/g, "")) / 100);
                        $("#produtosModal form").submit();
                    }
                    if (data == "aviso") s_confirm("Prosseguir apagará atribuições.<br>Deseja continuar?", fn);
                    else fn();
                } else s_alert(erro);
            });
        }

        function chamar_modal(id) {
            $("#produtosModalLabel").html((id ? "Editando" : "Cadastrando") + " produto");
            if (id) {
                $.get(URL + "/produtos/mostrar/" + id, function(data) {
                    if (typeof data == "string") data = $.parseJSON(data);
                    ["cod_externo", "descr", "preco", "ca", "validade", "categoria", "id_categoria", "referencia", "tamanho", "detalhes", "validade_ca_fmt"].forEach((_id) => {
                        $("#" + _id.replace("_fmt", "")).val(data[_id]);
                    });
                    $("#cod_externo").attr("disabled", true);
                    $("#consumo-chk").prop("checked", parseInt(data.e_consumo) == 1);
                    ant_consumo = parseInt(data.e_consumo) == 1;
                    modal("produtosModal", id, function() {
                        $("#produtosModal img").attr("src", data.foto);
                        $($("#produtosModal img").parent()).removeClass("d-none");
                        if (!data.foto) $($("#produtosModal img").parent()).addClass("d-none");
                    });
                });
            } else {
                modal("produtosModal", id, function() {
                    $($("#produtosModal img").parent()).addClass("d-none");
                    $("#cod_externo").attr("disabled", false);
                    $("#validade_ca").val(hoje());
                    $("#consumo-chk").prop("checked", false);
                    ant_consumo = false;
                });
            }
        }

        function atualiza_cod_externo(el) {
            contar_char(el, 8);
            $("#cod_externo_real").val($("#cod_externo").val());
        }
    </script>

    @include("modals.produtos_modal")
@endsection