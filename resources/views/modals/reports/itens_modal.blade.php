<!-- Modal -->
<div class = "modal fade" id = "relatorioItensModal" aria-hidden = "true">
    <div class = "modal-dialog modal-lg" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color">Extrato de itens</h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ config('app.root_url') }}/relatorios/extrato" method = "GET" target = "_blank">
                <div class = "modal-body">
                    <div class = "container">
                        <div class = "row">
                            <div class = "col-12 form-search">
                                <label for = "rel-maquina2" class = "custom-label-form">Máquina:</label>
                                <input id = "rel-maquina2"
                                    name = "maquina"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_maquina2"
                                    data-table = "valores"
                                    data-column = "descr"
                                    data-filter_col = "alias"
                                    data-filter = "maquinas"
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_maquina2" name = "id_maquina" type = "hidden" />
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-12 form-search">
                                <label for = "rel-produto" class = "custom-label-form">Produto:</label>
                                <input id = "rel-produto"
                                    name = "produto"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_produto"
                                    data-table = "produtos"
                                    data-column = "descr"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_produto" name = "id_produto" type = "hidden" />
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-6">
                                <label for = "rel-inicio1" class = "custom-label-form">Início:</label>
                                <input id = "rel-inicio1" name = "inicio" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                            <div class = "col-6">
                                <label for = "rel-fim1" class = "custom-label-form">Fim:</label>
                                <input id = "rel-fim1" name = "fim" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                        </div>
                        <div class = "row" style = "padding-top:5px">
                            <div class = "col-12">
                                <div class = "custom-control custom-switch">
                                    <input id = "rel-lm" name = "lm" type = "hidden" />
                                    <input id = "rel-lm-chk" class = "checkbox custom-control-input" type = "checkbox" onchange = "$(this).prev().val(this.checked ? 'S' : 'N')" />
                                    <label for = "rel-lm-chk" class = "custom-control-label">Listar movimentação<label>
                                </div>
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