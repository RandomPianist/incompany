
<!-- Modal -->
<div class = "modal fade" id = "estoqueModal" aria-labelledby = "estoqueModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "estoqueModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ config('app.root_url') }}/maquinas/estoque" method = "POST">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input class = "id_maquina" name = "id_maquina" type = "hidden" />
                        <div class = "row">
                            <div class = "col-4 form-search pr-1">
                                <label for = "produto-1" class = "custom-label-form">Produto: *</label>
                                <input id = "produto-1"
                                    name = "produto[]"
                                    class = "form-control autocomplete produto"
                                    data-input = "#id_produto-1"
                                    data-table = "produtos_todos"
                                    data-column = "descr"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "id_produto-1" class = "id-produto" name = "id_produto[]" type = "hidden" onchange = "atualizaPreco(1)" />
                            </div>
                            <div class = "col-2 p-0 px-1">
                                <label for = "es-1" class = "custom-label-form">E/S: *</label>
                                <select id = "es-1" name = "es[]" class = "form-control es" onchange = "carrega_obs(1)">
                                    <option value = "E">ENTRADA</option>
                                    <option value = "S">SAÍDA</option>
                                    <option value = "A">AJUSTE</option>
                                </select>
                            </div>
                            <div class = "col-1 p-0 px-1">
                                <label for = "qtd-1" class = "custom-label-form">Quantidade: *</label>
                                <input id = "qtd-1" name = "qtd[]" class = "form-control text-right qtd" autocomplete = "off" type = "number" onkeyup = "$(this).trigger('change')" onchange = "limitar(this)" />
                            </div>
                            <div class = "col-1 p-0 px-1 col-preco">
                                <label for = "preco-1" class = "custom-label-form">Preço: *</label>
                                <input id = "preco-1" name = "preco[]" class = "form-control dinheiro-editavel preco" autocomplete = "off" type = "text"/>
                            </div>
                            <div class = "col-2 p-0 px-1">
                                <label for = "obs-1" class = "custom-label-form">Observação:</label>
                                <input id = "obs-1" name = "obs[]" class = "form-control obs" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 16)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                            <div class = "col-2 text-right max-13">
                                <button type = "button" class = "btn btn-target mx-auto px-3 mt-4 w-100" onclick = "adicionar_campo()">+</button>
                            </div>
                        </div>
                        <template id = "template-linha">
                            <div class = "row mt-1">
                                <div class = "col-4 form-search pr-1">
                                    <input name = "produto[]"
                                        class = "form-control autocomplete produto"
                                        data-table = "produtos_todos"
                                        data-column = "descr"
                                        data-filter_col = ""
                                        data-filter = ""
                                        type = "text"
                                        autocomplete = "off"
                                    />
                                    <input type = "hidden" class = "id-produto" name = "id_produto[]" />
                                </div>
                                <div class = "col-2 p-0 px-1">
                                    <select class = "form-control es" name = "es[]"></select>
                                </div>
                                <div class = "col-1 p-0 px-1">
                                    <input type = "number" class = "form-control text-right qtd" name = "qtd[]" autocomplete = "off" onkeyup = "$(this).trigger('change')" onchange = "limitar(this)" />
                                </div>
                                <div class = "col-1 p-0 px-1 col-preco">
                                    <input type = "text" class="form-control dinheiro-editavel preco" name = "preco[]" autocomplete = "off" />
                                </div>
                                <div class = "col-2 p-0 px-1">
                                    <input type = "text" class = "form-control obs" name = "obs[]" autocomplete = "off" onkeyup = "contar_char(this, 16)" />
                                    <span class = "custom-label-form tam-max"></span>
                                </div>
                                <div class = "col-2 text-right max-13 p-0 pr-3">
                                    <button type = "button" class = "btn btn-target mr-2 px-20" onclick = "adicionar_campo()">+</button>
                                    <button type = "button" class = "btn btn-target-black mx-auto remove-produto px-20">-</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                <div class = "d-flex">
                    <button type = "button" class = "btn btn-target mx-auto my-4 px-5" onclick = "validar_estoque()">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type = "text/javascript" language = "JavaScript">
    function estoque(id) {
        $.get(URL + "/valores/{{ $alias }}/mostrar/" + id, function(descr) {
            $("#estoqueModalLabel").html(descr + " - movimentar estoque");
            $(".id_maquina").each(function() {
                $(this).val(id);
            });
            modal2("estoqueModal", ["obs-1", "qtd-1"]);
            $("#obs-1").trigger("keyup");
            $("#qtd-1").trigger("keyup");
            $("#es-1").trigger("change");
        });
    }

    function validar_estoque() {
        let obter_vetor = function(classe) {
            let resultado = new Array();
            $("." + classe).each(function() {
                resultado.push(classe == "preco" ? $(this).val().replace(/\D/g, "") : $(this).val());
            });
            return resultado.join(",");
        }

        limpar_invalido();
        let lista = new Array();
        for (let i = 1; i <= document.querySelectorAll("#estoqueModal input[type=number]").length; i++) lista.push("produto-" + i, "qtd-" + i);
        let erro = verifica_vazios(lista).erro;
        $.get(URL + "/maquinas/estoque/consultar/", {
            produtos_descr : obter_vetor("produto"),
            produtos_id : obter_vetor("id-produto"),
            quantidades : obter_vetor("qtd"),
            es : obter_vetor("es"),
            precos : obter_vetor("preco"),
            id_maquina : $($(".id_maquina")[0]).val()
        }, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            if (!erro && data.texto) {
                for (let i = 0; i < data.campos.length; i++) {
                    let el = $("#" + data.campos[i]);
                    $(el).val(data.valores[i]);
                    $(el).trigger("keyup");
                    $(el).addClass("invalido");
                }
                erro = data.texto;
            }
            if (!erro) {
                $(".preco").each(function() {
                    $(this).val(parseInt($(this).val().replace(/\D/g, "")) / 100);
                });
                $("#estoqueModal form").submit();
            } else s_alert(erro);
        });
    }

    function carrega_obs(seq) {
        switch($("es-" + seq).val()) {
            case "E":
                var obs = "ENTRADA";
                break;
            case "S":
                var obs = "SAÍDA";
                break;
            default:
                var obs = "AJUSTE";
        }
        $("obs-" + seq).val(obs);
        $("qtd-" + seq).focus();
    }

    function atualizaPreco(seq) {
        $.get(URL + "/maquinas/preco", {
            id_maquina : $($(".id_maquina")[0]).val(),
            id_produto : $("#id_produto-" + seq).val()
        }, function(preco) {
            let el_preco = $($($($("#id_produto-" + seq).parent()).parent()).find(".preco"));
            $(el_preco).val(preco);
            $(el_preco).trigger("keyup");
        })
    }

    function adicionar_campo() {
        const cont = $("#estoqueModal input[type=number]").length + 1;

        let linha = $($("#template-linha").html());

        $($(linha).find(".produto")[0]).attr("id", "produto-" + cont).data("input", "#id_produto-" + cont);
        $($(linha).find(".id-produto")[0]).attr("id", "id_produto-" + cont);
        $($(linha).find(".es")[0]).attr("id", "es-" + cont).html($("#es-1").html());
        $($(linha).find(".qtd")[0]).attr("id", "qtd-" + cont);
        $($(linha).find(".preco")[0]).attr("id", "preco-" + cont);
        $($(linha).find(".obs")[0]).attr("id", "obs-" + cont);

        $($(linha).find(".id-produto")[0]).on("change", () => atualizaPreco(cont));
        $($(linha).find(".es")[0]).on("change", () => carrega_obs(cont));

        $($(linha).find(".remove-produto")[0]).on("click", function() {
            $(linha).remove();
            ["produto","id_produto","es","qtd","preco","obs"].forEach((classe) => {
                $("." + classe).each(function(i) {
                    $(this).attr("id", classe + "-" + (i + 1));
                });
            });
        });

        $("#estoqueModal .container").append($(linha));

        carrega_autocomplete();
        carrega_dinheiro();
        $($(linha).find(".obs")[0]).trigger("keyup");
        $($(linha).find(".qtd")[0]).trigger("keyup");
        $($(linha).find(".es")[0]).trigger("change");

        $(".form-control").keydown(function() {
            $(this).removeClass("invalido");
        });
    }
</script>