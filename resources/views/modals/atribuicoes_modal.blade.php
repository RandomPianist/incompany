
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
                    <div class = "row pb-4">
                        <div class = "col-5 d-none" id = "div-referencia">
                            <label for = "referencia" class = "custom-label-form">Referência: *</label>
                            <input id = "referencia"
                                class = "form-control autocomplete w-108"
                                data-input = "#id_produto"
                                data-table = "produtos"
                                data-column = "referencia"
                                data-filter_col = "referencia"
                                data-filter = ""
                                data-prox = "quantidade"
                                type = "text"
                                autocomplete = "off"
                            />
                            <input id = "id_produto" type = "hidden" onchange = "atribuicao.preencherValidade(this.value)" />
                        </div>
                        <div class = "col-5" id = "div-produto">
                            <label for = "produto" class = "custom-label-form">Produto: *</label>
                            <input id = "produto"
                                class = "form-control autocomplete w-108"
                                data-input = "#id_produto"
                                data-table = "produtos"
                                data-column = "descr"
                                data-filter_col = ""
                                data-filter = ""
                                data-prox = "quantidade"
                                type = "text"
                                autocomplete = "off"
                            />
                        </div>
                        <div class = "col-1 d-flex align-items-center pl-0 pt-3 j-end">
                            <i
                                class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                data-atalho = "produtos"
                                data-campo_id = "id_produto"
                                data-campo_descr = "produto"
                            ></i>
                        </div>
                        <div class = "col-2">
                            <label for = "quantidade" class = "custom-label-form">Quantidade: *</label>
                            <input id = "quantidade" class = "form-control text-right" autocomplete = "off" type = "number" />
                        </div>
                        <div class = "col-2">
                            <label for = "validade" class = "custom-label-form">Validade em dias: *</label>
                            <input id = "validade" class = "form-control text-right" autocomplete = "off" type = "number" />
                        </div>
                        <div class = "col-2">
                            <label for = "obrigatorio" class = "custom-label-form">Obrigatório: *</label>
                            <select class = "form-control" id = "obrigatorio" onchange = "/*idatbglobal=0*/">
                                <option value = "opt-1">SIM</option>
                                <option value = "opt-0">NÃO</option>
                            </select>
                        </div>
                    </div>
                    <div class = "d-flex">
                        <button type = "button" class = "btn btn-target mx-auto mb-4 px-5" onclick = "atribuicao.salvar()">Atribuir</button>
                    </div>
                    <div class = "row mb-5">
                        <div class = "col-12 atribuicoes">
                            <table id = "table-atribuicoes" class = "w-100 atribuicoes" border = 1></table>
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