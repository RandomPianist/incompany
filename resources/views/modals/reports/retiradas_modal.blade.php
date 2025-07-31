
<!-- Modal -->
<div class = "modal fade" id = "relatorioRetiradasModal" aria-labelledby = "relatorioRetiradasModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-lg" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "relatorioRetiradasModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ config('app.root_url') }}/relatorios/retiradas" method = "GET" target = "_blank">
                <input id = "rel-grupo2" name = "rel_grupo" type = "hidden" />
                <input name = "json" type = "hidden" value = "N"/>
                <div class = "modal-body">
                    <div class = "container">
                        <div class = "row">
                            <div class = "col-6 form-search">
                                <label for = "rel-empresa2" class = "custom-label-form">Empresa:</label>
                                <input id = "rel-empresa2"
                                    name = "empresa"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_empresa2"
                                    data-table = "empresas"
                                    data-column = "nome_fantasia"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_empresa2" name = "id_empresa" type = "hidden" />
                            </div>
                            <div class = "col-6 form-search">
                                <label for = "rel-pessoa2" class = "custom-label-form">Colaborador:</label>
                                <input id = "rel-pessoa2"
                                    name = "pessoa"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_pessoa2"
                                    data-table = "pessoas_todos"
                                    data-column = "nome"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_pessoa2" name = "id_pessoa" type = "hidden" />
                            </div>
                            <div class = "col-6 form-search">
                                <label for = "rel-setor" class = "custom-label-form">Centro de custo:</label>
                                <input id = "rel-setor"
                                    name = "setor"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_setor"
                                    data-table = "setores"
                                    data-column = "descr"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_setor" name = "id_setor" type = "hidden" />
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-6">
                                <label for = "rel-consumo2" class = "custom-label-form">Tipo de produto:</label>
                                <select class = "form-control" id = "rel-consumo2" name = "consumo">
                                    <option value = "todos">Todos</option>
                                    <option value = "consumo">Consumo</option>
                                    <option value = "epi">EPI</option>
                                </select>
                            </div>
                            <div class = "col-6">
                                <label for = "rel-tipo" class = "custom-label-form">Tipo de relatório:</label>
                                <select class = "form-control" id = "rel-tipo" name = "tipo">
                                    <option value = "A">Analítico</option>
                                    <option value = "S">Sintético</option>
                                </select>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-6">
                                <label for = "rel-inicio3" class = "custom-label-form">Início:</label>
                                <input id = "rel-inicio3" name = "inicio" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                            <div class = "col-6">
                                <label for = "rel-fim3" class = "custom-label-form">Fim:</label>
                                <input id = "rel-fim3" name = "fim" class = "form-control data" autocomplete = "off" type = "text" />
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