
<!-- Modal -->
<div class = "modal fade" id = "produtosModal" aria-labelledby = "produtosModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl modal-xl-kx modal-dialog-centered" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "produtosModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ config('app.root_url') }}/produtos/salvar" method = "POST" enctype = "multipart/form-data">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input id = "id" name = "id" type = "hidden" />
                        <div class = "row">
                            <img style = "max-width:100%;max-height:254px" />
                        </div>
                        <div class = "row">
                            <div class = "col-3">
                                <input type = "hidden" id = "cod_externo_real" name = "cod_externo" />
                                <label for = "cod_externo" class = "custom-label-form">Código Kx-safe: *</label>
                                <input id = "cod_externo" class = "form-control" autocomplete = "off" type = "text" onkeyup = "atualiza_cod_externo(this)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                            <div class = "col-6">
                                <label for = "descr" class = "custom-label-form">Descrição: *</label>
                                <input id = "descr" name = "descr" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 256)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                            <div class = "col-3">
                                <label for = "preco" class = "custom-label-form">Preço: *</label>
                                <input id = "preco" name = "preco" class = "form-control dinheiro-editavel" autocomplete = "off" type = "text"/>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-3">
                                <label for = "ca" class = "custom-label-form">CA: *</label>
                                <input id = "ca" name = "ca" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 16)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                            <div class = "col-4">
                                <label for = "validade_ca" class = "custom-label-form">Validade do CA: *</label>
                                <input id = "validade_ca" name = "validade_ca" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                            <div class = "col-2">
                                <label for = "referencia" class = "custom-label-form">Referência:</label>
                                <input id = "referencia" name = "referencia" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 50)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                            <div class = "col-3">
                                <label for = "tamanho" class = "custom-label-form">Tamanho: *</label>
                                <input id = "tamanho" name = "tamanho" class = "form-control" autocomplete = "off" type = "text" onkeyup = "contar_char(this, 32)" />
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-3">
                                <label for = "validade" class = "custom-label-form">Validade em dias: *</label>
                                <input id = "validade" name = "validade" class = "form-control text-right" autocomplete = "off" type = "number" onkeyup = "$(this).trigger('change')" onchange = "limitar(this)" />
                            </div>
                            <div class = "col-5 pr-0 form-search form-search-3">
                                <label for = "categoria" class = "custom-label-form">Categoria: *</label>
                                <input id = "categoria"
                                    class = "form-control autocomplete w-108"
                                    data-input = "#id_categoria"
                                    data-table = "valores"
                                    data-column = "descr"
                                    data-filter_col = "alias"
                                    data-filter = "categorias"
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "id_categoria" name = "id_categoria" type = "hidden"/>
                            </div>
                            <div class = "col-1 d-flex align-items-center pl-0 pt-3 j-end">
                                <a href = "{{ config('app.root_url') }}/valores/categorias" title = "Cadastro de categorias" target = "_blank">
                                    <i class = "fa-sharp fa-regular fa-arrow-up-right-from-square"></i>
                                </a>
                            </div>
                            <div class = "col-3">
                                <button type = "button" class = "btn btn-target btn-target-black w-100 mt-4" onclick = "$(this).next().trigger('click')">Adicionar imagem</button>
                                <input type = "file" name = "foto" class = "d-none" />
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-12">
                                <label for = "detalhes" class = "custom-label-form">Detalhes:</label>
                                <textarea class = "form-control" id = "detalhes" name = "detalhes" onkeyup = "contar_char(this, 21845)"></textarea>
                                <span class = "custom-label-form tam-max"></span>
                            </div>
                        </div>
                        <div class = "row mb-3" style = "padding-top:5px">
                            <div class = "col-12">
                                <div class = "custom-control custom-switch">
                                    <input id = "consumo" name = "consumo" type = "hidden" />
                                    <input id = "consumo-chk" class = "checkbox custom-control-input" type = "checkbox" onchange = "document.getElementById('consumo').value = this.checked ? 1 : 0" />
                                    <label for = "consumo-chk" class = "custom-control-label">Consumo<label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class = "d-flex">
                    <button type = "button" class = "btn btn-target mx-auto my-4 mb-4 px-5" onclick = "validar()">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>