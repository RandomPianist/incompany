
<!-- Modal -->
<div class = "modal fade" id = "pessoaRetiradaModal" aria-hidden = "true">
    <div class = "modal-dialog modal-dialog-centered" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color">Seleção</h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <div class = "modal-body">
                <div class = "container">
                    <div class = "row">
                        <div class = "col-12 form-search">
                            <label for = "pessoa-retirando" class = "custom-label-form">Colaborador:</label>
                            <input id = "pessoa-retirando"
                                name = "pessoa"
                                class = "form-control autocomplete"
                                data-input = "#pessoa-retirando-id"
                                data-table = "pessoas"
                                data-column = "nome"
                                data-filter_col = ""
                                data-filter = ""
                                type = "text"
                                autocomplete = "off"
                            />
                            <input id = "pessoa-retirando-id" type = "hidden" />
                            <input id = "id_atribuicao" type = "hidden" />
                        </div>
                    </div>
                </div>
            </div>
            <div class = "d-flex">
                <button type = "button" class = "btn btn-target mx-auto mb-4 my-4 px-5" onclick = "atribuicao.setPessoaRetirando()">Ok</button>
            </div>
        </div>
    </div>
</div>