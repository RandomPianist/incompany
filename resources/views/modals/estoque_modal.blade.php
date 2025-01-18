
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
                                    class = "form-control autocomplete produto"
                                    data-input = "#id_produto-1"
                                    data-table = "produtos"
                                    data-column = "descr"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "id_produto-1" class = "id-produto" name = "id_produto[]" type = "hidden" />
                            </div>
                            <div class = "col-2 p-0 px-1">
                                <label for = "es-1" class = "custom-label-form">E/S: *</label>
                                <select id = "es-1" name = "es[]" class = "form-control es" onchange = "carrega_obs(1)">
                                    <option value = "E">ENTRADA</option>
                                    <option value = "S">SAÍDA</option>
                                </select>
                            </div>
                            <div class = "col-2 p-0 px-1">
                                <label for = "qtd-1" class = "custom-label-form">Quantidade: *</label>
                                <input id = "qtd-1" name = "qtd[]" class = "form-control text-right qtd" autocomplete = "off" type = "number" onkeyup = "$(this).trigger('change')" onchange = "limitar(this)" />
                            </div>
                            <div class = "col-2 p-0 px-1">
                                <label for = "obs-1" class = "custom-label-form">Observação:</label>
                                <input id = "obs-1" name = "obs[]" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 16)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                            <div class = "col-2 text-right">
                                <button type = "button" class = "btn btn-target mx-auto px-3 mt-4 w-100" onclick = "adicionar_campo()">+</button>
                            </div>
                        </div>
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
            document.getElementById("estoqueModalLabel").innerHTML = descr + " - movimentar estoque";
            Array.from(document.getElementsByClassName("id_maquina")).forEach((el) => {
                el.value = id;
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
            Array.from(document.getElementsByClassName(classe)).forEach((el) => {
                resultado.push(el.value);
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
            id_maquina : document.getElementsByClassName("id_maquina")[0].value
        }, function(data) {
            if (typeof data == "string") data = $.parseJSON(data);
            if (!erro && data.texto) {
                for (let i = 0; i < data.campos.length; i++) {
                    let el = document.getElementById(data.campos[i]);
                    el.value = data.valores[i];
                    el.classList.add("invalido");
                }
                erro = data.texto;
            }
            if (!erro) document.querySelector("#estoqueModal form").submit();
            else s_alert(erro);
        });
    }

    function carrega_obs(seq) {
        document.getElementById("obs-" + seq).value = document.getElementById("es-" + seq).value == "E" ? "ENTRADA" : "SAÍDA";
    }

    function adicionar_campo() {
        let tudo = document.querySelector("#estoqueModal .container");

        let linha = document.createElement("div");
        linha.classList.add("row");

        let col_prod = document.createElement("div");
        col_prod.classList.add("col-4", "form-search", "pr-1");

        let col_es = document.createElement("div");
        col_es.classList.add("col-2", "p-0", "px-1");

        let col_qtd = document.createElement("div");
        col_qtd.classList.add("col-2", "p-0", "px-1");

        let col_obs = document.createElement("div");
        col_obs.classList.add("col-2", "p-0", "px-1");

        let col_btn = document.createElement("div");
        col_btn.classList.add("col-2", "text-right");

        const cont = document.querySelectorAll("#estoqueModal input[type=number]").length + 1;

        let el_prod = document.createElement("input");
        el_prod.type = "text";
        el_prod.id = "produto-" + cont;
        el_prod.classList.add("form-control", "autocomplete", "produto");
        el_prod.autocomplete = "off";
        $(el_prod).data($("#produto-1").data());
        $(el_prod).data("input", "#id-produto-" + cont);

        let el_prod_post = document.createElement("input");
        el_prod_post.classList.add("id-produto");
        el_prod_post.type = "hidden";
        el_prod_post.name = "id_produto[]";
        el_prod_post.id = "id_produto-" + cont;

        let el_es = document.createElement("select");
        el_es.classList.add("form-control", "es");
        el_es.id = "es-" + cont;
        el_es.name = "es[]";
        el_es.innerHTML = document.getElementById("es-1").innerHTML;
        el_es.onchange = function() {
            carrega_obs(cont);
        }

        let el_qtd = document.createElement("input");
        el_qtd.type = "number";
        el_qtd.id = "qtd-" + cont;
        el_qtd.name = "qtd[]";
        el_qtd.classList.add("form-control", "text-right", "qtd");
        el_qtd.autocomplete = "off";
        el_qtd.onchange = function() {
            limitar(el_qtd);
        }
        el_qtd.onkeyup = function() {
            $(el_qtd).trigger("change");
        }

        let el_obs = document.createElement("input");
        el_obs.type = "text";
        el_obs.id = "obs-" + cont;
        el_obs.name = "obs[]";
        el_obs.classList.add("form-control");
        el_obs.autocomplete = "off";
        el_obs.onkeyup = function() {
            contar_char(el_obs, 16);
        }

        let el_obs_post = document.createElement("span");
        el_obs_post.classList.add("custom-label-form", "tam-max");

        let classes_btn = document.querySelector("#estoqueModal form div:not(.modal-body) button").classList.value
            .replace("mx-auto", "mr-2")
            .split(" ");
        classes_btn.splice(classes_btn.indexOf("px-3"), 1);
        classes_btn.splice(classes_btn.indexOf("mt-4"), 1);
        classes_btn.splice(classes_btn.indexOf("w-100"), 1);
        classes_btn.push("px-20");

        let adicionar = document.createElement("button");
        adicionar.type = "button";
        adicionar.textContent = "+";
        adicionar.classList.add(...classes_btn);

        classes_btn.splice(classes_btn.indexOf("mr-2"), 1);
        classes_btn.push("btn-target-black", "mx-auto", "remove-produto");
        let remover = document.createElement("button");
        remover.type = "button";
        remover.textContent = "-";
        remover.classList.add(...classes_btn);

        col_prod.appendChild(el_prod);
        col_prod.appendChild(el_prod_post);
        col_es.appendChild(el_es);
        col_qtd.appendChild(el_qtd);
        col_obs.appendChild(el_obs);
        col_obs.appendChild(el_obs_post);
        col_btn.appendChild(adicionar);
        col_btn.appendChild(remover);

        linha.appendChild(col_prod);
        linha.appendChild(col_es);
        linha.appendChild(col_qtd);
        linha.appendChild(col_obs);
        linha.appendChild(col_btn);
        tudo.appendChild(linha);

        adicionar.addEventListener("click", adicionar_campo);
        remover.addEventListener("click", () => {
            tudo.removeChild(linha);
        });

        carrega_autocomplete();
        $(el_obs).trigger("keyup");
        $(el_qtd).trigger("keyup");
        $(el_es).trigger("change");
        $(".form-control").each(function() {
            $(this).keydown(function() {
                $(this).removeClass("invalido");
            });
        });
    }
</script>