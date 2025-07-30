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
            <div id = "filtro-grid-by0" class = "input-group col-12 mb-3" data-table = "#table-dados">
                <input id = "busca" type = "text" class = "form-control form-control-lg" placeholder = "Procurar por..." aria-label = "Procurar por..." aria-describedby = "btn-filtro" />
                <div class = "input-group-append">
                    <button class = "btn btn-secondary btn-search-grid" type = "button" onclick = "listar()">
                        <i class = "my-icon fas fa-search"></i>
                    </button>
                </div>
            </div>
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
            <div class = "table-body-scroll custom-scrollbar">
                <table id = "table-dados" class = "table">
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <button class = "btn btn-primary custom-fab" type = "button" onclick = "chamar_modal(0)">
        <i class = "my-icon fas fa-plus"></i>
    </button>
    <script type = "text/javascript" language = "JavaScript">
        let ant_consumo = false;

        function listar(coluna) {
            $.get(URL + "/produtos/listar", {
                filtro : document.getElementById("busca").value
            }, function(data) {
                let resultado = "";
                if (typeof data == "string") data = $.parseJSON(data);
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
                document.getElementById("table-dados").innerHTML = resultado;
                $(".dinheiro").each(function() {
                    let texto_final = (parseFloat($(this).html()) * 100).toString();
                    if (texto_final.indexOf(".") > -1) texto_final = texto_final.substring(0, texto_final.indexOf("."));
                    if (texto_final == "") $(this).html("R$ 0,00");
                    $(this).html(dinheiro(texto_final));
                    $(this).addClass("text-right");
                });
                ordenar(coluna);
            });
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

        function validar() {
            limpar_invalido();
            const aux = verifica_vazios(["cod_externo", "descr", "ca", "validade", "categoria", "tamanho", "validade_ca"]);
            let erro = aux.erro;
            let alterou = aux.alterou;
            let el_cod_externo = document.getElementById("cod_externo");
            let el_categoria = document.getElementById("categoria");
            let el_preco = document.getElementById("preco");
            const _preco = parseInt(el_preco.value.replace(/\D/g, ""));
            if (!erro && _preco <= 0) {
                erro = "Valor inválido";
                el_preco.classList.add("invalido");
            }
            if (el_preco.value.trim() != dinheiro(anteriores.preco.toString()) || document.getElementById("consumo-chk").checked != ant_consumo) alterou = true;
            $.get(URL + "/produtos/consultar/", {
                id : document.getElementById("id").value,
                cod_externo : el_cod_externo.value,
                categoria : el_categoria.value,
                id_categoria : document.getElementById("id_categoria").value,
                referencia : document.getElementById("referencia").value,
                preco : _preco
            }, function(data) {
                if (!erro && data == "invalido") {
                    erro = "Categoria não encontrada";
                    el_categoria.classList.add("invalido");
                }
                if (!erro && data == "duplicado") {
                    erro = "Já existe um registro com esse código";
                    el_cod_externo.classList.add("invalido");
                }
                if (!erro && data.indexOf("preco") > -1) {
                    erro = "O preço do produto não pode ser menor que o preço mínimo de " + dinheiro(data.replace("preco", ""));
                    el_preco.classList.add("invalido");
                }
                if (!erro && !alterou && !document.querySelector("#produtosModal input[type=file]").value) erro = "Altere pelo menos um campo para salvar";
                if (!erro) {
                    const fn = function() {
                        el_preco.value = _preco / 100;
                        document.querySelector("#produtosModal form").submit();
                    }
                    if (data == "aviso") s_confirm("Prosseguir apagará atribuições.<br>Deseja continuar?", fn);
                    else fn();
                } else s_alert(erro);
            });
        }

        function chamar_modal(id) {
            let titulo = id ? "Editando" : "Cadastrando";
            titulo += " produto";
            document.getElementById("produtosModalLabel").innerHTML = titulo;
            let el_img = document.querySelector("#produtosModal img");
            if (id) {
                $.get(URL + "/produtos/mostrar/" + id, function(data) {
                    if (typeof data == "string") data = $.parseJSON(data);
                    ["cod_externo", "descr", "preco", "ca", "validade", "categoria", "id_categoria", "referencia", "tamanho", "detalhes", "validade_ca_fmt"].forEach((_id) => {
                        document.getElementById(_id.replace("_fmt", "")).value = data[_id];
                    });
                    document.getElementById("cod_externo").disabled = true;
                    document.getElementById("consumo-chk").checked = parseInt(data.e_consumo) == 1;
                    ant_consumo = parseInt(data.e_consumo) == 1;
                    modal("produtosModal", id, function() {
                        el_img.src = data.foto;
                        el_img.parentElement.classList.remove("d-none");
                        if (!data.foto) el_img.parentElement.classList.add("d-none");
                    });
                });
            } else {
                modal("produtosModal", id, function() {
                    el_img.parentElement.classList.add("d-none");
                    document.getElementById("cod_externo").disabled = false;
                    document.getElementById("validade_ca").value = hoje();
                    document.getElementById("consumo-chk").checked = false;
                    ant_consumo = false;
                });
            }
        }

        function atualiza_cod_externo(el) {
            contar_char(el, 8);
            document.getElementById("cod_externo_real").value = document.getElementById("cod_externo").value;
        }
    </script>

    @include("modals.produtos_modal")
@endsection