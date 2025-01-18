
<!-- Modal -->
<style type = "text/css" id = "estiloAux"></style>
<div class = "modal fade" id = "atribuicoesModal" aria-labelledby = "atribuicoesModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl" role = "document">
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
                                data-filter_col = ""
                                data-filter = ""
                                type = "text"
                                autocomplete = "off"
                            />
                            <input id = "id_produto" type = "hidden" onchange = "/*idatbglobal=0*/" />
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
                                type = "text"
                                autocomplete = "off"
                            />
                        </div>
                        <div class = "col-1 d-flex align-items-center pl-0 pt-3 j-end">
                            <a href = "{{ config('app.root_url') }}/produtos" title = "Cadastro de produtos" target = "_blank">
                                <i class="fa-sharp fa-regular fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                        <div class = "col-2">
                            <label for = "quantidade" class = "custom-label-form">Quantidade: *</label>
                            <input id = "quantidade" class = "form-control text-right" autocomplete = "off" type = "number" onkeyup = "$(this).trigger('change')" onchange = "/*idatbglobal=0;*/limitar(this)" />
                        </div>
                        <div class = "col-2">
                            <label for = "validade" class = "custom-label-form">Validade em dias: *</label>
                            <input id = "validade" class = "form-control text-right" autocomplete = "off" type = "number" onkeyup = "$(this).trigger('change')" onchange = "/*idatbglobal=0;*/limitar(this)" />
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
                        <button type = "button" class = "btn btn-target mx-auto mb-4 px-5" onclick = "atribuir()">Atribuir</button>
                    </div>
                    <div class = "row">
                        <div class = "col-12">
                            <table id = "table-atribuicoes" class = "w-100" border = 1></table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>