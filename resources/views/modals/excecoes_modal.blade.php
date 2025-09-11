
<!-- Modal -->
<div class = "modal fade" id = "excecoesModal" aria-labelledby = "excecoesModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-lg" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "excecoesModalLabel">Exceções da atribuição</h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <div class = "modal-body">
                <div class = "container">
                    <div class = "row pb-4">
                        <div class = "col-4">
                            <label for = "exc-ps-chave" class = "custom-label-form">Tipo:</label>
                            <select class = "form-control" id = "exc-ps-chave" onchange = "excecao.mudarTipo(this.value)">
                                <option value = "P">Funcionário</option>
                                <option value = "S">Centro de custo</option>
                            </select>
                        </div>
                        <div class = "col-7">
                            <label for = "exc-ps-valor" id = "lbl-exc-ps-valor" class = "custom-label-form"></label>
                            <input id = "exc-ps-valor"
                                class = "form-control autocomplete w-108"
                                data-input = "#exc-ps-id"
                                data-filter_col = ""
                                data-filter = ""
                                type = "text"
                                autocomplete = "off"
                            />
                            <input id = "exc-ps-id" type = "hidden" />
                        </div>
                        <div class = "col-1 d-flex align-items-center pl-0 pt-3 j-end">
                            <a target = "_blank" id = "exc-atalho">
                                <i class = "fa-sharp fa-regular fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    </div>
                    <div class = "d-flex">
                        <button type = "button" class = "btn btn-target mx-auto mb-4 px-5" onclick = "excecao.salvar()">Salvar</button>
                    </div>
                    <div class = "row">
                        <div class = "col-12">
                            <table id = "table-excecoes" class = "w-100 atribuicoes" border = 1></table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>