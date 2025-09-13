
<!-- Modal -->
<div class = "modal fade modal-linha-dinamica cpModal" id = "cpModal" aria-labelledby = "cpModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl modal-dialog-scrollable" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "cpModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/maquinas/produto" method = "POST">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input class = "id_maquina" name = "id_maquina" type = "hidden" />
                        <div class = "row mt-3">
                            <div class = "col-3 col-prod">
                                <input id = "busca-prod" type = "text" class = "form-control form-control-lg" placeholder = "Produto" aria-label = "Produto" aria-describedby = "btn-filtro2" />
                            </div>
                            <div class = "col-1 d-flex align-items-center col-atalho">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "produtos"
                                    data-campo_id = ""
                                    data-campo_descr = "busca-prod"
                                ></i>
                            </div>
                            <div class = "col-3">
                                <input id = "busca-refer" type = "text" class = "form-control form-control-lg" placeholder = "Referência" aria-label = "Referência" aria-describedby = "btn-filtro2" />
                            </div>
                            <div class = "col-3 col-prod">
                                <input id = "busca-cat" type = "text" class = "form-control form-control-lg" placeholder = "Categoria" aria-label = "Categoria" aria-describedby = "btn-filtro2" />
                            </div>
                            <div class = "col-1 d-flex align-items-center col-atalho">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "categorias"
                                    data-campo_id = ""
                                    data-campo_descr = "busca-cat"
                                ></i>
                            </div>
                            <div class = "col-1">
                                <button id = "btn-filtro2" type = "button" class = "btn btn-target mr-2 px-20" onclick = "cp_mp_listar('cp', false)">
                                    <i class = "my-icon far fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class = "modal-tudo mt-4 pr-3 pl-1 pb-1">
                            <div class = "row">
                                <div class = "col-5 form-search pr-1">
                                    <label for = "produto-1" class = "custom-label-form">Produto: *</label>
                                    <input id = "produto-1"
                                        name = "produto[]"
                                        class = "form-control autocomplete produto"
                                        data-input = "#cpModal #id_produto-1"
                                        data-table = "produtos_todos"
                                        data-column = "descr"
                                        data-filter_col = ""
                                        data-filter = ""
                                        type = "text"
                                        autocomplete = "off"
                                    />
                                    <input id = "id_produto-1" class = "id-produto" name = "id_produto[]" type = "hidden" />
                                </div>
                                <div class = "col-2 p-0 px-1">
                                    <label for = "lixeira-1" class = "custom-label-form">Situação: *</label>
                                    <select id = "lixeira-1" name = "lixeira[]" class = "form-control lixeira">
                                        <option value = "opt-0">Liberado para venda</option>
                                        <option value = "opt-1">Fora de linha</option>
                                    </select>
                                </div>
                                <div class = "col-1 p-0 px-1 col-preco">
                                    <label for = "preco-1" class = "custom-label-form">Preço: *</label>
                                    <input id = "preco-1" name = "preco[]" class = "form-control dinheiro-editavel preco" autocomplete = "off" type = "text"/>
                                </div>
                                <div class = "col-1 p-0 px-1">
                                    <label for = "minimo-1" class = "custom-label-form">Mínimo:</label>
                                    <input id = "minimo-1" name = "minimo[]" class = "form-control text-right minimo" autocomplete = "off" type = "number" />
                                </div>
                                <div class = "col-1 p-0 px-1">
                                    <label for = "maximo-1" class = "custom-label-form">Máximo:</label>
                                    <input id = "maximo-1" name = "maximo[]" class = "form-control text-right maximo" autocomplete = "off" type = "number" />
                                </div>
                                <div class = "col-2 text-right max-13">
                                    <button type = "button" class = "btn btn-target mx-auto px-3 mt-4 w-100" onclick = "cp_mp_adicionar_campo('cp')">+</button>
                                </div>
                            </div>
                            <template id = "template-linha">
                                <div class = "row mt-1">
                                    <div class = "col-5 form-search pr-1">
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
                                        <select name = "lixeira[]" class = "form-control lixeira"></select>
                                    </div>
                                    <div class = "col-1 p-0 px-1 col-preco">
                                        <input type = "text" class = "form-control dinheiro-editavel preco" name = "preco[]" autocomplete = "off" />
                                    </div>
                                    <div class = "col-1 p-0 px-1">
                                        <input name = "minimo[]" class = "form-control text-right minimo" autocomplete = "off" type = "number" />
                                    </div>
                                    <div class = "col-1 p-0 px-1">
                                        <input name = "maximo[]" class = "form-control text-right maximo" autocomplete = "off" type = "number" />
                                    </div>
                                    <div class = "col-2 text-right max-13 p-0 pr-3">
                                        <button type = "button" class = "btn btn-target mr-1 px-20" onclick = "cp_mp_adicionar_campo('cp')">+</button>
                                        <button type = "button" class = "btn btn-target-black mx-auto remove-linha px-15">
                                            <i class = "fal fa-eye-slash"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class = "d-flex mb-2">
                    <button type = "button" class = "btn btn-target mx-auto my-4 px-5" onclick = "cp_mp_validar('cp')">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>