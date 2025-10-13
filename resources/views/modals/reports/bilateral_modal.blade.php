
<!-- Modal -->
<div class = "modal fade" id = "relatorioBilateralModal" aria-labelledby = "relatorioBilateralModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-lg" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "relatorioBilateralModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/relatorios/bilateral" method = "GET" target = "_blank">
                <input id = "rel-prioridade" name = "prioridade" type = "hidden" />
                <input id = "rel-grupo1" name = "rel_grupo" type = "hidden" />
                <div class = "modal-body">
                    <div class = "container">
                        <div class = "row">
                            <div class = "col-11 form-search">
                                <label for = "rel-empresa1" class = "custom-label-form">Empresa:</label>
                                <input id = "rel-empresa1"
                                    name = "empresa"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_empresa1"
                                    data-table = "empresas"
                                    data-column = "nome_fantasia"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_empresa1" name = "id_empresa" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "empresas"
                                    data-campo_id = "rel-id_empresa1"
                                    data-campo_descr = "rel-empresa1"
                                ></i>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-11 form-search">
                                <label for = "rel-maquina1" class = "custom-label-form">Máquina:</label>
                                <input id = "rel-maquina1"
                                    name = "maquina"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_maquina1"
                                    data-table = "maquinas"
                                    data-column = "descr"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_maquina1" name = "id_maquina" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "maquinas"
                                    data-campo_id = "rel-id_maquina1"
                                    data-campo_descr = "rel-maquina1"
                                ></i>
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