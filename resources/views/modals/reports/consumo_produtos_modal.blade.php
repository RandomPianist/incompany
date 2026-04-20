<div class = "modal fade" id = "relatorioConsumoProdutoModal" aria-labelledby = "relatorioConsumoProdutoModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-lg" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "relatorioConsumoProdutoModalLabel">Consumo por Produto</h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/relatorios/consumo-produtos" method = "GET" target = "_blank">
                <input name = "json" type = "hidden" value = "N"/>
                <div class = "modal-body">
                    <div class = "container">
                        <div class = "row">
                            <div class = "col-5 form-search">
                                <label for = "rel-empresa_cp" class = "custom-label-form">Empresa:</label>
                                <input id = "rel-empresa_cp"
                                    name = "empresa"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_empresa_cp"
                                    data-table = "empresas"
                                    data-column = "nome_fantasia"
                                    data-filter_col = ""
                                    data-filter = ""
                                    data-prox = "rel-produto_cp" 
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_empresa_cp" name = "id_empresa" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "empresas"
                                    data-campo_descr = "rel-empresa_cp"
                                    data-campo_id = "rel-id_empresa_cp"
                                ></i>
                            </div>

                            <div class = "col-5 form-search">
                                <label for = "rel-produto_cp" class = "custom-label-form">Produto:</label>
                                <input id = "rel-produto_cp"
                                    name = "produto"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_produto_cp"
                                    data-table = "produtos"
                                    data-column = "descr"
                                    data-filter_col = ""
                                    data-filter = ""
                                    data-prox = "rel-setor_cp"
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_produto_cp" name = "id_produto" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "produtos"
                                    data-campo_descr = "rel-produto_cp"
                                    data-campo_id = "rel-id_produto_cp"
                                ></i>
                            </div>
                        </div>

                        <div class = "row">
                            <div class = "col-5 form-search">
                                <label for = "rel-setor_cp" class = "custom-label-form">Centro de custo:</label>
                                <input id = "rel-setor_cp"
                                    name = "setor"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_setor_cp"
                                    data-table = "setores"
                                    data-column = "descr"
                                    data-filter_col = ""
                                    data-filter = ""
                                    data-prox = "rel-consumo2"
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_setor_cp" name = "id_setor" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "setores"
                                    data-campo_descr = "rel-setor_cp"
                                    data-campo_id = "rel-id_setor_cp"
                                ></i>
                            </div>

                            <!-- <div class = "col-6">
                                <label for = "rel-consumo2" class = "custom-label-form">Tipo de produto:</label>
                                <select class = "form-control" id = "rel-consumo2" name = "consumo" onchange = "$('#rel-inicio_cp').focus()">
                                    <option value = "todos">Todos</option>
                                    <option value = "consumo">Consumo</option>
                                    <option value = "epi">EPI</option>
                                </select>
                            </div> -->
                        </div>

                        <div class = "row">
                            <div class = "col-6">
                                <label for = "rel-inicio_cp" class = "custom-label-form">Início:</label>
                                <input id = "rel-inicio_cp" name = "inicio" class = "form-control data" autocomplete = "off" type = "text" data-prox = "rel-fim_cp"/>
                            </div>
                            <div class = "col-6">
                                <label for = "rel-fim_cp" class = "custom-label-form">Fim:</label>
                                <input id = "rel-fim_cp" name = "fim" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class = "d-flex">
                    <button type = "button" class = "btn btn-target mx-auto mb-4 my-4 px-5" onclick = "relatorio.validar()">Visualizar</button>
                </div>
            </form>
        </div>
    </div>
</div>