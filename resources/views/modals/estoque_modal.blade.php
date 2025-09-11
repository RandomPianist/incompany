
<!-- Modal -->
<div class = "modal fade modal-linha-dinamica" id = "estoqueModal" aria-labelledby = "estoqueModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl modal-dialog-scrollable" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "estoqueModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/maquinas/estoque" method = "POST">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input class = "id_maquina" name = "id_maquina" type = "hidden" />
                        <div class = "modal-tudo pr-3 pl-1">
                            <div class = "row">
                                <div class = "col-3 form-search pr-1 col-prod">
                                    <label for = "produto-1" class = "custom-label-form">Produto: *</label>
                                    <input id = "produto-1"
                                        name = "produto[]"
                                        class = "form-control autocomplete produto"
                                        data-input = "#estoqueModal #id_produto-1"
                                        data-table = "produtos_todos"
                                        data-column = "descr"
                                        data-filter_col = ""
                                        data-filter = ""
                                        type = "text"
                                        autocomplete = "off"
                                    />
                                    <input id = "id_produto-1" class = "id-produto" name = "id_produto[]" type = "hidden" onchange = "atualizaPreco(1, 'estoque')" />
                                </div>
                                <div class = "col-1 d-flex align-items-center pl-0 pt-0 j-center col-atalho">
                                    <i
                                        class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                        data-atalho = "produtos"
                                        data-campo_id = "#estoqueModal #id_produto-1"
                                        data-campo_descr = "#estoqueModal #produto-1"
                                    ></i>
                               </div>
                                <div class = "col-2 p-0 px-1">
                                    <label for = "es-1" class = "custom-label-form">E/S: *</label>
                                    <select id = "es-1" name = "es[]" class = "form-control es" onchange = "carrega_obs(1, true)">
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
                                <div class = "col-2 text-right max-13 pt-1">
                                    <button type = "button" class = "btn btn-target mx-auto px-3 mt-4 w-100" onclick = "adicionar_campo_estoque()">+</button>
                                </div>
                            </div>
                            <template id = "template-linha">
                                <div class = "row mt-1">
                                    <div class = "col-3 form-search pr-1 col-prod">
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
                                    <div class = "col-1 d-flex align-items-center pl-0 pt-0 j-center col-atalho col-atalho2">
                                        <i class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho" data-atalho = "produtos"></i>
                                    </div>
                                    <div class = "col-2 p-0 px-1">
                                        <select class = "form-control es" name = "es[]"></select>
                                    </div>
                                    <div class = "col-1 p-0 px-1">
                                        <input type = "number" class = "form-control text-right qtd" name = "qtd[]" autocomplete = "off" onkeyup = "$(this).trigger('change')" onchange = "limitar(this)" />
                                    </div>
                                    <div class = "col-1 p-0 px-1 col-preco">
                                        <input type = "text" class = "form-control dinheiro-editavel preco" name = "preco[]" autocomplete = "off" />
                                    </div>
                                    <div class = "col-2 p-0 px-1">
                                        <input type = "text" class = "form-control obs" name = "obs[]" autocomplete = "off" onkeyup = "contar_char(this, 16)" />
                                        <span class = "custom-label-form tam-max"></span>
                                    </div>
                                    <div class = "col-2 text-right max-13 p-0 pr-3 pt-1">
                                        <button type = "button" class = "btn btn-target mr-1 px-20" onclick = "adicionar_campo_estoque()">+</button>
                                        <button type = "button" class = "btn btn-target-black mx-auto remove-produto px-15">
                                            <i class = "fal fa-eye-slash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class = "d-flex mb-2">
                    <button type = "button" class = "btn btn-target mx-auto my-4 px-5" onclick = "validar_estoque()">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>