
<!-- Modal -->
<div class = "modal fade" id = "relatorioCienciaModal" aria-hidden = "true">
    <div class = "modal-dialog modal-lg" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color">Termo de Ciência</h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/relatorios/ciencia" method = "GET" target = "_blank">
                <div class = "modal-body">
                    <div class = "container">
                        <div class = "row">
                            <div class = "col-11 form-search">
                                <label for = "rel-pessoa4" class = "custom-label-form">Colaborador:</label>
                                <input id = "rel-pessoa4"
                                    name = "pessoa"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_pessoa4"
                                    data-table = "pessoas"
                                    data-column = "nome"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_pessoa4" name = "id_pessoa" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "pessoas"
                                    data-campo_id = "rel-id_pessoa4"
                                    data-campo_descr = "rel-pessoa4"
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