
<!-- Modal -->
<div class = "modal fade" id = "comodatosModal" aria-labelledby = "comodatosModalLabel" aria-hidden = "true">
    <div class = "modal-dialog modal-xl modal-xl-kx" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color" id = "comodatosModalLabel"></h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form method = "POST">
                <div class = "modal-body">
                    <div class = "container">
                        @csrf
                        <input class = "id_maquina" name = "id_maquina" type = "hidden" />
                        <div class = "row">
                            <div class = "col-7 pr-0 form-search form-search-2">
                                <label for = "comodato-empresa" class = "custom-label-form">Empresa: *</label>
                                <input id = "comodato-empresa"
                                    name = "empresa"
                                    class = "form-control autocomplete"
                                    data-input = "#comodato-id_empresa"
                                    data-table = "empresas"
                                    data-column = "nome_fantasia"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    data-prox = "comodato-inicio"
                                    autocomplete = "off"
                                />
                                <input id = "comodato-id_empresa" name = "id_empresa" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <a href = "{{ $root_url }}/empresas" title = "Cadastro de empresas" target = "_blank">
                                    <i class = "fa-sharp fa-regular fa-arrow-up-right-from-square"></i>
                                </a>
                            </div>
                            <div class = "col-2">
                                <label for = "comodato-inicio" class = "custom-label-form">Início: *</label>
                                <input id = "comodato-inicio" name = "inicio" class = "form-control data" autocomplete = "off" type = "text" data-prox = "comodato-fim" />
                            </div>
                            <div class = "col-2">
                                <label for = "comodato-fim" class = "custom-label-form">Fim: *</label>
                                <input id = "comodato-fim" name = "fim" class = "form-control data" autocomplete = "off" type = "text" />
                            </div>
                        </div>
                        <div class = "row pb5-px">
                            <div class = "col-12">
                                <div class = "custom-control custom-switch">
                                    <input id = "atb_todos-chk" name = "atb_todos" type = "hidden" />
                                    <input id = "atb_todos-chk" class = "checkbox custom-control-input" type = "checkbox" onchange = "mostrarComAtb()" />
                                    <label for = "atb_todos-chk" class = "custom-control-label">Atribuir todos os produtos do contrato para todos os usuários da máquina<label>
                                </div>
                            </div>
                        </div>
                        <div class = "row com-atb-row">
                            <div class = "col-12">
                                <label for = "com-atb-row" class = "custom-label-form">Essas atribuições devem ter as seguintes propriedades:</label>
                            </div>
                        </div>
                        <div class = "row com-atb-row sem-margem" id = "com-atb-row">
                            <div class = "col-4">
                                <label for = "com-quantidade" class = "custom-label-form">Quantidade:</label>
                                <input id = "com-quantidade" name = "qtd" class = "form-control text-right" autocomplete = "off" type = "number" />
                            </div>
                            <div class = "col-4">
                                <label for = "com-validade" class = "custom-label-form">Validade em dias:</label>
                                <input id = "com-validade" name = "validade" class = "form-control text-right" autocomplete = "off" type = "number" />
                            </div>
                            <div class = "col-4">
                                <label for = "com-obrigatorio" class = "custom-label-form">Obrigatório:</label>
                                <select class = "form-control" id = "com-obrigatorio" name = "obrigatorio">
                                    <option value = "opt-1">SIM</option>
                                    <option value = "opt-0">NÃO</option>
                                </select>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-12">
                                <div class = "custom-control custom-switch">
                                    <input id = "travar_ret" name = "travar_ret" type = "hidden" />
                                    <input id = "travar_ret-chk" class = "checkbox custom-control-input" type = "checkbox" onchange = "$('#travar_ret').val($(this).prop('checked') ? '1' : '0')" />
                                    <label for = "travar_ret-chk" class = "custom-control-label">Solicitar senha de supervisor para produtos antes do vencimento<label>
                                </div>
                            </div>
                        </div>
                        <div class = "row mb-3">
                            <div class = "col-12">
                                <div class = "custom-control custom-switch">
                                    <input id = "travar_estq" name = "travar_estq" type = "hidden" />
                                    <input id = "travar_estq-chk" class = "checkbox custom-control-input" type = "checkbox" onchange = "$('#travar_estq').val($(this).prop('checked') ? '1' : '0')" />
                                    <label for = "travar_estq-chk" class = "custom-control-label">Habilitar retiradas somente com estoque disponível<label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class = "d-flex">
                    <button type = "button" class = "btn btn-target mx-auto mb-4 my-4 px-5">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>