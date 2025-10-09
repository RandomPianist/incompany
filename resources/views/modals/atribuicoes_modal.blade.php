<!-- Modal -->
<style type = "text/css" id = "estiloAux"></style>
<div class = "modal fade" id = "atribuicoesModal" aria-labelledby = "atribuicoesModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl modal-xl-kx" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "atribuicoesModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>

            <div class = "modal-body">
                <div class = "container">
                    <ul class = "nav nav-tabs" id = "atribuicoesTab" role = "tablist">
                        <li class = "nav-item" role = "presentation">
                            <a class = "nav-link active" id = "produto-tab" data-toggle = "tab" href = "#produto-pane" role = "tab" aria-controls = "produto-pane" aria-selected = "true">Produtos</a>
                        </li>
                        <li class = "nav-item" role = "presentation">
                            <a class = "nav-link" id = "grade-tab" data-toggle = "tab" href = "#grade-pane" role = "tab" aria-controls = "grade-pane" aria-selected = "false">Grades</a>
                        </li>
                    </ul>

                    <div class = "form-container-bordered">
                        <div class = "tab-content" id = "atribuicoesTabContent">
                            
                            <div class = "tab-pane fade show active" id = "produto-pane" role = "tabpanel" aria-labelledby = "produto-tab">
                                <div class = "row pt-2 align-items-end">
                                    <div class = "@if ($admin) col-4 @else col-5 @endif">
                                        <label for = "produto" class = "custom-label-form">Produto:</label>
                                        <input id = "produto" class = "form-control autocomplete" data-input = "#id_produto_p" data-table = "produtos" data-column = "descr" type = "text" autocomplete = "off" />
                                        <input id = "id_produto_p" type = "hidden" onchange = "atribuicao.preencherValidade(this.value, 'P')" />
                                    </div>
                                    @if ($admin)
                                        <div class = "col-1 pb-2" style = "cursor: pointer;">
                                            <i class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho" data-atalho = "produtos" data-campo_id = "id_produto_p" data-campo_descr = "produto"></i>
                                        </div>
                                    @endif
                                    <div class = "col-2">
                                        <label for = "quantidade_p" class = "custom-label-form">Quantidade:</label>
                                        <input id = "quantidade_p" class = "form-control text-right" autocomplete = "off" type = "number" value = "1" />
                                    </div>
                                    <div class = "col-2">
                                        <label for = "validade_p" class = "custom-label-form">Validade em dias:</label>
                                        <input id = "validade_p" class = "form-control text-right" autocomplete = "off" type = "number" value = "1" />
                                    </div>
                                    <div class = "col-3">
                                        <label for = "obrigatorio_p" class = "custom-label-form">Obrigatório:</label>
                                        <select class = "form-control" id = "obrigatorio_p">
                                            <option value = "opt-1">SIM</option>
                                            <option value = "opt-0" selected>NÃO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class = "tab-pane fade" id = "grade-pane" role = "tabpanel" aria-labelledby = "grade-tab">
                                <div class = "row pt-2 align-items-end">
                                    <div class = "@if ($admin) col-4 @else col-5 @endif">
                                        <label for = "referencia" class = "custom-label-form">Referência:</label>
                                        <input id = "referencia" class = "form-control autocomplete" data-input = "#id_produto_r" data-table = "produtos" data-column = "referencia" type = "text" autocomplete = "off" />
                                        <input id = "id_produto_r" type = "hidden" onchange = "atribuicao.preencherValidade(this.value, 'R')" />
                                    </div>
                                    @if ($admin)
                                    <div class = "col-1 pb-2" style = "cursor: pointer;">
                                        <i class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho" data-atalho = "produtos" data-campo_id = "id_produto_p" data-campo_descr = "produto"></i>
                                    </div>
                                    @endif
                                    <div class = "col-2">
                                        <label for = "quantidade_r" class = "custom-label-form">Quantidade:</label>
                                        <input id = "quantidade_r" class = "form-control text-right" autocomplete = "off" type = "number" value = "1" />
                                    </div>
                                    <div class = "col-2">
                                        <label for = "validade_r" class = "custom-label-form">Validade em dias:</label>
                                        <input id = "validade_r" class = "form-control text-right" autocomplete = "off" type = "number" value = "1" />
                                    </div>
                                    <div class = "col-3">
                                        <label for = "obrigatorio_r" class = "custom-label-form">Obrigatório:</label>
                                        <select class = "form-control" id = "obrigatorio_r">
                                            <option value = "opt-1">SIM</option>
                                            <option value = "opt-0" selected>NÃO</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class = "d-flex mt-4">
                            <button type = "button" class = "btn btn-target mx-auto mb-4 px-5" onclick = "atribuicao.salvar()">Atribuir</button>
                        </div>

                        <div class = "row">
                            <div class = "col-12 atribuicoes">
                                <table id = "table-atribuicoes" class = "w-100 atribuicoes" border = "1"></table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class = "d-flex" id = "col-btn-salvar">
                <button type = "button" class = "btn btn-target mx-auto mb-4 px-5" onclick = "atribuicao.recalcular()">Salvar</button>
            </div>
        </div>
    </div>
</div>

@include("modals.excecoes_modal")
@include("modals.detalhar_atb_modal")