
<!-- Modal -->
<div class = "modal fade modal-linha-dinamica cpModal" id = "mpModal" aria-labelledby = "mpModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl modal-dialog-scrollable" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "mpModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/produtos/maquina" method = "POST">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input id = "id_produto" name = "id_produto" type = "hidden" />
                        <div class = "row mt-3">
                            <div class = "col-10">
                                <input id = "busca-maq" type = "text" class = "form-control form-control-lg" placeholder = "Máquina" aria-label = "Máquina" aria-describedby = "btn-filtro2" />
                            </div>
                            <div class = "col-1 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "maquinas"
                                    data-campo_id = ""
                                    data-campo_descr = "busca-maq"
                                ></i>
                            </div>
                            <div class = "col-1">
                                <button id = "btn-filtro2" type = "button" class = "btn btn-target mr-2 px-20" onclick = "cp_mp_listar('mp', false)">
                                    <i class = "my-icon far fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class = "modal-tudo mt-4 pl-1 pb-1">
                            <div class = "row">
                                <div class = "col-5 form-search pr-1">
                                    <label for = "maquina-1" class = "custom-label-form">Máquinas: *</label>
                                    <input id = "maquina-1"
                                        name = "maquina[]"
                                        class = "form-control autocomplete maquina"
                                        data-input = "#mpModal #maquina-1"
                                        data-table = "maquinas"
                                        data-column = "descr"
                                        data-filter_col = ""
                                        data-filter = ""
                                        type = "text"
                                        autocomplete = "off"
                                    />
                                    <input id = "id_maquina-1" class = "id-maquina" name = "id_maquina[]" type = "hidden" />
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
                                    <button type = "button" class = "btn btn-target mx-auto px-3 mt-4 w-100" onclick = "cp_mp_adicionar_campo('mp')">+</button>
                                </div>
                            </div>
                            <template id = "template-linha">
                                <div class = "row mt-1">
                                    <div class = "col-5 form-search pr-1">
                                        <input name = "maquina[]"
                                            class = "form-control autocomplete maquina"
                                            data-table = "maquinas"
                                            data-column = "descr"
                                            data-filter_col = ""
                                            data-filter = ""
                                            type = "text"
                                            autocomplete = "off"
                                        />
                                        <input type = "hidden" class = "id-maquina" name = "id_maquina[]" />
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
                                        <button type = "button" class = "btn btn-target mr-1 px-20" onclick = "cp_mp_adicionar_campo('mp')">+</button>
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
                    <button type = "button" class = "btn btn-target mx-auto my-4 px-5" onclick = "cp_mp_validar('mp')">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>