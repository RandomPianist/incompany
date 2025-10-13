
<!-- Modal -->
<div class = "modal fade" id = "relatorioPessoasModal" aria-hidden = "true">
    <div class = "modal-dialog modal-lg" role = "document">
        <div class = "modal-content">
            <div class = "modal-header">
                <h6 class = "modal-title header-color">Pessoas</h6>
                <button type = "button" class = "close" data-dismiss = "modal" aria-label = "Close">
                    <span aria-hidden = "true">&times;</span>
                </button>
            </div>
            <form action = "{{ $root_url }}/relatorios/pessoas" method = "GET" target = "_blank">
                <div class = "modal-body">
                    <div class = "container">
                        <div class = "row">
                            <div class = "col-5 form-search">
                                <label for = "rel-empresa3" class = "custom-label-form">Empresa:</label>
                                <input id = "rel-empresa3"
                                    name = "empresa"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_empresa3"
                                    data-table = "empresas"
                                    data-column = "nome_fantasia"
                                    data-filter_col = ""
                                    data-filter = ""
                                    data-prox = "rel-setor2"
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_empresa3" name = "id_empresa" type = "hidden" onchange = "relatorio.mudou_empresa()" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "empresas"
                                    data-campo_id = "rel-id_empresa3"
                                    data-campo_descr = "rel-empresa3"
                                ></i>
                            </div>
                            <div class = "col-5 form-search">
                                <label for = "rel-setor2" class = "custom-label-form">Centro de custo:</label>
                                <input id = "rel-setor2"
                                    name = "setor"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_setor2"
                                    data-table = "setores"
                                    data-column = "descr"
                                    data-filter_col = ""
                                    data-filter = ""
                                    data-prox = "rel-pessoa3"
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_setor2" name = "id_setor" type = "hidden" onchange = "relatorio.mudou_setor()" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "setores"
                                    data-campo_id = "rel-id_setor3"
                                    data-campo_descr = "rel-setor3"
                                ></i>
                            </div>
                        </div>
                        <div class = "row">
                            <div class = "col-5 form-search">
                                <label for = "rel-pessoa3" class = "custom-label-form">Colaborador:</label>
                                <input id = "rel-pessoa3"
                                    name = "pessoa"
                                    class = "form-control autocomplete"
                                    data-input = "#rel-id_pessoa3"
                                    data-table = "pessoas"
                                    data-column = "nome"
                                    data-filter_col = ""
                                    data-filter = ""
                                    type = "text"
                                    autocomplete = "off"
                                />
                                <input id = "rel-id_pessoa3" name = "id_pessoa" type = "hidden" />
                            </div>
                            <div class = "col-1 pt-4 d-flex align-items-center">
                                <i
                                    class = "fa-sharp fa-regular fa-arrow-up-right-from-square atalho"
                                    data-atalho = "pessoas"
                                    data-campo_id = "rel-id_pessoa3"
                                    data-campo_descr = "rel-pessoa3"
                                ></i>
                            </div>
                            <div class = "col-6">
                                <label for = "rel-biometria" class = "custom-label-form">Biometria:</label>
                                <select class = "form-control" id = "rel-biometria" name = "biometria">
                                    <option value = "todos">Todos</option>
                                    <option value = "sim">Apenas com biometria</option>
                                    <option value = "nao">Apenas sem biometria</option>
                                </select>
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